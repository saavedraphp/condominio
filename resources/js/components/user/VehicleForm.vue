<script setup>
import {ref, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';

const emit = defineEmits(['vehicle-added', 'vehicle-edit', 'close-modal']);
const props = defineProps({
    vehicle: Object,
});



// Schema de validación con Yup
const schema = yup.object({
    plateNumber: yup.string().required().min(2, 'la placa debe tener al menos 7 caracteres.'),
    brand:  yup.string().required().min(2, 'la placa debe tener al menos 5 caracteres.'),
    model: yup.string().required().min(2, 'la placa debe tener al menos 5 caracteres.'),
});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
    validationSchema: schema,
    initialValues: {
        plateNumber: 'AB-8888',
        brand: 'TOYOTA',
        model: 'AVANZA'
    }
});

// Campos de VeeValidate
const plateNumber = useField('plateNumber');
const brand = useField('brand');
const model = useField('model');

let titleForm = 'Adicionar Vehículo';

watch(() => props.vehicle, (newVehicle) => {
    if(props.vehicle?.id) {
        titleForm = 'Editar Vehículo';
    }
    if (newVehicle) {
        plateNumber.value.value = newVehicle.plate_number || "";
        brand.value.value = newVehicle.brand || "";
        model.value.value = newVehicle.model || "";
    }
}, {immediate: true});

const submitForm = handleSubmit((values) => {
    if (props.vehicle?.id) {
        emit('vehicle-edit', {
            id: props.vehicle.id,
            plate_number: values.plateNumber,
            brand: values.brand,
            model: values.model,
        });
    } else {
        emit('vehicle-added', {
            plate_number: values.plateNumber,
            brand: values.brand,
            model: values.model,
        });
    }

    resetForm({values: {plateNumber: '', brand: '', model: ''}});
    close();
});

const close = () => {
    emit('close-modal');
    resetForm();
}

</script>

<template>
    <v-card>
        <v-card-title>{{ titleForm }}</v-card-title>
        <v-card-text>
            <v-form @submit.prevent="submitForm">
                <v-text-field
                    variant="outlined"
                    label="Placa"
                    v-model="plateNumber.value.value"
                    :error-messages="plateNumber.errorMessage.value"
                />
                <v-text-field
                    variant="outlined"
                    label="Marca"
                    v-model="brand.value.value"
                    :error-messages="brand.errorMessage.value"
                />
                <v-text-field
                    variant="outlined"
                    label="Modelo"
                    v-model="model.value.value"
                    :error-messages="model.errorMessage.value"
                />
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="close">Cancelar</v-btn>
                    <v-btn color="red" type="submit">Guardar</v-btn>
                </v-card-actions>
            </v-form>
        </v-card-text>
    </v-card>
</template>

<style scoped>

</style>
