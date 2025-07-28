<script setup>
import {computed, ref, watch} from "vue";
import axios from "axios";
import * as yup from "yup";
import dayjs from "dayjs";
import {useField, useForm} from "vee-validate";
import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";

const props = defineProps({
    modelValue: Boolean,
    expense: {
        type: Object,
        default: null
    },
    routes: {
        type: Object,
        required: true
    }
});
const emit = defineEmits(['update:modelValue', 'other-expenses-saved', 'refresh-data']);

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const mySnackbar = ref(null);
const activeTab = ref('other-expenses');
const isLoading = ref(false);
const file_object = useField('file_object');
const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
const deleteDialog = ref(false);
const elementToDelete = ref(null);
const elementIndexToDelete = ref(-1);
const localData = ref({});
const hasChanges = ref(false);

// ---  Form ---
// SOLUCIÓN: `isEditing` ahora depende directamente del prop, la fuente de verdad.
const isEditing = computed(() => !!(props.expense && props.expense.id));
const formTitle = computed(() => isEditing.value ? 'Editar Gasto' : 'Adicionar Gasto');

// --- Formulario y Validación con Vee-Validate ---
// Ya no necesitamos `localExpense`. Vee-Validate maneja el estado del formulario.

const expenseSchema = yup.object({
    // He corregido "details" por "description" para que coincida con tus campos.
    title: yup.string().required('EL título es requerido.'),
    description: yup.string().nullable(),
    amount: yup.number().required('El monto es requerido.').positive('El monto debe ser positivo.'),
    date: yup.string().required('La fecha es requerida.'),
});

const {
    handleSubmit: handleExpenseSubmit,
    resetForm,
    setValues
} = useForm({
    validationSchema: expenseSchema,
    initialValues: {
        title: '',
        date: dayjs().format('YYYY-MM-DD'),
        description: '',
        amount: 0,
        details_other_expenses: [],
    }
});

// Campos vinculados al formulario (esto está bien)
const {value: title, errorMessage: titleError} = useField('title');
const {value: description, errorMessage: descriptionError} = useField('description');
const {value: date, errorMessage: dateError} = useField('date');
const {value: amount, errorMessage: amountError} = useField('amount');

// --- Lógica del Submit ---
const submitExpense = handleExpenseSubmit(async (values) => {
    isLoading.value = true;

    const formData = new FormData();
    formData.append('title', values.title);
    formData.append('description', values.description || '');
    formData.append('amount', values.amount);
    formData.append('date', values.date);


    try {
        let response;
        if (isEditing.value) {
            formData.append('_method', 'PUT');
            // Usamos `props.expense.id` porque es la fuente de verdad del ID a editar
            response = await axios.post(`${props.routes.base}/${props.expense.id}`, formData, {
                headers: {'Content-Type': 'multipart/form-data'}
            });
        } else {
            response = await axios.post(props.routes.base, formData, {
                headers: {'Content-Type': 'multipart/form-data'}
            });
        }

        if (response.data.success) {
            emit('other-expenses-saved', response.data.message, isEditing.value);
            closeDialog();
        } else {
            mySnackbar.value.show(response.data.message || 'Ocurrió un error inesperado.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Error al grabar el gasto.', 'error');
    } finally {
        isLoading.value = false;
    }
});

function closeDialog() {
    dialog.value = false;
    if(hasChanges.value) {
        emit('refresh-data');
    }
}

// --- Watcher Unificado ---
// Este watcher ahora funcionará correctamente porque `isEditing` es correcto.
watch(() => props.expense, (newVal) => {
    activeTab.value = 'other-expenses';
    if (newVal && newVal.id) {
        localData.value = JSON.parse(JSON.stringify(newVal));
        if (!localData.value.quotations) {
            localData.value.quotations = []; // Asegurar que exista el array
        }
        // MODO EDICIÓN: `isEditing` es TRUE, entra aquí correctamente.
        setValues({
            title: props.expense.title,
            date: dayjs(props.expense.date.substring(0, 10)).format('YYYY-MM-DD'),
            description: props.expense.description || '',
            amount: props.expense.amount || 0,
            details_other_expenses: props.expense.details_other_expenses || [],
        });
    } else {
        // MODO CREACIÓN: `isEditing` es FALSE, entra aquí correctamente.
        resetForm();
    }
    hasChanges.value = false;
}, {deep: true, immediate: true});

const submitImage = async () => {
    if (!file_object.value.value) {
        mySnackbar.value.show('Por favor, selecciona un archivo de imagen.', 'error');
        return;
    }

    isLoading.value = true;
    const formData = new FormData();
    formData.append('file_path', file_object.value.value);

    try {
        const templateUrl = `${props.routes.store_details}`;
        const urlFinal = templateUrl.replace('PLACEHOLDER', props.expense.id);

        let response;
        response = await axios.post(`${urlFinal}`, formData, {
            headers: {'Content-Type': 'multipart/form-data'}
        });

        if (response.data.success) {
            const savedOrUpdatedQuotation = response.data.data;
            localData.value.details_other_expenses.push(savedOrUpdatedQuotation);
            mySnackbar.value.show(response.data.message || 'Imagen cargada exitosamente.', 'success');
            file_object.value.value = null; // Limpiar el campo después de cargar
            hasChanges.value = true;
        } else {
            mySnackbar.value.show(response.data.message || 'Error al cargar la imagen.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Error al cargar la imagen.', 'error');
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

function confirmDeleteDetail(item, index) {
    elementToDelete.value = item;
    elementIndexToDelete.value = index;
    deleteDialog.value = true;
}

const deleteElementName = computed(() => {

    if (!elementToDelete.value) return '';
    return `Imagen : ${elementToDelete.value.original_filename} (ID: ${elementToDelete.value.id})`;
});

async function deleteConfirmed() {
    if (!elementToDelete.value) return;
    isLoading.value = true;
    const urlTemplate =  props.routes.destroy_details;
    const urlToDelete = urlTemplate
        .replace('PLACEHOLDER_1', props.expense.id)
        .replace('PLACEHOLDER_2', elementToDelete.value.id);

    try {
        const response = await axios.delete(urlToDelete);
        if (response.data.success) {
            localData.value.details_other_expenses.splice(elementIndexToDelete.value, 1);
            mySnackbar.value.show(response.data.message, 'success');
            hasChanges.value = true;
        } else {
            mySnackbar.value.show(response.data.message || 'Failed to delete quotation.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Error deleting quotation.', 'error');
        console.error(error);
    }
    finally {
        closeDeleteModal();
    }
}

const closeDeleteModal = () => {
    deleteDialog.value = false;
    if(hasChanges.value) {
        emit('update:modelValue', false);
        emit('refresh-data');
    }
    setTimeout(() => {
        elementToDelete.value = null;
        isLoading.value = false;
    }, 300);
};
</script>

<template>
    <v-dialog :model-value="dialog" @update:model-value="closeDialog" persistent max-width="800px" scrollable>
        <v-card :loading="isLoading">
            <v-card-title class="pa-4">
                <span class="text-h5">{{ formTitle }}</span>
            </v-card-title>

            <v-tabs v-model="activeTab" color="primary" grow class="mb-0">
                <v-tab value="other-expenses" :disabled="isLoading">Detalles del Gasto</v-tab>
                <v-tab value="details" :disabled="isLoading || !isEditing">Adjuntar imagenes</v-tab>
            </v-tabs>
            <v-divider></v-divider>

            <v-card-text style="min-height: 300px; max-height: 60vh;">
                <v-window v-model="activeTab">
                    <!-- CORREGIDO: El 'value' ahora coincide con la pestaña del formulario -->
                    <v-window-item value="other-expenses">
                        <!-- Usamos el submit del formulario, no es necesario un ref -->
                        <v-form @submit.prevent="submitExpense" class="pa-3">
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
                                    <v-textarea
                                        v-model="description"
                                        :error-messages="descriptionError"
                                        label="Descripción"
                                        rows="3"
                                        variant="outlined"
                                    ></v-textarea>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="amount"
                                        :error-messages="amountError"
                                        label="Monto"
                                        variant="outlined"
                                        density="compact"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="date"
                                        :error-messages="dateError"
                                        label="Fecha*"
                                        type="date"
                                        variant="outlined"
                                        density="compact"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                            <v-row class="mt-4">
                                <v-col cols="12" class="text-end">
                                    <v-btn
                                        color="grey"
                                        @click="closeDialog"
                                        :disabled="isLoading"
                                    >
                                        Cancelar
                                    </v-btn>
                                    <v-btn
                                        type="submit"
                                        color="primary"
                                        class="ms-2"
                                        :loading="isLoading"
                                        :disabled="isLoading"
                                    >
                                        {{ isEditing ? 'Actualizar' : 'Crear' }}
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-window-item>
                    <v-window-item value="details">
                        <v-row>
                            <v-col cols="12" md="8">
                                <v-file-input
                                    v-model="file_object.value.value"
                                    :error-messages="file_object.errorMessage.value"
                                    label="Archivo de imagen"
                                    :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                                    variant="outlined"
                                    density="compact"
                                    show-size
                                    prepend-icon="mdi-paperclip"
                                    clearable
                                >
                                </v-file-input>
                            </v-col>
                            <v-col cols="12" md="4" class="text-end">
                                <v-btn
                                    color="primary"
                                    @click="submitImage"
                                    :loading="isLoading"
                                    :disabled="isLoading"
                                >
                                    Cargar Imagen
                                </v-btn>
                            </v-col>
                            <v-col>
                                <v-list v-if="localData.details_other_expenses.length > 0" lines="two"
                                        density="compact">
                                    <v-list-item
                                        v-for="(item, index) in localData.details_other_expenses"
                                        :key="item.id || `new-${index}`"
                                        class="mb-2 elevation-1"
                                    >
                                        <v-list-item-title class="font-weight-medium">{{
                                                item.original_filename
                                            }}
                                        </v-list-item-title>

                                        <template v-slot:append>
                                            <v-tooltip text="Download File"
                                                       v-if="false">
                                                <template v-slot:activator="{ props: tooltipProps }">
                                                    <v-btn v-bind="tooltipProps" icon="mdi-download" variant="text" color="info"
                                                           size="small" @click="downloadQuotationFile(item)"
                                                           :disabled="isLoading"></v-btn>
                                                </template>
                                            </v-tooltip>
                                            <v-tooltip text="Delete imagen">
                                                <template v-slot:activator="{ props: tooltipProps }">
                                                    <v-btn v-bind="tooltipProps" icon="mdi-delete" variant="text" color="error"
                                                           size="small"
                                                           @click="confirmDeleteDetail(item, index)"
                                                           :disabled="isLoading"></v-btn>
                                                </template>
                                            </v-tooltip>
                                        </template>
                                    </v-list-item>
                                </v-list>
                                <v-alert v-else type="info" density="compact" class="ma-2" outlined>
                                    Aún no se han adjuntado imagenes para este gasto.
                                </v-alert>
                            </v-col>
                        </v-row>
                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>
        <DeleteConfirmationModal
            v-model:show="deleteDialog"
            :item-name="deleteElementName"
            :loading="isLoading"
            @confirm="deleteConfirmed"
            @cancel="closeDeleteModal"
        />
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
