<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

// --- Props y Emits ---
const props = defineProps({
    modelValue: Boolean,
    petitionId: {
        type: [Number, String],
        default: null,
    },
    apiBaseUrl: {
        type: String,
        required: true
    },
    webUserId: {
        type: Number,
        required: true
    }
});
const emit = defineEmits(['update:modelValue', 'close', 'updated']);

// --- Estado ---
const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
const petition = ref(null);
const loading = ref(false);
const replyLoading = ref(false);
const replyError = ref('');
const replyFormRef = ref(null);
const newReply = ref({ body: '' });
// ID del usuario inquilino actual (esto deberías obtenerlo de tu store de autenticación o similar)
const currentWebUserId = ref(obtenerIdUsuarioActual()); // IMPLEMENTA ESTA FUNCIÓN

// Asume que tienes una función para obtener el ID del usuario logueado
function obtenerIdUsuarioActual() {
    // Ejemplo: leer de localStorage, Vuex, Pinia, etc.
    // return JSON.parse(localStorage.getItem('webUser'))?.id;
    // ¡¡¡IMPORTANTE: IMPLEMENTA TU LÓGICA REAL AQUÍ!!!
    return props.webUserId;
}


// --- Reglas ---
const rules = {
    required: value => !!value || 'Este campo es requerido.',
    maxLength: (max) => (value) => (value && value.length <= max) || `Máximo ${max} caracteres.`,
};

// --- Métodos ---
const fetchPetitionDetails = async () => {
    if (!props.petitionId) return;
    loading.value = true;
    petition.value = null; // Resetear antes de cargar
    try {
        const response = await axios.get(`${props.apiBaseUrl}/${props.petitionId}`);
        petition.value = response.data;
    } catch (error) {
        console.error("Error fetching petition details:", error);
        // Manejar error (snackbar, etc.)
    } finally {
        loading.value = false;
    }
};

const submitReply = async () => {
    replyError.value = '';
    const { valid } = await replyFormRef.value.validate();
    if (!valid || !petition.value) return;

    replyLoading.value = true;
    try {
        const response = await axios.post(`${props.apiBaseUrl}/${petition.value.id}/replies`, newReply.value);
        // Añadir la nueva respuesta a la lista visualmente
        if (!petition.value.replies) petition.value.replies = [];
        petition.value.replies.push(response.data); // Asume que la API devuelve la respuesta creada
        newReply.value.body = ''; // Limpiar el campo
        replyFormRef.value.resetValidation(); // Resetear validación
        // Opcional: Recargar toda la petición para asegurar consistencia del estado
        // await fetchPetitionDetails();
        emit('updated'); // Notificar al componente padre que algo cambió
    } catch (error) {
        console.error("Error submitting reply:", error);
        if (error.response && error.response.data && error.response.data.message) {
            replyError.value = error.response.data.message;
        } else {
            replyError.value = 'Error al enviar la respuesta.';
        }
    } finally {
        replyLoading.value = false;
    }
};

const isMyReply = (reply) => {
    // Comprueba si el tipo es WebUser y el ID coincide con el usuario actual
    return reply.repliable_type?.includes('WebUser') && reply.repliable_id === currentWebUserId.value;
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Abierto': return 'blue';
        case 'En Progreso': return 'orange';
        case 'Cerrado': return 'grey';
        default: return 'grey';
    }
};


const closeDialog = () => {
    emit('close');
    // Resetear estado al cerrar si es necesario
    // petition.value = null;
    // newReply.value.body = '';
    // replyError.value = '';
};

// --- Watchers ---
watch(() => props.petitionId, (newId) => {
    if (newId && props.modelValue) {
        fetchPetitionDetails();
    } else {
        petition.value = null; // Limpiar si no hay ID o el dialog está cerrado
    }
});

watch(() => props.modelValue, (newValue) => {
    if(newValue && props.petitionId) {
        fetchPetitionDetails();
    } else if (!newValue) {
        // Opcional: Limpiar al cerrar
        petition.value = null;
        newReply.value.body = '';
        replyError.value = '';
        if (replyFormRef.value) replyFormRef.value.resetValidation();
    }
});

</script>
<template>
    <v-dialog v-model="dialog"  :scrim="false" transition="dialog-bottom-transition">
        <v-card :loading="loading">
            <v-toolbar dark color="primary">
                <v-btn icon dark @click="closeDialog">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
                <v-toolbar-title>Detalle de Petición #{{ petition?.id }}</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-chip :color="getStatusColor(petition?.status)" dark class="mr-4" v-if="petition">
                    {{ petition.status }}
                </v-chip>
            </v-toolbar>

            <v-card-text v-if="petition" class="pa-5">
                <!-- Detalles de la petición -->
                <v-row>
                    <v-col cols="12" md="6">
                        <p><strong>Asunto:</strong> {{ petition.subject }}</p>
                        <p><strong>Tipo:</strong> {{ petition.type }}</p>
                        <p><strong>Fecha Creación:</strong> {{ formatDate(petition.created_at) }}</p>
                    </v-col>
                </v-row>
                <v-divider class="my-4"></v-divider>

                <!-- Mensaje Inicial -->
                <div class="mb-5 message-bubble tenant-message">
                    <p><strong>Tú</strong> ({{ formatDate(petition.created_at) }}):</p>
                    <p style="white-space: pre-wrap;">{{ petition.description }}</p>
                </div>


                <!-- Historial de Respuestas -->
                <v-timeline side="end" align="start" density="compact" v-if="petition.replies && petition.replies.length > 0">
                    <v-timeline-item
                        v-for="reply in petition.replies"
                        :key="reply.id"
                        :dot-color="isMyReply(reply) ? 'blue' : 'deep-purple-lighten-1'"
                        size="small"
                    >
                        <div class="d-flex justify-space-between align-center">
                            <strong>{{ reply.repliable?.name || (isMyReply(reply) ? 'Tú' : 'Administrador') }}</strong>
                            <span class="text-caption">{{ formatDate(reply.created_at) }}</span>
                        </div>
                        <div :class="['message-bubble', isMyReply(reply) ? 'tenant-message' : 'admin-message']">
                            <p style="white-space: pre-wrap;">{{ reply.body }}</p>
                        </div>
                    </v-timeline-item>
                </v-timeline>
                <v-alert v-else type="info" variant="tonal" class="mt-4">
                    Aún no hay respuestas para esta petición.
                </v-alert>


                <!-- Formulario de Respuesta (si no está cerrada) -->
                <v-divider class="my-4" v-if="petition.status !== 'Cerrado'"></v-divider>
                <v-form ref="replyFormRef" @submit.prevent="submitReply" v-if="petition.status !== 'Cerrado'">
                    <h3 class="text-h6 mb-3">Añadir Respuesta</h3>
                    <v-textarea
                        v-model="newReply.body"
                        label="Tu respuesta"
                        rows="4"
                        auto-grow
                        :rules="[rules.required, rules.maxLength(5000)]"
                        counter="5000"
                        required
                        variant="outlined"
                    ></v-textarea>
                    <v-alert v-if="replyError" type="error" dense class="mt-2 mb-3">
                        {{ replyError }}
                    </v-alert>
                    <v-btn
                        color="primary"
                        type="submit"
                        :loading="replyLoading"
                        class="mt-2"
                    >Enviar Respuesta</v-btn>
                </v-form>
                <v-alert v-else type="warning" variant="tonal" class="mt-4">
                    Esta petición ha sido cerrada y no se pueden añadir más respuestas.
                </v-alert>

            </v-card-text>

            <v-card-text v-else-if="loading" class="text-center pa-10">
                <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                <p class="mt-4">Cargando detalles...</p>
            </v-card-text>
            <v-card-text v-else class="text-center pa-10">
                <v-alert type="error">No se pudo cargar la información de la petición.</v-alert>
            </v-card-text>

        </v-card>
    </v-dialog>
</template>
<style scoped>
.message-bubble {
    padding: 10px 15px;
    border-radius: 15px;
    margin-bottom: 10px;
    max-width: 80%;
    word-wrap: break-word;
}

.tenant-message {
    background-color: #e3f2fd; /* Light blue */
    margin-left: auto; /* Align tenant messages to the right */
    border-bottom-right-radius: 0;
}

.admin-message {
    background-color: #ede7f6; /* Light purple */
    margin-right: auto; /* Align admin messages to the left */
    border-bottom-left-radius: 0;
}

/* Ajustes para timeline si es necesario */
.v-timeline-item .v-timeline-item__body {
    width: 100%; /* Asegurar que el cuerpo ocupe espacio */
}
.v-timeline--vertical.v-timeline {
    grid-row-gap: 15px; /* Espacio entre items */
}
</style>
