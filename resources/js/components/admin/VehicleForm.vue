<script setup>
import {computed, ref, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";

const emit = defineEmits(['vehicle-created', 'vehicle-edited', 'update:modelValue']);
const props = defineProps({
    modelValue: Boolean,
    vehicle: Object,
    user: Object,
    urlBase: {
        type: String,
        required: true
    },

});

const dialog = computed(({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
}));

const isEditing = computed(() => !!props.vehicle?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Miembro' : 'Adicionar Miembro');


// Schema de validación con Yup
const schema = yup.object({
    plateNumber: yup.string().required().min(2, 'la placa debe tener al menos 7 caracteres.'),
    brand: yup.string().required().min(2, 'la placa debe tener al menos 5 caracteres.'),
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
const isLoading = ref(false);
const mySnackbar = ref(null);

watch(() => props.vehicle, (newVehicle) => {

    if (newVehicle) {
        plateNumber.value.value = newVehicle.plate_number || "";
        brand.value.value = newVehicle.brand || "";
        model.value.value = newVehicle.model || "";
    }
}, {immediate: true});

const submitForm = handleSubmit(async (values) => {
    isLoading.value = true;
    const url = isEditing.value
        ? `${props.urlBase}/${props.vehicle?.id}`
        : `${props.urlBase}/`;
    const method = isEditing.value ? 'put' : 'post';
    const typeEmit = isEditing.value ? 'vehicle-edited' : 'vehicle-created';

    const payload = {
        web_user_id: props.user.id,
        plate_number: values.plateNumber,
        brand: values.brand,
        model: values.model,
    };

    try {
        const response = await axios({
            method: method,
            url: url,
            data: payload
        });
        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            emit(typeEmit);
            close()
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        isLoading.value = false;
    }
});

const close = () => {
    dialog.value = false;
    resetForm();
}
</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card>
            <v-card-title>{{ formTitle }}</v-card-title>
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
                        <v-btn color="grey" @click="close" :disabled="isLoading">Cancelar</v-btn>
                        <v-btn color="red" type="submit" :loading="isLoading">Guardar</v-btn>
                    </v-card-actions>
                </v-form>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
