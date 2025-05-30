<script setup>
import { ref, computed, watch } from 'vue';
import { useField, useForm } from 'vee-validate';
import * as yup from 'yup';
import Snackbar from "@/components/Snackbar.vue";

const props = defineProps({
    modelValue: Boolean,
    quotationDataProp: {
        type: Object,
        default: null
    },
    isEditingProp: Boolean,
});
const emit = defineEmits(['update:modelValue', 'quotation-saved']);

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const quotationFormRef = ref(null);
const isLoading = ref(false); // Para un futuro spinner si es necesario

const isEditing = computed(() => props.isEditingProp);
const formTitle = computed(() => isEditing.value ? 'Editar Cotización' : 'Crear Cotización');

// Vee-Validate Schema for Quotation
const quotationSchema = yup.object({
    company_name: yup.string().required('El nombre  es requerida.'),
    amount: yup.number().typeError('El importe debe ser un número.').min(0.01, 'El importe debe ser mayor que 0.').required('Amount is required.'),
});

const { handleSubmit: handleQuotationSubmit, resetForm: resetQuotationForm, setValues: setQuotationValues, values: formValues } = useForm({
    validationSchema: quotationSchema,
    initialValues: {
        id: null,
        company_name: 'Company Name',
        amount: 100.00,
        file_object: null, // v-file-input usa un array
        file_path: null, // Para mostrar el nombre del archivo existente
    }
});
const mySnackbar = ref(null);
const company_name = useField('company_name');
const amount = useField('amount');
const file_object = useField('file_object'); // Para el v-file-input
const ACCEPTED_FILE_TYPES = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];


watch(() => props.quotationDataProp, (newVal) => {
    if (dialog.value) {
        if (newVal) {
            setQuotationValues({
                id: newVal.id || null,
                company_name: newVal.company_name || '',
                amount: newVal.amount || null,
                file_object: [],
                file_path: newVal.file_path || null, // Ruta del archivo existente
            });
        } else {
            resetQuotationForm();
        }
    }
}, { deep: true, immediate: true });

watch(dialog, (newVal) => {
    if(!newVal) {
        resetQuotationForm();
    } else {
        // Lógica de inicialización al abrir, ya cubierta por el watch de quotationDataProp
    }
});

/*const currentFileName = computed(() => {
    if (formValues.file_object && formValues.file_object[0]) {
        return formValues.file_object[0].name;
    }
    if (formValues.file_path) {
        return formValues.file_path.split('/').pop();
    }
    return '';
});*/

const submitQuotation = handleQuotationSubmit(async (values) => {
    let fileToUpload = null;

    if (Array.isArray(values.file_object) && values.file_object.length > 0) {
        fileToUpload = values.file_object[0];
    } else if (values.file_object instanceof File) {
        fileToUpload = values.file_object;
    }
    const isCreating = !isEditing;
    const hasSelectedFile = !!fileToUpload;
    const hasExistingFile = !!props.quotationDataProp?.file_path;

    if (!hasSelectedFile && !hasExistingFile) {
        mySnackbar.value.show('Por favor, seleccione un archivo para subir.', 'error');
        return;
    }

    const dataToEmit = {
        id: values.id,
        company_name: values.company_name,
        amount: parseFloat(values.amount),
        file_object: values.file_object, // Array de File (usualmente con 1 archivo) o null
        file_path: values.file_path // Ruta del archivo existente, para referencia
    };
    emit('quotation-saved', dataToEmit);
    // No cerramos el diálogo aquí, el padre lo hará después de procesar.
    // closeDialog(); // Opcional, si el padre no lo cierra.
});

function closeDialog() {
    emit('update:modelValue', false);
}
</script>

<template>
    <v-dialog :model-value="dialog" @update:model-value="closeDialog" persistent max-width="600px">
        <v-card :loading="isLoading">
            <v-form ref="quotationFormRef" @submit.prevent="submitQuotation">
                <v-card-title class="pa-4">
                    <span class="text-h5">{{ formTitle }}</span>
                </v-card-title>
                <v-card-text>
                    <v-container>
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="company_name.value.value"
                                    :error-messages="company_name.errorMessage.value"
                                    label="Nombre*"
                                    variant="outlined"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model.number="amount.value.value"
                                    :error-messages="amount.errorMessage.value"
                                    label="Monto (S/)*"
                                    type="number"
                                    step="0.01"
                                    prefix="S/"
                                    variant="outlined"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-file-input
                                    v-model="file_object.value.value"
                                    :error-messages="file_object.errorMessage.value"
                                    label="Archivo de cotización (PDF, DOCX)*"
                                    :accept="ACCEPTED_FILE_TYPES.join(',')"
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
                                <div v-if="isEditing && formValues.file_path && (!formValues.file_object || formValues.file_object.length === 0)" class="text-caption mt-n2 mb-2 ml-1 ps-1">
                                    Current file: {{ formValues.file_path.split('/').pop() }}. Select a new file to replace it.
                                </div>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card-text>
                <v-card-actions  class="pa-4">
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
