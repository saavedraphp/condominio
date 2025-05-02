<script setup>
import {ref, onMounted, computed} from 'vue';
import DocumentDetailDialog from './DocumentDetailDialog.vue'; // Importa el diálogo
import {formatDate, getMegabytes} from "@/utils/functions.js";
import DocumentForm from "@/components/user/DocumentForm.vue";
import Snackbar from "@/components/Snackbar.vue";
import axios from "axios";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";

const props = defineProps({
    apiBaseUrl: {
        type: String,
        required: true
    },
    isAdmin: {
        type: Boolean,
        default: false
    }
});

// --- State ---
const documents = ref([]);
const loading = ref(false);
const error = ref(false);
const errorMessage = ref('');
const showDetailDialog = ref(false);
const selectedDocumentId = ref(null);
const showModal = ref(false)
const selectedElement = ref(null)
const mySnackbar = ref(null);
const isDownloading = ref(false);
const currentlyDownloading = ref(null);
const downloadError = ref(null);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

// --- Headers para v-data-table ---
const headers = ref([
    {title: 'Título', key: 'title', sortable: true},
    {title: 'Tamaño', key: 'format_size', sortable: true},
    {title: 'Fecha Publicación', key: 'created_at', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

// --- Methods ---
const getDocuments = async () => {
    loading.value = true;
    error.value = false;
    errorMessage.value = '';
    try {
        const response = await axios.get(props.apiBaseUrl);
        documents.value = response.data.map(doc => ({
            ...doc,
            format_size: getMegabytes(doc.size)+' MB',
        }));
    } catch (err) {
        error.value = true;
        errorMessage.value = 'No se pudieron cargar los documentos. Inténtalo de nuevo más tarde.';
        if (err.response && err.response.status === 401) {
            errorMessage.value = 'No estás autorizado para ver esta información.';
            // Aquí podrías redirigir al login
        }
    } finally {
        loading.value = false;
    }
};

const viewDocument = (document) => {
    selectedDocumentId.value = document.id;
    showDetailDialog.value = true;
};

const closeModal = (() => {
    showModal.value = false;
    selectedElement.value = null;
});
const downloadFile = async (item) => {
    const url = `${props.apiBaseUrl}/${item.id}/download`;
    currentlyDownloading.value = item.id;
    await handleAxiosDownload(url, `documento-${item.id}.pdf`);
    currentlyDownloading.value = null;
};

const refreshViewAndMessage = (message) => {
    mySnackbar.value.show(message, 'success');
    getDocuments();
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
const deleteDocument = async () => {
    try {
        if (!itemToDelete.value) return;
        const documentId = itemToDelete.value.id;
        const response = await axios.delete(`${props.apiBaseUrl}/${documentId}`)

        if (response.data && response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            await getDocuments();
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        mySnackbar.value.show(error.response?.data?.errors, 'error');
    } finally {
        closeDeleteModal();
    }
};

const openModalEdit = (item) => {
    selectedElement.value = {...item};
    showModal.value = true;
};

const openDeleteModal = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteModalItemName = computed(() => {
    if (!itemToDelete.value) return '';
    return `${itemToDelete.value.title} ID: ${itemToDelete.value.id}`;

});

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    setTimeout(() => {
        itemToDelete.value = null;
        isDeleting.value = false;
    }, 300);
};

// --- Lifecycle Hooks ---
onMounted(() => {
    getDocuments();
});
</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-file-document-multiple-outline"></v-icon>
                 
                Documentos del Condominio
                <v-spacer></v-spacer>
                <v-btn
                    v-if="isAdmin"
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="showModal = true"
                >
                    Agregar Documento
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-data-table
                v-if="!error"
                :headers="headers"
                :items="documents"
                :loading="loading"
                loading-text="Cargando documentos..."
                no-data-text="No hay documentos disponibles."
                item-value="id"
                class="elevation-1"
                hide-default-footer
                dense
            >
                <!-- Formateo de Fecha (Opcional) -->
                <template v-slot:item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                </template>
                <template v-slot:item.title="{ item }">
                    <div :title="item.title" class="text-truncate">
                        {{ item.title }}
                    </div>

                </template>
                <!-- Columna de Acciones -->
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Ver Detalles" v-if="false">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                icon="mdi-eye"
                                variant="text"
                                color="primary"
                                size="small"
                                v-bind="props"
                                @click="viewDocument(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Descargar Comprobante">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-download"
                                variant="text"
                                color="green-darken-1"
                                size="x-small"
                                :disabled="!item.file_path || isDownloading"
                                :loading="isDownloading && currentlyDownloading === item.id"
                                @click="downloadFile(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Ver" v-if="true">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-pencil"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="openModalEdit(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Eliminar">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-delete"
                                variant="text"
                                color="error"
                                size="small"
                                @click="openDeleteModal(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                </template>
            </v-data-table>
        </v-card>
        <v-dialog v-model="showModal" persistent max-width="600px">
            <DocumentForm
                :api-url="apiBaseUrl"
                :document="selectedElement"
                @document-added="refreshViewAndMessage"
                @document-updated="refreshViewAndMessage"
                @close-modal="closeModal"
            >
            </DocumentForm>
        </v-dialog>
        <DeleteConfirmationModal
            v-model:show="showDeleteModal"
            :item-name="deleteModalItemName"
            :loading="isDeleting"
            @confirm="deleteDocument"
            @cancel="closeDeleteModal"
        />
        <DocumentDetailDialog
            v-model="showDetailDialog"
            :api-base-url="apiBaseUrl"
            :document-id="selectedDocumentId"
            @close="showDetailDialog = false"
        />
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
