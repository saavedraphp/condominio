<script setup>
import {computed, ref, watch} from "vue";
import {useField, useForm} from "vee-validate";
import * as yup from 'yup';
import Snackbar from "@/components/Snackbar.vue";
import axios from "axios";

const emit = defineEmits(['added', 'edited', 'close-modal', 'update:modelValue', 'refresh-data']);

const props = defineProps({
    modelValue: Boolean,
    element: Object,
    routes: {
        type: Object,
        required: true,
    },
    required: true,
});

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Schema de validación con Yup
const schema = yup.object({
    name: yup.string().required().min(2, 'El nombre debe tener al menos 2 caracteres.'),
    email: yup.string().required().email('Debe ser un correo electrónico válido.'),
    phone: yup.string().required().matches(/^\d+$/, 'El número de teléfono debe tener al menos 7 dígitos.').min(7),

});

// Configuración de vee-validate
const {handleSubmit, resetForm, setValues} = useForm({
    validationSchema: schema,
    initialValues: {
        name: '',
        email: '',
        phone: '',
        status: true
    }
});

const {value: name, errorMessage: nameError} = useField('name');
const {value: email, errorMessage: emailError} = useField('email');
const {value: phone, errorMessage: phoneError} = useField('phone');
const {value: status, errorMessage: statusError} = useField('status', 'boolean');
const isLoading = ref(false);
const mySnackbar = ref(null);

const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Seguridad' : 'Adicionar Seguridad');

watch(() => props.element, (newValue) => {
    if (newValue && newValue.id) {
        setValues({
            name: newValue.name || "",
            email: newValue.email || "",
            phone: newValue.phone || "",
            status: newValue.status === 'active',
        });
    } else {
        resetForm();
    }
}, {immediate: true});

const submitForm = handleSubmit(async (values) => {

    const formData = new FormData();
    formData.append('name', values.name);
    formData.append('email', values.email);
    formData.append('phone', values.phone);
    formData.append('status', values.status === true ? 'active' : 'inactive');

    try {
        isLoading.value = true;
        let url = props.routes.base;
        let action = 'added';
        if (isEditing.value) {
            url = `${props.routes.base}/${props.element.id}`
            formData.append('_method', 'PUT');
            action = 'edited';
        }

        let response = await axios.post(url, formData, {
            headers: {'Content-Type': 'multipart/form-data'}
        });

        if (response.data.success) {
            emit(action, response.data.message);
            close();
        } else {
            mySnackbar.value.show(response.data.message || 'Ocurrió un error inesperado.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Error al grabar el registro.', 'error');
        console.error(error);
    } finally {
        isLoading.value = false;
    }

});


const selectedElement = ref(null);

const close = () => {
    dialog.value = false;
}

</script>
<template>
    <v-dialog :model-value="dialog" @update:model-value="close" persistent max-width="800px" scrollable>
        <v-card>
            <v-card-title>
                <span class="text-h5">{{ formTitle }}</span>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text>
                <v-form @submit.prevent="submitForm">
                    <v-container>
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="name"
                                    :error-messages="nameError"
                                    label="Name*"
                                    variant="outlined"
                                    density="compact"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="email"
                                    :error-messages="emailError"
                                    label="Email*"
                                    variant="outlined"
                                    density="compact"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="phone"
                                    :error-messages="phoneError"
                                    label="Teléfono*"
                                    variant="outlined"
                                    density="compact"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-switch
                                    v-model="status"
                                    :label="status ? 'Activo' : 'Inactivo'"
                                    color="success"
                                    inset
                                ></v-switch>
                            </v-col>
                        </v-row>
                    </v-container>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="grey" variant="flat" @click="close">Cancelar</v-btn>
                        <v-btn color="primary" variant="flat" type="submit"
                               :loading="isLoading"
                               :disabled="isLoading">Guardar
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
