<script setup>
import {computed, ref, watch} from "vue";
import {useField, useForm} from 'vee-validate'
import Snackbar from "./../Snackbar.vue"
import * as yup from "yup";
import axios from "axios";

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
        title: '',
        amount: null,
        paymentProof: [],
    }
});

const title = useField('title')
const amount = useField('amount')
const paymentProof = useField('paymentProof');

// --- Computed Properties ---
const isEditing = computed(() => !!props.payment?.id);
const formTitle = computed(() => isEditing.value ? 'Detalle del Pago' : 'Adicionar Pago');


const submitForm = handleSubmit(async (values) => {
    try {
        const formData = new FormData();
        formData.append('title', values.title);
        formData.append('amount', values.amount);
        //const cleanAmount = String(values.amount).replace(/,/g, '');

        let fileToUpload = null;
        const proofValue = values.paymentProof;

        if (Array.isArray(proofValue) && proofValue.length > 0) {
            fileToUpload = proofValue[0];
        } else if (proofValue instanceof File) {
            fileToUpload = proofValue;
        }

        if (fileToUpload instanceof File) {
            formData.append('file_path', fileToUpload, fileToUpload.name);
        } else {
            mySnackbar.value.show('La imagen del comprobante es requerido', 'error');
            return;
        }

        isRecording.value = true;

        const response = await axios.post(`/user/house/${props.house.id}/payments/`, formData);
        if (response.data.success) {
            emit('payment-added', response.data.message);
            close();
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
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
                    :disabled="isEditing"
                ></v-text-field>
                <v-text-field
                    v-model="amount.value.value"
                    :error-messages="amount.errorMessage.value"
                    prefix="S/"
                    variant="outlined"
                    label="Monto del pago"
                    :disabled="isEditing"
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
                    :disabled="isEditing"
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
                    <v-btn color="blue-darken-1" variant="text" @click="close">Cancelar</v-btn>
                    <v-btn
                        color="red"
                        type="submit"
                        :loading="isRecording"
                        :disabled="isRecording || isEditing">
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
