<script setup>
import {computed, ref, watch} from "vue";
import {useField, useForm} from "vee-validate";
import * as yup from 'yup';
import Snackbar from "@/components/Snackbar.vue";
import axios from "axios";
import {formatDate, formatDateCustom} from "@/utils/functions.js";

const emit = defineEmits(['added', 'edited', 'close-modal', 'update:modelValue', 'refresh-data']);

const props = defineProps({
    modelValue: Boolean,
    element: Object,
    house: {
        type: Object,
        required: true,
    },
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
    title: yup.string().required().min(5, 'El nombre debe tener al menos 5 caracteres.'),
    starts_at:  yup.string().required().min(10, 'la fecha de inicio es requerida.'),
    expires_at: yup.string().required().min(10, 'la fecha de inicio es requerida.'),

});

// Configuración de vee-validate
const {handleSubmit, resetForm, setValues} = useForm({
    validationSchema: schema,
    initialValues: {
        house_id: null,
        title: '',
        details: '',
        starts_at: new Date().toISOString().substring(0, 10),
        expires_at: new Date().toISOString().substring(0, 10),
        access_code: '',
    }
});

const {value: title, errorMessage: titleError} = useField('title');
const {value: details} = useField('details');
const {value: starts_at, errorMessage: starts_atError} = useField('starts_at');
const {value: expires_at, errorMessage: expires_atError} = useField('expires_at');
const {value: access_code} = useField('access_code');

const isSubmit = ref(false);
const mySnackbar = ref(null);

const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Pase' : 'Adicionar Pase');

watch(() => props.element, (newValue) => {
    if (newValue && newValue.id) {
        setValues({
            title: newValue.title || "",
            details: newValue.details || "",
            starts_at: formatDateCustom(newValue.starts_at, 'YYYY-MM-DD') || "",
            expires_at: formatDateCustom(newValue.expires_at, 'YYYY-MM-DD') || "",
            access_code: newValue.access_code || "",
        });
    } else {
        resetForm();
    }
}, {immediate: true});

const submitForm = handleSubmit(async (values) => {

    const formData = new FormData();
    formData.append('title', values.title);
    formData.append('details', values.details);
    formData.append('starts_at', values.starts_at);
    formData.append('expires_at', values.expires_at);
    formData.append('access_code', values.access_code);
    formData.append('house_id', props.house.id);

    try {
        isSubmit.value = true;
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
        isSubmit.value = false;
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
                                    v-model="title"
                                    :error-messages="titleError"
                                    label="Name*"
                                    variant="outlined"
                                    density="compact"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="details"
                                    label="Detalles"
                                    rows="3"
                                    variant="outlined"
                                    density="compact"
                                ></v-textarea>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="access_code"
                                    :label="isEditing ? 'Código de Acceso (no editable)' : 'Este Código de Acceso se autogenera al crear el pase' "
                                    variant="outlined"
                                    density="compact"
                                    required
                                    readonly
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="starts_at"
                                    :error-messages="starts_atError"
                                    type="date"
                                    label="Fecha Inicio*"
                                    variant="outlined"
                                    density="compact"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="expires_at"
                                    :error-messages="expires_atError"
                                    type="date"
                                    label="Fecha Expiración*"
                                    variant="outlined"
                                    density="compact"
                                    required
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </v-container>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="grey" variant="flat" @click="close">Cancelar</v-btn>
                        <v-btn color="primary" variant="flat" type="submit"
                               :loading="isSubmit"
                               :disabled="isSubmit">Guardar
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
