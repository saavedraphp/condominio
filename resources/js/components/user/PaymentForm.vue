<script setup>
import {computed, ref, watch} from "vue";
import {useField, useForm} from 'vee-validate'
import Snackbar from "./../Snackbar.vue"
import * as yup from "yup";
import axios from "axios";
import dayjs from "dayjs";

const emit = defineEmits(['payment-added', 'payment-edit', 'close-modal']);

const props = defineProps({
    payment: Object,
    default: null,
    house: {
        type: Object,
        required: true
    }
});

const error = ref(null)
const isRecording = ref(false)
const mySnackbar = ref(null);
const MAX_FILE_SIZE_MB = 2;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const existingImageUrl = ref(null);

const schema = yup.object({

    title: yup.string().required('El título es requerida.'),
    amount: yup.number()
        .typeError('El valor tiene que ser númerico.')
        .required('El monto es requerida.')
        .positive('El monto debe ser positivo.'),
    payment_date: yup.string().required().min(10, 'Este campo es requerido.'),
    transaction_code: yup.string()
        .required('El código de transacción es requerido.'),
    // Añade la validación para el campo del archivo
    paymentProof: yup.mixed()
        .required('El comprobante es requerido.')
        .test(
            'fileSize',
            `El archivo es demasiado grande (máx ${MAX_FILE_SIZE_MB}MB)`,
            (value) => {
                // value será el array [File] o []
                if (!value || value.length === 0) return true; // Dejar que .required() maneje la ausencia
                const file = value[0];

                if (!file) return true;
                return file.size <= MAX_FILE_SIZE_BYTES;
            }
        )
        .test(
            'fileType',
            'Formato de archivo no válido (solo JPG, PNG, GIF, WEBP)',
            (value) => {
                if (!value || value.length === 0) return true; // Dejar que .required() maneje la ausencia
                const file = value[0];

                if (!file) return true;
                return ACCEPTED_IMAGE_TYPES.includes(file.type);

            }
        )
});


const {handleSubmit, resetForm, setValues} = useForm({
    validationSchema: schema,
    initialValues: {
        title: 'Pago con fecha',
        amount: 200,
        transaction_code: '123654789',
        payment_date: dayjs().format('YYYY-MM-DD'),
        paymentProof: [],
    }
});

const title = useField('title')
const amount = useField('amount')
const {value: transaction_code, errorMessage: transaction_codeError} = useField('transaction_code')
const {value: payment_date, errorMessage: payment_dateError} = useField('payment_date');
const paymentProof = useField('paymentProof');

// --- Computed Properties ---
const isEditing = computed(() => !!props.payment?.id);
const formTitle = computed(() => isEditing.value ? 'Detalle del Pago' : 'Adicionar Pago');


const submitForm = handleSubmit(async (values) => {
    try {
        const formData = new FormData();
        formData.append('title', values.title);
        formData.append('amount', values.amount);
        formData.append('payment_date', values.payment_date);
        formData.append('transaction_code', values.transaction_code);

        //const cleanAmount = String(values.amount).replace(/,/g, '');

        let fileToUpload = null;
        const proofValue = values.paymentProof;

        if (Array.isArray(proofValue) && proofValue.length > 0) {
            fileToUpload = proofValue[0];
        } else if (proofValue instanceof File) {
            fileToUpload = proofValue;
        }


        // Doble chequeo por si acaso, aunque yup debería haberlo atrapado
        if (!fileToUpload && !props.payment?.file_path) {
            mySnackbar.value.show('Por favor, seleccione un archivo para subir.', 'error');
            return;
        }

        if (fileToUpload instanceof File) {
            formData.append('file_path', fileToUpload, fileToUpload.name);
        }

        const isEditingMode = isEditing.value;
        let url = `/user/house/${props.house.id}/payments`;
        const config = {
            headers: {
                'Accept': 'application/json',
            }
        };

        if (isEditingMode) {
            url = url+`/${props.payment.id}`;
            formData.append('_method', 'PUT');
        }

        isRecording.value = true;
        const response = await axios.post(url, formData, config);

        if (response.data.success) {
            if (isEditingMode) {
                emit('payment-edit', response.data.message);
            } else {
                emit('payment-added', response.data.message);
            }
            close();
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response.data.message || 'Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
        console.log(error);
    } finally {
        isRecording.value = false;
    }

});

const close = () => {
    emit('close-modal');
    resetForm();
}

watch(() => props.payment, (newValue) => {
    if (newValue) {
        setValues({
            id: newValue.id || null,
            title: newValue.title || '',
            amount: newValue.amount ?? null,
            transaction_code: newValue.transaction_code || '',
            payment_date: newValue.payment_date || '',
            file_path: [],
        });
        existingImageUrl.value = newValue.file_path_url || null;
    } else {
        resetForm();
        existingImageUrl.value = null;
    }
}, {immediate: true, deep: true});
</script>

<template>
    <v-card>
        <v-card-title>
            <span class="text-h5">{{ formTitle }}</span>
        </v-card-title>
        <v-card-text>
            <v-form @submit.prevent="submitForm" class="mt-2">
                <v-text-field
                    v-model="title.value.value"
                    :error-messages="title.errorMessage.value"
                    variant="outlined"
                    label="Título de pago"
                ></v-text-field>
                <v-text-field
                    v-model="amount.value.value"
                    :error-messages="amount.errorMessage.value"
                    prefix="S/"
                    variant="outlined"
                    label="Monto del pago"
                ></v-text-field>
                <v-text-field
                    v-model="transaction_code"
                    :error-messages="transaction_codeError"
                    variant="outlined"
                    label="Código de transacción"
                ></v-text-field>
                <v-text-field
                    v-model="payment_date"
                    :error-messages="payment_dateError"
                    label="Fecha (YYYY-MM-DD)"
                    variant="outlined"
                    type="date"
                ></v-text-field>
                <v-file-input
                    v-model="paymentProof.value.value"
                    :error-messages="paymentProof.errorMessage.value"
                    label="Selecciono un Comprobante (Imagen)"
                    variant="outlined"
                    :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                    prepend-icon=""
                    show-size
                    clearable
                ></v-file-input>
                <div v-if="isEditing && existingImageUrl" class="mb-3">
                    <p class="text-caption mb-1">Comprobante actual:</p>
                    <v-img
                        :src="existingImageUrl"
                        max-height="150"
                        contain
                        alt="Comprobante actual"
                        class="mb-2"
                    ></v-img>
                </div>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="flat" @click="close">Cancelar</v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        type="submit"
                        :loading="isRecording"
                        :disabled="isRecording">
                        Guardar
                    </v-btn>
                </v-card-actions>
            </v-form>
        </v-card-text>
        <Snackbar ref="mySnackbar"/>
    </v-card>
</template>

<style scoped>

</style>
