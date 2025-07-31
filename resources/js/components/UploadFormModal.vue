<script setup>
import {ref, computed, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';
import Snackbar from "@/components/Snackbar.vue";
import axios from "axios";

const props = defineProps({
    modelValue: Boolean,
    element: {
        type: Object,
        default: null
    },
    is_visible: {
        type: Boolean,
        default: true
    },
    routes: {
        type: Object,
    },
    isEditingProp: Boolean,
});
const emit = defineEmits(['update:modelValue', 'file-saved']);

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const quotationFormRef = ref(null);
const isLoading = ref(false); // Para un futuro spinner si es necesario

const isEditing = computed(() => props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar' : 'Adicionar');

// Vee-Validate Schema for Quotation
const quotationSchema = yup.object({
    title: yup.string().required('El nombre  es requerida.'),
    date_document: yup.date().required('La fecha es requerida.').typeError('Fecha inválida.'),
});

const {
    handleSubmit: handleQuotationSubmit,
    resetForm: resetForm,
    setValues: setQuotationValues,
    values: formValues
} = useForm({
    validationSchema: quotationSchema,
    initialValues: {
        id: null,
        title: '',
        file_object: null, // v-file-input usa un array
        file_path: null,
        date_document: new Date().toISOString().split('T')[0], // Fecha actual en formato YYYY-MM-DD
        is_visible: props.is_visible,

    }
});
const mySnackbar = ref(null);
const {value: title, errorMessage: titleError} = useField('title');
const {value: date_document, errorMessage: date_documentError} = useField('date_document');

const file_object = useField('file_object'); // Para el v-file-input
const {value: is_visible} = useField('is_visible'); // Para el checkbox de visibilidad

watch(() => props.element, (newVal) => {
    if (dialog.value) {
        if (newVal) {
            setQuotationValues({
                id: newVal.id || null,
                title: newVal.title || '',
                date_document: newVal.date_document || new Date().toISOString().split('T')[0],
                file_object: [],
                file_path: newVal.file_path || null, // Ruta del archivo existente
                is_visible: newVal.is_visible || true,
            });
        } else {
            resetForm();
        }
    }
}, {deep: true, immediate: true});

watch(dialog, (newVal) => {
    if (!newVal) {
        resetForm();
    } else {
        // Lógica de inicialización al abrir, ya cubierta por el watch de element
    }
});

const submitUpload = handleQuotationSubmit(async (values) => {
    let fileToUpload = null;
    const formData = new FormData();
    if (Array.isArray(values.file_object) && values.file_object.length > 0) {
        fileToUpload = values.file_object[0];
    } else if (values.file_object instanceof File) {
        fileToUpload = values.file_object;
    }
    const hasSelectedFile = !!fileToUpload;
    const hasExistingFile = !!props.element?.file_path;

    if (!hasSelectedFile && !hasExistingFile) {
        mySnackbar.value.show('Por favor, seleccione un archivo para subir.', 'error');
        return;
    }

    formData.append('title', values.title);
    formData.append('date_document', values.date_document);
    formData.append('is_visible', values.is_visible ? '1' : '0');
    if (fileToUpload) {
        formData.append('file_path', fileToUpload, fileToUpload.name);
    }

    isLoading.value = true;

    try {
        let urlFinal = `${props.routes.store_document}`;
        if (isEditing.value) {
            formData.append('_method', 'PUT');
            const templateUrl = `${props.routes.update_document}`;
            urlFinal = templateUrl.replace('PLACEHOLDER', props.element.id);
        }

        let response;
        response = await axios.post(`${urlFinal}`, formData, {
            headers: {'Content-Type': 'multipart/form-data'}
        });

        if (response.data.success) {
            const savedOrUpdated = response.data.data;
            emit('file-saved', savedOrUpdated);
            mySnackbar.value.show(response.data.message || 'Archivo cargada exitosamente.', 'success');
            file_object.value.value = null; // Limpiar el campo después de cargar
            closeDialog();
        } else {
            mySnackbar.value.show(response.data.message || 'Error al cargar la imagen.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Error al cargar la imagen.', 'error');
        console.error(error);
    } finally {
        isLoading.value = false;
    }

});

function closeDialog() {
    emit('update:modelValue', false);
}
</script>

<template>
    <v-dialog :model-value="dialog" @update:model-value="closeDialog" persistent max-width="600px">
        <v-card :loading="isLoading">
            <v-form @submit.prevent="submitUpload">
                <v-card-title class="pa-4">
                    <span class="text-h5">{{ formTitle }}</span>
                </v-card-title>
                <v-card-text>
                    <v-container>
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="title"
                                    :error-messages="titleError"
                                    label="Título*"
                                    variant="outlined"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="date_document"
                                    :error-messages="date_documentError"
                                    label="Fecha para el orden"
                                    type="date"
                                    variant="outlined"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-file-input
                                    v-model="file_object.value.value"
                                    :error-messages="file_object.errorMessage.value"
                                    label="Archivo *"
                                    variant="outlined"
                                    density="compact"
                                    show-size
                                    prepend-icon="mdi-paperclip"
                                    clearable
                                >
                                    <template v-slot:selection="{ fileNames }">
                                        <template v-for="fileName in fileNames" :key="fileName">
                                            <v-chip size="small" label color="primary" class="me-2">
                                                {{ fileName }}
                                            </v-chip>
                                        </template>
                                    </template>
                                </v-file-input>
                                <div
                                    v-if="isEditing && formValues.file_path && (!formValues.file_object || formValues.file_object.length === 0)"
                                    class="text-caption mt-n2 mb-2 ml-1 ps-1">
                                    Current file: {{ formValues.file_path.split('/').pop() }}. Select a new file to
                                    replace it.
                                </div>
                            </v-col>
                            <v-col cols="12">
                                <v-checkbox
                                    v-model="is_visible"
                                    label="Es visible"
                                    class="pa-0 ma-0"
                                    density="compact"
                                />
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="flat" @click="closeDialog">Cancel</v-btn>
                    <v-btn color="primary" variant="flat" type="submit">
                        Grabar
                    </v-btn>
                </v-card-actions>
            </v-form>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
