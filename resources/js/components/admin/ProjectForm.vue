<script setup>
import {ref, computed, watch, nextTick} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';
import axios from "axios";
import dayjs from 'dayjs';
import Snackbar from "@/components/Snackbar.vue"; // Asegúrate que la ruta sea correcta
import QuotationFormModal from './QuotationFormModal.vue';
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue"; // Componente que crearemos

const props = defineProps({
    modelValue: Boolean, // Para v-model en el diálogo
    projectDataProp: {
        type: Object,
        default: null
    },
    urlBase: {
        type: String,
        required: true
    }

});
const emit = defineEmits(['update:modelValue', 'project-saved']);

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const mySnackbar = ref(null);
const projectFormRef = ref(null);
const activeTab = ref('details');
const isLoading = ref(false);

// --- Project Form ---
const isEditingProject = computed(() => !!(localProjectData.value && localProjectData.value.id));
const formTitle = computed(() => isEditingProject.value ? 'Editar Proyecto' : 'Adicionar Proyecto');

const deleteQuotationName = computed(() => {
    if (!quotationToDelete.value) return '';
    return `Proyecto: ${quotationToDelete.value.company_name} (ID: ${quotationToDelete.value.id})`;
});


const projectSchema = yup.object({
    name: yup.string().required('EL nombre es requerido.'),
    start_date: yup.string().required('La fecha de inicio es requerido.'),
    end_date: yup.string().required('La fecha de fin es requerido.')
        .test('is-after-start', 'La fecha de finalización debe ser posterior a la fecha de inicio', function (value) {
            const {start_date} = this.parent;
            return !start_date || !value || dayjs(value).isAfter(dayjs(start_date)) || dayjs(value).isSame(dayjs(start_date));
        }),
    additional_expenses: yup.number().min(0).nullable().default(0),
    details: yup.string().nullable(),
});

const { handleSubmit: handleProjectSubmit, resetForm: resetProjectForm, setValues: setProjectValues } = useForm({
    validationSchema: projectSchema,
    initialValues: {
        name: 'Nuevo Proyecto',
        start_date: dayjs().format('YYYY-MM-DD'),
        end_date: dayjs().add(1, 'month').format('YYYY-MM-DD'),
        additional_expenses: 100,
        details: 'Detalle del proyecto',
        chosen_quotation_id: null,
        quotations: [] // Para almacenar cotizaciones temporalmente si el proyecto es nuevo
    }
});

// Campos del proyecto
const name = useField('name');
const start_date = useField('start_date');
const end_date = useField('end_date');
const additional_expenses = useField('additional_expenses');
const details = useField('details');

// --- Quotations Management (within ProjectFormModal) ---
const localProjectData = ref({}); // Copia local del proyecto para editar, incluye sus cotizaciones
const showQuotationFormModal = ref(false);
const selectedQuotation = ref(null); // Para pasar al QuotationFormModal
const isEditingQuotation = ref(false);
const editingQuotationIndex = ref(-1); // Para saber si estamos editando una cotización existente en el array local
const deleteQuotationDialog = ref(false);
const quotationToDelete = ref(null);
const quotationIndexToDelete = ref(-1);


watch(() => props.projectDataProp, (newVal) => {
    if (dialog.value) { // Solo actualiza si el diálogo está abierto o se está abriendo
        if (newVal && newVal.id) { // Editing existing project
            // Deep copy y cargar cotizaciones si es necesario
            localProjectData.value = JSON.parse(JSON.stringify(newVal));
            if (!localProjectData.value.quotations) {
                localProjectData.value.quotations = []; // Asegurar que exista el array
            }
            setProjectValues({
                id: localProjectData.value.id,
                name: localProjectData.value.name,
                start_date: dayjs(localProjectData.value.start_date).format('YYYY-MM-DD'),
                end_date: dayjs(localProjectData.value.end_date).format('YYYY-MM-DD'),
                additional_expenses: localProjectData.value.additional_expenses || 0,
                details: localProjectData.value.details || '',
                chosen_quotation_id: localProjectData.value.chosen_quotation_id,
            });
            // Aquí podrías cargar las cotizaciones si no vienen con projectDataProp
            // fetchQuotationsForProject(localProjectData.value.id);
        } else { // Adding new project
            resetProjectForm(); // Resetea a initialValues
            localProjectData.value = { // Configura el objeto local para el nuevo proyecto
                ...projectSchema.getDefault(), // Usa los defaults de yup
                quotations: [],
                chosen_quotation_id: null
            };
        }
        activeTab.value = 'details'; // Siempre empieza en la pestaña de detalles
    }
}, { deep: true, immediate: true });


watch(dialog, (newVal) => {
    if(newVal) { // Cuando el diálogo se abre
        if (props.projectDataProp && props.projectDataProp.id) {
            // Logic for editing existing (already handled by projectDataProp watch)
        } else {
            resetProjectForm();
            localProjectData.value = {
                ...projectSchema.getDefault(),
                quotations: [],
                chosen_quotation_id: null
            };
        }
        activeTab.value = 'details';
    } else { // Cuando el diálogo se cierra
        resetProjectForm();
        localProjectData.value = {}; // Limpia los datos locales
    }
});


const submitProject = handleProjectSubmit(async (values) => {
    isLoading.value = true;
    const projectPayload = { ...values }; // Datos del formulario del proyecto

    // Añadir las cotizaciones del array local si es un proyecto nuevo
    if (!isEditingProject.value) {
        projectPayload.quotations = localProjectData.value.quotations.map(q => ({
            company_name: q.company_name,
            amount: q.amount,
            // El archivo se manejará de forma especial si se envía todo junto
            // Si se envían por separado, el file_path ya estaría (o el backend lo generaría)
            // Para el ejemplo de enviar todo junto, el backend tendría que procesar los archivos de `files_for_quotations`
            file_to_upload_name: q.file_object ? q.file_object[0]?.name : null // Nombre para referencia
        }));
    }
    // El chosen_quotation_id se actualiza por separado o al final en el backend si es nuevo.
    // Para un proyecto existente, ya se habría guardado. Para uno nuevo, el backend lo asignaría si una cotización se marca como elegida.
    projectPayload.chosen_quotation_id = localProjectData.value.chosen_quotation_id;


    // --- Lógica de FormData para el proyecto y sus archivos de cotización (si es nuevo y se envían juntos) ---
    const formData = new FormData();
    Object.keys(projectPayload).forEach(key => {
        if (key === 'quotations' && !isEditingProject.value) {
            // Para proyectos nuevos, serializamos las cotizaciones
            projectPayload.quotations.forEach((quotation, index) => {
                formData.append(`quotations[${index}][company_name]`, quotation.company_name);
                formData.append(`quotations[${index}][amount]`, quotation.amount);
                // Adjuntar el archivo de esta cotización si existe
                const originalQuotation = localProjectData.value.quotations[index];
                if (originalQuotation.file_object && originalQuotation.file_object[0]) {
                    formData.append(`quotations[${index}][file]`, originalQuotation.file_object[0], originalQuotation.file_object[0].name);
                }
            });
        } else if (key !== 'quotations') { // Evitar duplicar 'quotations' si no es un proyecto nuevo
            formData.append(key, projectPayload[key] === null ? '' : projectPayload[key]);
        }
    });


    try {
        let response;
        if (isEditingProject.value) {
            // Para PUT, no se envían las cotizaciones como array, se gestionan por separado.
            // Y _method: 'PUT' para Laravel si no usas axios.put directamente con FormData
            formData.append('_method', 'PUT');
            response = await axios.post(`${props.urlBase}/${localProjectData.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } else {
            response = await axios.post(props.urlBase, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }

        if (response.data.success) {
            emit('project-saved', response.data.message, isEditingProject.value);
            closeDialog();
        } else {
            mySnackbar.value.show(response.data.message || 'An unexpected error occurred.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Error saving project.', 'error');
    } finally {
        isLoading.value = false;
    }
});

function closeDialog() {
    emit('update:modelValue', false);
    // No resetear aquí, el watch(dialog) lo hace
}

// --- Quotation Methods ---
function openAddQuotationModal() {
    if (!isEditingProject.value && (!localProjectData.value.name || !localProjectData.value.start_date)) {
        mySnackbar.value.show('Please save the project details first or at least provide Name and Dates.', 'warning');
        activeTab.value = 'details'; // Llévalo a la pestaña de detalles
        // Opcionalmente, podrías permitir agregar cotizaciones offline y guardarlas todas con el proyecto.
        // return; // Descomenta si quieres forzar guardar proyecto primero.
    }
    selectedQuotation.value = null;
    isEditingQuotation.value = false;
    editingQuotationIndex.value = -1;
    showQuotationFormModal.value = true;
}

function openEditQuotationModal(quotation, index) {
    selectedQuotation.value = JSON.parse(JSON.stringify(quotation)); // Copia profunda
    isEditingQuotation.value = true;
    editingQuotationIndex.value = index; // Guardamos el índice para actualizar el array local
    showQuotationFormModal.value = true;
}

async function handleQuotationSaved(quotationData) {
    let fileToUpload = null;

    if (Array.isArray(quotationData.file_object) && quotationData.file_object.length > 0) {
        fileToUpload = quotationData.file_object[0];
    } else if (quotationData.file_object instanceof File) {
        fileToUpload = quotationData.file_object;
    }

    // Doble chequeo por si acaso, aunque yup debería haberlo atrapado
    if (!fileToUpload && (quotationData.file_path?.file_path && quotationData.file_path.value === null)) {
        mySnackbar.value.show('Por favor, seleccione un archivo para subir.', 'error');
        return;
    }
    const newQuotation = {
        id: quotationData.id || null, // ID si ya existía (viene de quotationData si se está editando)
        company_name: quotationData.company_name,
        amount: parseFloat(quotationData.amount),
        file_path: quotationData.file_path, // Ruta del archivo existente
        file_object: quotationData.file_object, // Nuevo archivo seleccionado por el usuario (File object)
    };

    if (isEditingProject.value && localProjectData.value.id) {
        isLoading.value = true;
        const quotationFormData = new FormData();
        quotationFormData.append('company_name', newQuotation.company_name);
        quotationFormData.append('amount', newQuotation.amount);
        if (fileToUpload) {
            quotationFormData.append('file', fileToUpload, fileToUpload.name);
        }

        let url = `${props.urlBase}/${localProjectData.value.id}/quotations`;
        let method = 'post';

        if (isEditingQuotation.value && newQuotation.id) { // Editando cotización existente
            url += `/${newQuotation.id}`;
            quotationFormData.append('_method', 'PUT'); // Laravel espera _method para FormData con PUT
        }

        try {
            const response = await axios.post(url, quotationFormData, { // Siempre POST con _method si es PUT/PATCH con FormData
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            if (response.data.success) {
                const savedOrUpdatedQuotation = response.data.quotation; // Asumiendo que el backend devuelve la cotización
                if (isEditingQuotation.value) {
                    localProjectData.value.quotations.splice(editingQuotationIndex.value, 1, savedOrUpdatedQuotation);
                } else {
                    localProjectData.value.quotations.push(savedOrUpdatedQuotation);
                }
                mySnackbar.value.show(response.data.message, 'success');
            } else {
                mySnackbar.value.show(response.data.message || 'Failed to save quotation.', 'error');
            }
        } catch (error) {
            mySnackbar.value.show(error.response?.data?.message || 'Error saving quotation.', 'error');
        } finally {
            isLoading.value = false;
            showQuotationFormModal.value = false;
        }

    } else {
        // Proyecto nuevo: Añadir/actualizar en el array local `localProjectData.value.quotations`
        // Se guardará todo junto cuando se guarde el proyecto.
        if (editingQuotationIndex.value > -1) { // Editando una del array local
            // Mantener el ID temporal si existiera (ej. Date.now()) o asignar uno si es realmente nueva
            newQuotation.id = localProjectData.value.quotations[editingQuotationIndex.value].id || `temp-${Date.now()}`;
            localProjectData.value.quotations.splice(editingQuotationIndex.value, 1, newQuotation);
        } else { // Agregando nueva al array local
            newQuotation.id = `temp-${Date.now()}`; // ID temporal para la key en v-for
            localProjectData.value.quotations.push(newQuotation);
        }
        showQuotationFormModal.value = false; // Cierra el modal de cotización
    }
}

function confirmDeleteQuotation(quotation, index) {
    quotationToDelete.value = quotation;
    quotationIndexToDelete.value = index;
    deleteQuotationDialog.value = true;
}

async function deleteQuotationConfirmed() {
    if (!quotationToDelete.value) return;
    isLoading.value = true;

    if (isEditingProject.value && localProjectData.value.id && quotationToDelete.value.id && !String(quotationToDelete.value.id).startsWith('temp-')) {
        // Proyecto existente y cotización con ID real: Eliminar vía API
        try {
            const response = await axios.delete(`${props.urlBase}/${localProjectData.value.id}/quotations/${quotationToDelete.value.id}`);
            if (response.data.success) {
                localProjectData.value.quotations.splice(quotationIndexToDelete.value, 1);
                if (localProjectData.value.chosen_quotation_id === quotationToDelete.value.id) {
                    localProjectData.value.chosen_quotation_id = null; // Deseleccionar si era la elegida
                }
                mySnackbar.value.show(response.data.message, 'success');
            } else {
                mySnackbar.value.show(response.data.message || 'Failed to delete quotation.', 'error');
            }
        } catch (error) {
            mySnackbar.value.show(error.response?.data?.message || 'Error deleting quotation.', 'error');
        }
    } else {
        // Proyecto nuevo o cotización sin ID real (temporal): Solo quitar del array local
        localProjectData.value.quotations.splice(quotationIndexToDelete.value, 1);
        if (localProjectData.value.chosen_quotation_id === quotationToDelete.value.id) {
            localProjectData.value.chosen_quotation_id = null;
        }
        mySnackbar.value.show('Quotation removed from list.', 'info');
    }

    isLoading.value = false;
    deleteQuotationDialog.value = false;
    quotationToDelete.value = null;
    quotationIndexToDelete.value = -1;
}

async function setChosenQuotation(quotationId) {
    if (localProjectData.value.id) { // Solo si el proyecto ya existe y tiene ID
        isLoading.value = true;
        try {
            const response = await axios.patch(`${props.urlBase}/${localProjectData.value.id}/choose-quotation`, {
                chosen_quotation_id: quotationId
            });
            if (response.data.success) {
                localProjectData.value.chosen_quotation_id = quotationId;
                // Actualizar también el projectDataProp si el padre lo necesita reactivamente
                // emit('project-updated-internally', localProjectData.value);
                mySnackbar.value.show('Cotización seleccionada', 'success');
            } else {
                mySnackbar.value.show(response.data.message || 'Failed to set chosen quotation.', 'error');
            }
        } catch (error) {
            mySnackbar.value.show(error.response?.data?.message || 'Error setting chosen quotation.', 'error');
        } finally {
            isLoading.value = false;
        }
    } else {
        // Si el proyecto es nuevo, solo actualizamos localmente
        localProjectData.value.chosen_quotation_id = quotationId;
    }
}
function unsetChosenQuotation() {
    if (localProjectData.value.id) {
        setChosenQuotation(null); // Llama a la API con null
    } else {
        localProjectData.value.chosen_quotation_id = null;
    }
}

function getQuotationFileName(quotation) {
    if (quotation.file_object && quotation.file_object[0]) {
        return quotation.file_object[0].name;
    }
    if (quotation.file_path) {
        return quotation.file_path.split('/').pop();
    }
    return 'No file';
}

function downloadQuotationFile(quotation) {
    // Implementar descarga. Si es un archivo recién subido (file_object), no se puede "descargar" aún.
    // Si es file_path, construir la URL de descarga.
    if(quotation.file_path) {
        window.open(`${window.location.origin}/storage/${quotation.file_path}`, '_blank');

    } else {
        mySnackbar.value.show('File not yet uploaded or no file path available.', 'info');
    }
}


const closeDeleteQuotationModal = () => {
    deleteQuotationDialog.value = false;
    setTimeout(() => {
        quotationToDelete.value = null;
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
                <v-tab value="details" :disabled="isLoading">Detalles del proyecto</v-tab>
                <v-tab value="quotations" :disabled="isLoading || !isEditingProject">Cotizaciones</v-tab>
            </v-tabs>
            <v-divider></v-divider>

            <v-card-text style="min-height: 300px; max-height: 60vh;"> <!-- Para scroll en el contenido -->
                <v-window v-model="activeTab">
                    <!-- Project Details Tab -->
                    <v-window-item value="details">
                        <v-form ref="projectFormRef" @submit.prevent="submitProject" class="pa-3">
                            <v-row dense>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="name.value.value"
                                        :error-messages="name.errorMessage.value"
                                        label="Proyecto*"
                                        variant="outlined"
                                        density="compact"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="start_date.value.value"
                                        :error-messages="start_date.errorMessage.value"
                                        label="Fecha Inicio*"
                                        type="date"
                                        variant="outlined"
                                        density="compact"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="end_date.value.value"
                                        :error-messages="end_date.errorMessage.value"
                                        label="Fecha Fin*"
                                        type="date"
                                        variant="outlined"
                                        density="compact"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model.number="additional_expenses.value.value"
                                        :error-messages="additional_expenses.errorMessage.value"
                                        label="Gastos Adicionales (S/)"
                                        type="number"
                                        prefix="S/"
                                        variant="outlined"
                                        density="compact"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="details.value.value"
                                        :error-messages="details.errorMessage.value"
                                        label="Detalle"
                                        variant="outlined"
                                        rows="4"
                                        density="compact"
                                    ></v-textarea>
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-window-item>

                    <!-- Quotations Tab -->
                    <v-window-item value="quotations" class="pa-1">
                        <div class="d-flex justify-end pa-2">
                            <v-btn color="secondary" @click="openAddQuotationModal" prepend-icon="mdi-plus" :disabled="isLoading">
                                Agregar Cotización
                            </v-btn>
                        </div>
                        <v-alert v-if="!isEditingProject && !localProjectData.id" type="info" density="compact" class="ma-2" outlined>
                            Las cotizaciones añadidas aquí se guardarán al guardar el nuevo proyecto.
                            Puedes gestionar (editar, eliminar o seleccionar) las cotizaciones una vez creado el proyecto.
                        </v-alert>

                        <v-list v-if="localProjectData.quotations && localProjectData.quotations.length > 0" lines="two" density="compact">
                            <v-list-item
                                v-for="(quotation, index) in localProjectData.quotations"
                                :key="quotation.id || `new-${index}`"
                                class="mb-2 elevation-1"
                            >
                                <template v-slot:prepend>
                                    <v-icon
                                        :color="localProjectData.chosen_quotation_id === quotation.id ? 'success' : 'grey-lighten-1'"
                                        @click="localProjectData.chosen_quotation_id === quotation.id ? unsetChosenQuotation() : setChosenQuotation(quotation.id)"
                                        style="cursor: pointer;"
                                    >
                                        {{ localProjectData.chosen_quotation_id === quotation.id ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                </template>

                                <v-list-item-title class="font-weight-medium">{{ quotation.company_name }}</v-list-item-title>
                                <v-list-item-subtitle>
                                    Amount: S/{{ parseFloat(quotation.amount || 0).toFixed(2) }} <br/>
                                    File: {{ getQuotationFileName(quotation) }}
                                </v-list-item-subtitle>

                                <template v-slot:append>
                                    <v-tooltip text="Download File" v-if="quotation.file_path || (quotation.file_object && quotation.file_object[0])">
                                        <template v-slot:activator="{ props: tooltipProps }">
                                            <v-btn v-bind="tooltipProps" icon="mdi-download" variant="text" color="info" size="small" @click="downloadQuotationFile(quotation)" :disabled="isLoading"></v-btn>
                                        </template>
                                    </v-tooltip>
                                    <v-tooltip text="Edit Quotation">
                                        <template v-slot:activator="{ props: tooltipProps }">
                                            <v-btn v-bind="tooltipProps"
                                                   icon="mdi-pencil"
                                                   variant="text"
                                                   color="primary"
                                                   size="small"
                                                   @click="openEditQuotationModal(quotation, index)" :disabled="isLoading"
                                            >
                                            </v-btn>
                                        </template>
                                    </v-tooltip>
                                    <v-tooltip text="Delete Quotation">
                                        <template v-slot:activator="{ props: tooltipProps }">
                                            <v-btn v-bind="tooltipProps" icon="mdi-delete" variant="text" color="error" size="small"
                                                   @click="confirmDeleteQuotation(quotation, index)" :disabled="isLoading"></v-btn>
                                        </template>
                                    </v-tooltip>
                                </template>
                            </v-list-item>
                        </v-list>
                        <v-alert v-else type="info" density="compact" class="ma-2" outlined>
                            Aún no se han añadido cotizaciones para este proyecto.
                        </v-alert>
                    </v-window-item>
                </v-window>
            </v-card-text>

            <v-divider></v-divider>
            <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn color="grey" variant="flat" @click="closeDialog" :disabled="isLoading">Cancel</v-btn>
                <v-btn color="primary" variant="flat" @click="submitProject" :loading="isLoading">
                    Grabar
                </v-btn>
            </v-card-actions>
        </v-card>

        <QuotationFormModal
            v-model="showQuotationFormModal"
            :quotation-data-prop="selectedQuotation"
            :is-editing-prop="isEditingQuotation"
            @quotation-saved="handleQuotationSaved"
        />
        <DeleteConfirmationModal
            v-model:show="deleteQuotationDialog"
            :item-name="deleteQuotationName"
            :loading="isLoading"
            @confirm="deleteQuotationConfirmed"
            @cancel="closeDeleteQuotationModal"
        />
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
