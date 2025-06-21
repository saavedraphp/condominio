<script setup>
import {ref, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {formatDate} from "@/utils/functions.js";
import HouseMonthlyChargeForm from "@/components/admin/HouseMonthlyChargeForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue"; // Reutilizas el tuyo

// Props (si necesitas, como isAdmin, etc. similar a tu otro componente de lista)
const props = defineProps({
    isAdmin: Boolean,
    urlBase: {
        type: String,
        require: true
    }
});

const mySnackbar = ref(null);
const projects = ref([]);
const loadingProjects = ref(true);
const search = ref(''); // Para la búsqueda en v-data-table

const showProjectFormModal = ref(false);
const showDeleteProjectModal = ref(false);
const selectedProject = ref(null);
const elementToDelete = ref(null);
const isDeleting = ref(false);
const currentlyDownloading = ref(null);
const isDownloading = ref(false);
const downloadError = ref(null);

const headers = ref([
    {title: 'ID', key: 'id', align: 'start', sortable: true},
    {title: 'Casa', key: 'house.address', align: 'start', sortable: true},
    {title: 'Periodo', key: 'period_name', sortable: true},
    {title: 'Monto Total', key: 'total_amount', sortable: true},
    {title: 'F. Emision', key: 'issued_date_format', align: 'end', sortable: true},
    {title: 'F. Vencimiento', key: 'due_format', align: 'end', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'center'},
]);

onMounted(() => {
    getMonthlyCharge();
});

async function getMonthlyCharge() {
    loadingProjects.value = true;
    try {
        const response = await axios.get(`${props.urlBase}`);
        projects.value = response.data.data.map(project => ({
            ...project,
            issued_date_format: formatDate(project.issued_date),
            due_format: formatDate(project.due_date),

        }));
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Failed to load projects.', 'error');
    } finally {
        loadingProjects.value = false;
    }
}



function formatCurrency(value) {
    return `S/${parseFloat(value).toFixed(2)}`;
}

const openAddProjectModal = () => {
    selectedProject.value = null;
    showProjectFormModal.value = true;
};

const openEditProjectModal = (element) => {
    selectedProject.value = JSON.parse(JSON.stringify(element));
    showProjectFormModal.value = true;
};

const openDeleteModal = (element) => {
    elementToDelete.value = element;
    showDeleteProjectModal.value = true;
};

const deleteModalItemName = computed(() => {
    if (!elementToDelete.value) return '';
    return `Registro ID: ${elementToDelete.value.id} (Monto: ${elementToDelete.value.total_amount})`;
});

const closeDeleteModal = () => {
    showDeleteProjectModal.value = false;
    setTimeout(() => {
        elementToDelete.value = null;
        isDeleting.value = false;
    }, 300);
};

const downloadFile = async (item) => {
    const url = `${props.urlBase}/${item.id}/download`;
    currentlyDownloading.value = item.id;
    await handleAxiosDownload(url, `documento-${item.id}.pdf`);
    currentlyDownloading.value = null;
};

const handleAxiosDownload = async (url, fallbackFilename = 'download') => {
    isDownloading.value = true;
    downloadError.value = null;
    let objectUrl = null;

    try {
        const response = await axios.get(url, {
            responseType: 'blob', // ¡Importante! Esperamos datos binarios
            // Puedes añadir headers aquí si necesitas, ej. Authorization:
            // headers: { 'Authorization': `Bearer ${your_token}` }
        });

        // --- Éxito: Procesar el Blob ---
        const blob = new Blob([response.data], {type: response.headers['content-type']});

        // Extraer nombre de archivo del header Content-Disposition (si existe)
        let filename = fallbackFilename;
        const contentDisposition = response.headers['content-disposition'];
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename\*?=['"]?(?:UTF-\d['"]*)?([^;"']+)['"]?;?/i);
            if (filenameMatch && filenameMatch[1]) {
                // Decodificar si está codificado (ej. filename*=UTF-8''nombre%20con%20espacios.pdf)
                try {
                    filename = decodeURIComponent(filenameMatch[1]);
                } catch (e) {
                    // Si falla la decodificación, usa el valor crudo (menos común)
                    filename = filenameMatch[1];
                    console.warn("Could not decode filename from Content-Disposition, using raw value:", filename);
                }
            }
        }

        // Crear enlace temporal y simular click
        objectUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = objectUrl;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link); // Limpiar el DOM

        return null; // Indica éxito

    } catch (error) {
        // --- Manejo de Errores ---
        let errorMessage = 'Ocurrió un error desconocido durante la descarga.';

        if (error.response) {
            // El servidor respondió con un estado de error (4xx, 5xx)
            console.error('Error response:', error.response);
            // El error JSON está en el Blob, necesitamos leerlo
            if (error.response.data instanceof Blob) {
                try {
                    // Intenta leer el Blob como texto y luego parsearlo como JSON
                    const errorText = await error.response.data.text();
                    const errorJson = JSON.parse(errorText);
                    //errorMessage = errorJson.message || `Error ${error.response.status}: Respuesta inesperada del servidor.`;
                    mySnackbar.value.show(errorJson.message, 'error');
                } catch (parseError) {
                    console.error('Error parsing error blob:', parseError);
                    errorMessage = `Error ${error.response.status}: No se pudo leer el mensaje de error del servidor.`;
                }
            } else {
                // Si la respuesta no es un Blob (poco común con responseType: 'blob', pero posible)
                errorMessage = error.response.data?.message || `Error ${error.response.status}`;
            }

        } else if (error.request) {
            // La solicitud se hizo pero no se recibió respuesta (problema de red?)
            console.error('Error request:', error.request);
            errorMessage = 'No se pudo conectar con el servidor para iniciar la descarga.';
        } else {
            // Error al configurar la solicitud
            console.error('Error message:', error.message);
            errorMessage = `Error al preparar la descarga: ${error.message}`;
        }

        return errorMessage; // Devolver el mensaje de error

    } finally {
        isDownloading.value = false;
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl); // ¡Importante! Liberar memoria
        }
    }
}

const deleteConfirmed = async () => {
    if (!elementToDelete.value) return;
    isDeleting.value = true;
    try {
        const response = await axios.delete(`${props.urlBase}/${elementToDelete.value.id}`);
        if (response.data && response.data.success) {
            mySnackbar.value.show(response.data.message || 'Project deleted successfully.', 'success');
            await getMonthlyCharge(); // Recarga la lista
        } else {
            mySnackbar.value.show(response.data.message || 'Failed to delete project.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'An error occurred while deleting.', 'error');
    } finally {
        isDeleting.value = false;
        closeDeleteModal();
    }
};

const refreshList = (message) => {
    mySnackbar.value.show(message, 'success');
    showProjectFormModal.value = false;
    getMonthlyCharge();
};

</script>

<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-briefcase-check-outline"></v-icon>
                  Gestión Cobros Mensuales
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="openAddProjectModal"
                >
                    Agregar
                </v-btn>
            </v-card-title>

            <v-text-field v-if="false"
                v-model="search"
                density="compact"
                label="Search Projects..."
                prepend-inner-icon="mdi-magnify"
                variant="solo-filled"
                flat
                hide-details
                single-line
                class="pa-2"
            ></v-text-field>
            <v-divider></v-divider>

            <v-data-table
                :headers="headers"
                :items="projects"
                :search="search"
                :loading="loadingProjects"
                class="elevation-1"
                item-value="id"
            >
                <template v-slot:item.start_date="{ item }">
                    <span>{{ item.start_date_format }}</span>
                </template>
                <template v-slot:item.end_date="{ item }">
                    <span>{{ item.end_date_format }}</span>
                </template>
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Descargar Archivo">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-download"
                                variant="text"
                                color="green-darken-1"
                                size="x-small"
                                :disabled="!item.pdf_path || isDownloading"
                                :loading="isDownloading && currentlyDownloading === item.id"
                                @click="downloadFile(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Delete Project">
                        <template v-slot:activator="{ props: tooltipProps }">
                            <v-btn
                                v-bind="tooltipProps"
                                icon="mdi-delete"
                                variant="text"
                                color="error"
                                size="small"
                                @click="openDeleteModal(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                </template>
                <template v-slot:loading>
                    <v-skeleton-loader type="table-row@5"></v-skeleton-loader>
                </template>
                <template v-slot:no-data v-if="false">
                    <v-alert type="info" class="ma-3">No hay datos.</v-alert>
                </template>
            </v-data-table>
        </v-card>

        <HouseMonthlyChargeForm
            v-model="showProjectFormModal"
            :project-data-prop="selectedProject"
            :url-base="props.urlBase"
            @monthly-charge-created="refreshList"
        />

        <DeleteConfirmationModal
            v-model:show="showDeleteProjectModal"
            :item-name="deleteModalItemName"
            :loading="isDeleting"
            @confirm="deleteConfirmed"
            @cancel="closeDeleteModal"
        />

        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
