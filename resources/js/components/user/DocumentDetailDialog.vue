<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

// --- Props & Emits ---
const props = defineProps({
    modelValue: Boolean, // Para controlar la visibilidad del diálogo (v-model)
    documentId: {
        type: [Number, String, null],
        default: null,
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
const error = ref(false);
const errorMessage = ref('');

// --- Computed ---
const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});


// --- Methods ---
const fetchDocumentDetails = async (id) => {
    if (!id) return;
    loading.value = true;
    error.value = false;
    errorMessage.value = '';
    documentData.value = null;
    try {
        const response = await axios.get(`${props.apiBaseUrl}/${id}`);
        documentData.value = response.data;
    } catch (err) {
        console.error(`Error fetching document ${id}:`, err);
        error.value = true;
        errorMessage.value = 'No se pudieron cargar los detalles del documento.';
        if (err.response && err.response.status === 404) {
            errorMessage.value = 'Documento no encontrado o no tienes permiso para verlo.';
        } else if (err.response && err.response.status === 401) {
            errorMessage.value = 'No estás autorizado.';
        }
    } finally {
        loading.value = false;
    }
};

const closeDialog = () => {
    emit('close'); // Emite el evento close también por si se usa externamente
    dialogVisible.value = false; // Cierra usando el computed property
};

const trackDownload = () => {
    // Opcional: Puedes añadir lógica aquí si quieres registrar la descarga
    // console.log(`Iniciando descarga para: ${documentData.value?.original_filename}`);
    // No necesitas cerrar el diálogo aquí, el usuario puede querer mantenerlo abierto.
}

// Función para formatear fecha
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

// --- Watchers ---
// Observa cambios en documentId y si el diálogo se hace visible
watch(() => [props.documentId, props.modelValue], ([newId, isVisible]) => {
    if (isVisible && newId) {
        fetchDocumentDetails(newId);
    } else if (!isVisible) {
        // Limpia el estado cuando el diálogo se cierra
        documentData.value = null;
        loading.value = false;
        error.value = false;
        errorMessage.value = '';
    }
});

</script>
<template>
    <v-dialog
        :model-value="modelValue"
        @update:model-value="$emit('update:modelValue', $event)"
        max-width="600px"
        persistent
        scrollable
    >
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center">
                <span class="headline">Detalles del Documento</span>
                <v-spacer></v-spacer>
                <v-btn icon="mdi-close" variant="text" @click="closeDialog"></v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <v-card-text v-if="!error && documentData">
                <v-list density="compact">
                    <v-list-item>
                        <v-list-item-title class="font-weight-bold">Título:</v-list-item-title>
                        <v-list-item-subtitle>{{ documentData.title }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item v-if="documentData.description">
                        <v-list-item-title class="font-weight-bold">Descripción:</v-list-item-title>
                        <v-list-item-subtitle style="white-space: pre-wrap;">{{ documentData.description }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item>
                        <v-list-item-title class="font-weight-bold">Tipo:</v-list-item-title>
                        <v-list-item-subtitle>{{ documentData.type || 'No especificado' }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item>
                        <v-list-item-title class="font-weight-bold">Nombre Archivo:</v-list-item-title>
                        <v-list-item-subtitle>{{ documentData.original_filename }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item>
                        <v-list-item-title class="font-weight-bold">Tamaño:</v-list-item-title>
                        <v-list-item-subtitle>{{ documentData.readable_size }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item>
                        <v-list-item-title class="font-weight-bold">Fecha Publicación:</v-list-item-title>
                        <v-list-item-subtitle>{{ formatDate(documentData.created_at) }}</v-list-item-subtitle>
                    </v-list-item>
                </v-list>

            </v-card-text>

            <!-- Mensaje de Error -->
            <v-card-text v-if="error">
                <v-alert type="error" dense outlined>
                    {{ errorMessage }}
                </v-alert>
            </v-card-text>

            <!-- Mensaje mientras carga -->
            <v-card-text v-if="loading && !error">
                <div class="text-center">
                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                    <p>Cargando detalles...</p>
                </div>
            </v-card-text>


            <v-divider></v-divider>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="grey-darken-1" variant="text" @click="closeDialog">
                    Cerrar
                </v-btn>
                <v-btn
                    color="primary"
                    variant="flat"
                    prepend-icon="mdi-download"
                    :disabled="loading || error || !documentData?.file_path_url"
                    :href="documentData?.file_path_url"
                    target="_blank"
                    @click="trackDownload"
                >
                    Descargar Archivo
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
<style scoped>
/* Estilos si son necesarios */
.v-list-item-title {
    margin-bottom: 4px; /* Espacio entre título y subtítulo */
}
.v-list-item-subtitle {
    opacity: 0.8;
}
</style>
