<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

// --- Props & Emits ---
const props = defineProps({
    modelValue: Boolean, // Para controlar la visibilidad del diálogo (v-model)
    id: {
        type: [Number, String, null],
        required: true,
    },
    apiBaseUrl: {
        type: String,
        default: '/api/documents',
    },
});
const emit = defineEmits(['update:modelValue', 'close']);

// --- State ---
const documentData = ref(null);
const loading = ref(false);
const error = ref(null); // Usar null para el estado inicial, y un string para el mensaje

// --- Computed ---
const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

// URL principal del archivo para evitar inconsistencias
const fileUrl = computed(() => documentData.value?.url || '');

const isImage = computed(() => documentData.value?.mime_type?.startsWith('image/'));
const isPdf = computed(() => documentData.value?.mime_type === 'application/pdf');
// Para otros tipos de archivo que se pueden previsualizar en el navegador (audio, video)
const isBrowserPreviewable = computed(() => {
    const mime = documentData.value?.mime_type || '';
    return mime.startsWith('video/') || mime.startsWith('audio/');
});


// --- Methods ---
const fetchDocumentDetails = async (documentId) => {
    if (!documentId) return;

    loading.value = true;
    error.value = null;
    documentData.value = null;

    try {
        // CORRECCIÓN CRÍTICA: Se añade el ID a la URL de la API
        const response = await axios.get(`${props.apiBaseUrl}`);

        if (response.data && response.data.data) {
            documentData.value = response.data.data;
        } else {
            // Si la API responde 200 pero sin datos, lo consideramos un error lógico
            throw new Error('La respuesta de la API no contiene los datos del documento.');
        }
    } catch (err) {
        console.error(`Error no se pudo obtener el archivo Id ${documentId}:`, err);
        console.log(err.response.data);
        if (err.response.data) {
            switch (err.response.status) {
                case 404:
                    error.value = err.response.data.message || 'El archivo no fue encontrado.';
                    break;
                case 401:
                    error.value = 'No tienes autorización para ver este documento.';
                    break;
                default:
                    error.value = `Error del servidor: ${err.response.statusText || 'No se pudo cargar el documento.'}`;
            }
        } else {
            error.value = err.message || 'Error de red o no se pudo conectar con el servidor.';
        }
    } finally {
        loading.value = false;
    }
};

const closeDialog = () => {
    emit('close');
    dialogVisible.value = false;
};

// Función para formatear fecha (ahora sí la usaremos)
const formatDate = (dateString) => {
    if (!dateString) return 'No disponible';
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const formatSize = (bytes) => {
    if (bytes === 0 || !bytes) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// --- Watchers ---
watch(() => [props.id, props.modelValue], ([newId, isVisible]) => {
    if (isVisible && newId) {
        fetchDocumentDetails(newId);
    } else if (!isVisible) {
        // Limpia el estado cuando el diálogo se cierra para no mostrar datos viejos
        documentData.value = null;
        loading.value = false;
        error.value = null;
    }
}, { immediate: true }); // `immediate` puede ser útil si el diálogo se monta ya visible

</script>

<template>
    <v-dialog
        :model-value="dialogVisible"
        @update:model-value="dialogVisible = $event"
        max-width="800px"
        persistent
        scrollable
    >
        <v-card>
            <v-card-title class="d-flex align-center py-3">
                <span class="text-h6">Detalles del Documento</span>
                <v-spacer></v-spacer>
                <v-btn icon="mdi-close" variant="text" @click="closeDialog"></v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <v-card-text style="min-height: 400px;">
                <!-- 1. Estado de Carga -->
                <div v-if="loading" class="d-flex justify-center align-center fill-height">
                    <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                </div>
                <!-- Opcional: un skeleton loader para una mejor UX -->
                <!-- <v-skeleton-loader v-if="loading" type="article, actions"></v-skeleton-loader> -->

                <!-- 2. Estado de Error -->
                <div v-else-if="error" class="d-flex justify-center align-center fill-height">
                    <v-alert
                        type="error"
                        variant="tonal"
                        border="start"
                        prominent
                        icon="mdi-alert-circle-outline"
                    >
                        {{ error }}
                    </v-alert>
                </div>

                <!-- 3. Estado de Éxito (Datos cargados) -->
                <div v-else-if="documentData">
                    <v-row>
                        <!-- Columna de Previsualización -->
                        <v-col cols="12" >
                            <h3 class="mb-4">{{ documentData.title || 'Vista previa' }}</h3>

                            <!-- Previsualización de Imagen -->
                            <v-img
                                v-if="isImage"
                                :src="fileUrl"
                                :lazy-src="fileUrl"
                                aspect-ratio="1"
                                class="grey lighten-2"
                                max-height="500"
                            >
                                <template v-slot:placeholder>
                                    <v-row class="fill-height ma-0" align="center" justify="center">
                                        <v-progress-circular indeterminate color="grey-lighten-5"></v-progress-circular>
                                    </v-row>
                                </template>
                            </v-img>

                            <!-- Previsualización de PDF -->
                            <div v-else-if="isPdf" style="border: 1px solid #e0e0e0;">
                                <!-- El iframe para mostrar el PDF -->
                                <iframe :src="fileUrl" width="100%" height="500px" frameborder="0">
                                    <!-- Puedes dejar un texto simple para navegadores muy antiguos -->
                                    Tu navegador no soporta contenido incrustado.
                                </iframe>

                                <!-- El enlace de respaldo, fuera del iframe y siempre visible por si falla la carga -->
                                <p style="padding: 10px; text-align: center; margin: 0;">
                                    Si no puedes ver el documento,
                                    <a :href="fileUrl" target="_blank">haz clic aquí para abrir el PDF.</a>
                                </p>
                            </div>
                            <!-- Fallback para otros tipos -->
                            <v-sheet v-else color="grey-lighten-3" class="d-flex flex-column align-center justify-center pa-8 rounded" height="300">
                                <v-icon size="64" class="mb-4">mdi-file-question-outline</v-icon>
                                <p class="text-center">No hay una vista previa disponible para este tipo de archivo.</p>
                                <p class="text-body-2 text-grey-darken-1">{{ documentData.original_filename }}</p>
                            </v-sheet>
                        </v-col>

                        <!-- Columna de Detalles -->
                        <v-col cols="12" md="5" v-if="false">
                            <h3 class="mb-2">Información</h3>
                            <v-list lines="two" density="compact">
                                <v-list-item
                                    prepend-icon="mdi-text-box-outline"
                                    title="Nombre original"
                                    :subtitle="documentData.original_filename || '-'"
                                ></v-list-item>
                                <v-list-item
                                    prepend-icon="mdi-pound"
                                    title="Tamaño del archivo"
                                    :subtitle="formatSize(documentData.size) || '-'"
                                ></v-list-item>
                                <v-list-item
                                    prepend-icon="mdi-file-code-outline"
                                    title="Tipo MIME"
                                    :subtitle="documentData.mime_type || '-'"
                                ></v-list-item>
                                <v-list-item
                                    prepend-icon="mdi-calendar-upload"
                                    title="Fecha de subida"
                                    :subtitle="formatDate(documentData.created_at)"
                                ></v-list-item>
                            </v-list>
                        </v-col>
                    </v-row>
                </div>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions class="pa-3">
                <v-spacer></v-spacer>
                <v-btn color="grey" variant="flat" @click="closeDialog">
                    Cerrar
                </v-btn>
                <v-btn v-if="false"
                    color="primary"
                    variant="flat"
                    prepend-icon="mdi-download"
                    :disabled="loading || error || !fileUrl"
                    :href="fileUrl"
                    target="_blank"
                    download
                >
                    Descargar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
/* Estilos específicos si son necesarios */
.fill-height {
    height: 100%;
}
</style>
