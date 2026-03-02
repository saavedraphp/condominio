<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import PreviewImageDialog from "@/components/user/PreviewImageDialog.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";

const emit = defineEmits(['update:modelValue', 'expense-created', 'expense-edited','update-element']);
const props = defineProps({
    modelValue: Boolean,
    element: Object,
    urlBase: {
        type: Object,
        required: true
    },
    budgetScope: {
        type: String,
        required: true
    },
    typeImageOptions: {
        type: Array,
        required: true
    },
});

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Schema de validación con Yup
const schema = yup.object({
    description: yup.string().required().min(2, 'El año debe tener al menos 4 caracteres.'),
    title: yup.string().required().min(2, 'El título debe tener al menos 10 caracteres.'),
    amount: yup.string().required('El monto es requerido.'),
    expense_date: yup.string().required().min(10, 'la fecha de inicio es requerida.'),
});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
    validationSchema: schema,
    initialValues: {
        description: '',
        title: '',
        amount: '',
        expense_date: new Date().toISOString().split('T')[0], // Formato YYYY-MM-DD
        documentFile: null,
    }
});

const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
// Campos de VeeValidate
const selectedAnnualBudget = ref(null);
const annualBudget = ref([]);
const isLoadingBudget = ref(false);
const expense_date = useField('expense_date');
const description = useField('description');
const {value: title, errorMessage: titleError} = useField('title');
const {value: documentFile, errorMessage: documentFileError} = useField('documentFile');
const amount = useField('amount');
const isRecording = ref(false);
const mySnackbar = ref(null);
const errorMessage = ref(null);
const currentSearch = ref('');
const selectExpenseType = ref(null);

const arrayImages = computed(() => {
    // Si no hay elemento (ej. creando nuevo), devolvemos un arreglo vacío
    if (!props.element) return [];

    const images = [];

    // 1. Verificamos si existe la imagen del Recibo
    // Ajusta 'file_path_url' o 'file_path' según lo que envíe tu backend
    if (props.element.file_path) {
        const option = props.typeImageOptions.find(opt => opt.id === 'payment');

        images.push({
            id: props.element.id, // Asegúrate de usar los IDs de tus constantes del backend
            name: option.name,
            type: option.id,
            file_path: props.element.file_path
        });
    }

    // 2. Verificamos si existe la imagen del Pago
    if (props.element.file_path_receipt) {
        const option = props.typeImageOptions.find(opt => opt.id === 'receipt');
        images.push({
            id: props.element.id,
            name: option.name,
            type: option.id,
            file_path: props.element.file_path_receipt
        });
    }

    // 3. Verificamos si existe la imagen del Trabajo
    if (props.element.file_path_job) {
        const option = props.typeImageOptions.find(opt => opt.id === 'job');
        images.push({
            id: props.element.id,
            name: option.name,
            type: option.id,
            file_path: props.element.file_path_job
        });
    }

    return images;
});

const selectElement = ref(null);
const showPreviewFileModal = ref(false);
const routeImage = ref(null);
const handleShowPreviewFile = (item) => {
    selectElement.value = item.id;
    showPreviewFileModal.value = true;
    let urlTemplate = props.urlBase.preview_payment;

    if (item.type === 'receipt') {
        urlTemplate = props.urlBase.preview_receipt;
    } else if (item.type === 'payment') {
        urlTemplate = props.urlBase.preview_payment;
    } else if (item.type === 'job') {
        urlTemplate = props.urlBase.preview_job;
    }

    //const urlTemplate = props.urlBase.preview_payment;
    const urlToPreview = urlTemplate
        .replace('PLACEHOLDER_1', item.id)

    routeImage.value = `${urlToPreview}`;
};

const handleClosePreviewFileModal = () => {
    selectElement.value = null;
    showPreviewFileModal.value = false
}

const isLoading = ref(false);


// --- Computed Properties ---
const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar gasto' : 'Adicionar gasto');

const getAnnualBudget = async () => {
    isLoadingBudget.value = true;
    annualBudget.value = [];
    selectedAnnualBudget.value = null;
    try {
        const response = await axios.get(`${props.urlBase['budget_scope']}/`, {
            params: {
                budget_scope: props.budgetScope,
                search: currentSearch.value,
            }
        });
        annualBudget.value = response.data.data || response.data || [];
    } catch (error) {
        errorMessage.value = error.response?.data?.message || error.message || 'No se pudo conectar a la API.';
        annualBudget.value = [];
    } finally {
        isLoadingBudget.value = false;
    }
};

watch(() => props.element, (newValue) => {
    if (newValue) {
        description.value.value = newValue.description || "";
        title.value = newValue.title || "";
        amount.value.value = newValue.amount || "";
        expense_date.value.value = new Date(newValue.expense_date).toISOString().split('T')[0] || "";
    } else {
        resetForm();
    }
}, {immediate: true});

watch(
    [() => props.element, annualBudget],
    ([element, budgets]) => {
        if (element && budgets.length > 0) {
            selectedAnnualBudget.value = budgets.find(
                item => item.id === element.annual_budget_id
            ) || null;
        }
    },
    {immediate: true}
);

const uploadImage = async () => {
    if (!documentFile.value) {
        mySnackbar.value.show('Por favor, seleccione una imagen para subir.', 'error');
        return;
    }

    if (!selectExpenseType.value) {
        mySnackbar.value.show('Por favor selecciona un tipo de imagen.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('file_path', documentFile.value, documentFile.value.name);
    formData.append('type_expense_id', selectExpenseType.value);

    try {
        const response = await axios.post(`${props.urlBase['base']}/${props.element.id}/upload-image`, formData, {
            headers: {
                'Accept': 'application/json',
            }
        });
        if (response.data.success) {
            documentFile.value = null;
            selectExpenseType.value = null;
            mySnackbar.value.show(response.data.message, 'success');
            emit('update-element', response.data.data)
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || error.message || 'Error al subir la imagen.', 'error');
    }
};

const submitForm = handleSubmit(async (values) => {

    const formData = new FormData();
    formData.append('description', values.description);
    formData.append('title', values.title);
    formData.append('amount', values.amount);
    formData.append('expense_date', values.expense_date);
    formData.append('annual_budget_id', selectedAnnualBudget.value.id);

    isRecording.value = true;
    let url = `${props.urlBase['base']}/`;

    if (isEditing.value) {
        url = `${props.urlBase['base']}/${props.element?.id}`
        formData.append('_method', 'PUT');
    }

    const typeEmit = isEditing.value ? 'expense-edited' : 'expense-created';

    const config = {
        headers: {
            'Accept': 'application/json',
        }
    };

    try {
        const response = await axios.post(url, formData, config);
        if (response.data.success) {
            emit(typeEmit, response.data.message);
            close()
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response.data.message, 'error');
    } finally {
        isRecording.value = false;
    }
});

const onSearchInput = (value) => {
    currentSearch.value = value;
};

const close = () => {
    dialog.value = false;
    resetForm();
}

onMounted(() => {
    getAnnualBudget();
});

const deleteItemDialog = ref(false);
const imageToDelete = ref(null);
const imageIndexToDelete = ref(-1);

function confirmDeleteImage(item, index) {
    imageToDelete.value = item;
    imageIndexToDelete.value = index;
    deleteItemDialog.value = true;
}

const deleteImage = async () => {
    try {
        if (!imageToDelete.value) return;
        const id = imageToDelete.value.id;
        const urlTemplate = props.urlBase.delete_image_expense;
        const urlToDelete = urlTemplate
            .replace('PLACEHOLDER_1', id)
            .replace('PLACEHOLDER_2', imageToDelete.value.type);

        const response = await axios.delete(urlToDelete)

        if (response.data && response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            emit('update-element', response.data.data)
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        mySnackbar.value.show(error.response?.data?.errors, 'error');
    } finally {
        closeDeleteModal();
    }
};

const closeDeleteModal = () => {
    deleteItemDialog.value = false;
    setTimeout(() => {
        imageIndexToDelete.value = null;
        isLoading.value = false;
    }, 300);
};
</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card>
            <v-card-title>{{ formTitle }}</v-card-title>
            <v-card-text>
                <v-form @submit.prevent="submitForm">

                    <v-row dense>
                        <v-col cols="12">
                            <v-autocomplete
                                v-model="selectedAnnualBudget"
                                :items="annualBudget"
                                :loading="isLoadingBudget"
                                :disabled="isLoadingBudget"
                                item-title="Tipo de gasto"
                                item-value="id"
                                label="Buscar y seleccionar el tipo de gasto..."
                                placeholder="Escribe el nombre..."
                                variant="outlined"
                                return-object
                                clearable
                                no-data-text="No se encontraron gastos"
                                @update:search="onSearchInput"
                            >
                                <!-- Opcional: Personalizar cómo se muestra cada item en la lista -->
                                <template v-slot:item="{ props, item }">
                                    <v-list-item
                                        v-bind="props"
                                        :title="`${item.raw.budget_type.name} (${item.raw.year})`"
                                    ></v-list-item>
                                </template>

                                <!-- Opcional: Mostrar algo más que el item-title cuando está seleccionado -->
                                <template v-slot:selection="{ item }">
                                    <span>{{ item.raw.budget_type.name }} ({{ item.raw.year }})</span>
                                </template>

                            </v-autocomplete>
                        </v-col>
                    </v-row>

                    <v-text-field
                        v-model="title"
                        :error-messages="titleError"
                        variant="outlined"
                        label="Título del gasto"
                    ></v-text-field>
                    <v-textarea
                        v-model="description.value.value"
                        :error-messages="description.errorMessage.value"
                        rows="3"
                        variant="outlined"
                        label="Descripción del gasto"
                    />
                    <v-row>
                        <v-col cols="12" md="6">

                            <v-text-field
                                v-model="amount.value.value"
                                :error-messages="amount.errorMessage.value"
                                variant="outlined"
                                label="Monto"
                                type="number"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="expense_date.value.value"
                                :error-messages="expense_date.errorMessage.value"
                                variant="outlined"
                                label="Fecha del gasto"
                                type="date"
                            />
                        </v-col>
                    </v-row>
                    <v-alert v-if="!element && !element?.id" type="info" density="compact"
                             class="ma-2" outlined>
                        Las imagenes podran ser asociadas a un gasto una vez que este haya sido creado, por lo que si
                        deseas agregar una imagen, primero debes guardar el gasto y luego editarlo para subir la imagen.
                    </v-alert>
                    <v-row v-else>
                        <v-col cols="12" md="6">
                            <v-file-input
                                v-model="documentFile"
                                :error-messages="documentFileError"
                                label="Imagen"
                                variant="outlined"
                                :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                                prepend-icon=""
                                show-size
                                clearable
                            ></v-file-input>
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="selectExpenseType"
                                :items="typeImageOptions"
                                :loading="isLoadingBudget"
                                :disabled="isLoadingBudget"
                                item-title="name"
                                item-value="id"
                                label="Tipo de imagen"
                                variant="outlined"
                                clearable
                            />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-btn color="primary"
                                   variant="flat"
                                   type="button"
                                   :loading="isRecording"
                                   :disabled="isRecording"
                                   block
                                   height="56"
                                   @click="uploadImage"

                            >
                                Subir
                            </v-btn>
                        </v-col>
                    </v-row>
                    <v-list v-if="arrayImages && arrayImages.length > 0" lines="two"
                            density="compact">
                        <v-list-item
                            v-for="(item, index) in arrayImages"
                            :key="item.id"
                            class="mb-2 elevation-1"
                        >
                            <v-list-item-title class="font-weight-medium">{{
                                    item.name
                                }}
                            </v-list-item-title>
                            <template v-slot:append>
                                <v-tooltip text="Ver"
                                           v-if="item.file_path">
                                    <template v-slot:activator="{ props: tooltipProps }">
                                        <v-btn v-bind="tooltipProps" icon="mdi-eye" variant="text"
                                               color="info"
                                               size="small"
                                               @click="handleShowPreviewFile(item)"
                                        ></v-btn>
                                    </template>
                                </v-tooltip>
                                <v-tooltip text="Delete" v-if="item.file_path">
                                    <template v-slot:activator="{ props: tooltipProps }">
                                        <v-btn v-bind="tooltipProps" icon="mdi-delete" variant="text"
                                               color="error"
                                               size="small"
                                               @click="confirmDeleteImage(item, index)"
                                        ></v-btn>
                                    </template>
                                </v-tooltip>
                            </template>
                        </v-list-item>
                    </v-list>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="grey"
                               variant="flat"
                               @click="close"
                               :disabled="isRecording"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn color="primary"
                               variant="flat"
                               type="submit"
                               :loading="isRecording"
                               :disabled="isRecording"
                        >
                            Guardar
                        </v-btn>
                    </v-card-actions>

                    <v-alert type="error" variant="tonal" v-if="errorMessage">
                        {{ errorMessage }}
                    </v-alert>
                </v-form>
                <PreviewImageDialog
                    v-model="showPreviewFileModal"
                    :api-base-url="routeImage"
                    :id="selectElement"
                    @close="handleClosePreviewFileModal"
                />
                <DeleteConfirmationModal
                    v-model:show="deleteItemDialog"
                    :loading="isLoading"
                    @confirm="deleteImage"
                    @cancel="closeDeleteModal"
                />
                <Snackbar ref="mySnackbar"/>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>


