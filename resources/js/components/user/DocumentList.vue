<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-file-document-multiple-outline"></v-icon>
                 
                Documentos del Condominio
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

                <!-- Columna de Acciones -->
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Ver Detalles">
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
                    <!-- El botón de descarga podría ir aquí también, o solo en el detalle -->
                    <!--
                    <v-tooltip text="Descargar">
                       <template v-slot:activator="{ props }">
                            <v-btn
                              icon="mdi-download"
                              variant="text"
                              color="secondary"
                              size="small"
                              v-bind="props"
                              :href="getDownloadLink(item.id)"
                              target="_blank"
                            ></v-btn>
                       </template>
                    </v-tooltip>
                    -->
                </template>
            </v-data-table>
        </v-card>
        <!-- Dialogo para Detalles del Documento -->
        <DocumentDetailDialog
            v-model="showDetailDialog"
            :document-id="selectedDocumentId"
            @close="showDetailDialog = false"
        />

    </v-container>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import DocumentDetailDialog from './DocumentDetailDialog.vue'; // Importa el diálogo

// --- State ---
const documents = ref([]);
const loading = ref(false);
const error = ref(false);
const errorMessage = ref('');
const showDetailDialog = ref(false);
const selectedDocumentId = ref(null);

// --- Headers para v-data-table ---
const headers = ref([
    {title: 'Título', key: 'title', sortable: true},
    {title: 'Tipo', key: 'type', sortable: true},
    {title: 'Fecha Publicación', key: 'created_at', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

// --- Methods ---
const fetchDocuments = async () => {
    loading.value = true;
    error.value = false;
    errorMessage.value = '';
    try {
        // Asegúrate de que la URL base de tu API esté configurada
        // o usa la URL completa '/api/documents'
        const response = await axios.get('/user/documents/');
        documents.value = response.data;
    } catch (err) {
        console.error("Error fetching documents:", err);
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

// Función para formatear fecha (puedes usar una librería como date-fns o moment)
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = {year: 'numeric', month: 'short', day: 'numeric'};
    return new Date(dateString).toLocaleDateString(undefined, options);
};

// Función para obtener la URL de descarga directa (si decides poner el botón en la tabla)
// Es mejor tenerlo en el detalle para cargar los datos completos primero.
// const getDownloadLink = (docId) => {
//    // Asume que tu API está en el mismo dominio o tienes configurado el base URL de axios
//    return `/api/documents/${docId}/download`;
// }

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchDocuments();
});
</script>
