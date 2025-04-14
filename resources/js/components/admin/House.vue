<script setup>
import {computed, ref, watch} from "vue";
import {useField, useForm} from 'vee-validate'
import Snackbar from "./../Snackbar.vue"
import * as yup from "yup";

const loading = ref(false)
const error = ref(null)

const emit = defineEmits(['house-added', 'house-edit', 'close-modal']);

const props = defineProps({
    house: Object,
    default: null
});

const schema = yup.object({
    paymentCode: yup.string().required('El código de pago es requerido.').min(4, 'El código de pago debe tener al menos 4 caracteres.'),
    propertyUnit: yup.string().required('La unidad de propiedad es requerida.').min(2, 'La unidad de propiedad debe tener al menos 2 caracteres.'),
    address: yup.string().required('La dirección es requerida.').min(5, 'La dirección debe tener al menos 5 caracteres.'),
    constructionArea: yup.number() // Usar number si es numérico
        .typeError('El área de construcción debe ser un número.')
        .required('El área de construcción es requerida.')
        .positive('El área de construcción debe ser positiva.'),
    participationPercentage: yup.number()
        .typeError('El porcentaje debe ser un número.')
        .required('El porcentaje de participación es requerido.')
        .min(0, 'El porcentaje no puede ser negativo.')
        .max(100, 'El porcentaje no puede ser mayor a 100.')
});


const {handleSubmit, resetForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: {
        paymentCode: '230808',
        propertyUnit: 'POPEYA201',
        address: 'TORRE POPEYA 201',
        constructionArea: '250',
        participationPercentage: '70'
    }
});

const paymentCode = useField('paymentCode')
const propertyUnit = useField('propertyUnit')
const address = useField('address')
const constructionArea = useField('constructionArea')
const participationPercentage = useField('participationPercentage')

// --- Computed Properties ---
const isEditing = computed(() => !!props.house?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Casa' : 'Adicionar Casa');


const submitForm = handleSubmit((values) => {
    try {
        const payload = {
            payment_code: values.paymentCode,
            property_unit: values.propertyUnit,
            address: values.address,
            construction_area: values.constructionArea,
            participation_percentage: values.participationPercentage,
        };

        if (isEditing.value) {
            emit('house-edit', {...payload, id: props.house.id});
        } else {
            emit('house-added', payload);
        }
        close();
    } catch (e) {
         error.value = 'Ocurrió un error al guardar.';
    }
});

const close = () => {
    emit('close-modal');
    resetForm();
}

watch(() => props.house, (newValue) => {
    if (newValue) {
        setValues({
            paymentCode: newValue.payment_code || '',
            propertyUnit: newValue.property_unit || '',
            address: newValue.address || '',
            constructionArea: newValue.construction_area ?? null,
            participationPercentage: newValue.participation_percentage ?? null,
        });
    } else {
        resetForm();
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
                    v-model="paymentCode.value.value"
                    :error-messages="paymentCode.errorMessage.value"
                    variant="outlined"
                    label="Código de pago"
                ></v-text-field>
                <v-text-field
                    v-model="propertyUnit.value.value"
                    :error-messages="propertyUnit.errorMessage.value"
                    variant="outlined"
                    label="Código de propiedad"
                ></v-text-field>
                <v-text-field
                    v-model="address.value.value"
                    :error-messages="address.errorMessage.value"
                    variant="outlined"
                    label="Address"
                ></v-text-field>
                <v-text-field
                    v-model="constructionArea.value.value"
                    :error-messages="constructionArea.errorMessage.value"
                    variant="outlined"
                    label="Area de construcción"
                ></v-text-field>
                <v-text-field
                    v-model="participationPercentage.value.value"
                    :error-messages="participationPercentage.errorMessage.value"
                    variant="outlined"
                    label="Porcentaje de participación"
                ></v-text-field>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue-darken-1" variant="text" @click="close">Cancelar</v-btn>
                    <v-btn color="red" type="submit">Guardar</v-btn>
                </v-card-actions>
            </v-form>
        </v-card-text>
    </v-card>
</template>

<style scoped>

</style>
