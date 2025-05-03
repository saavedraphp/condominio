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
const statusUpdating = ref(false);
const replyError = ref('');
const statusError = ref('');
const replyFormRef = ref(null);
const newReply = ref({ body: '' });
const currentStatus = ref(null); // Para el v-select del estado
const statusOptions = ['Abierto', 'En Progreso', 'Cerrado'];


// --- Reglas ---
const rules = {
    required: value => !!value || 'Este campo es requerido.',
    maxLength: (max) => (value) => (value && value.length <= max) || `Máximo ${max} caracteres.`,
};

// --- Métodos ---
const fetchPetitionDetails = async () => {
    if (!props.petitionId) return;
    loading.value = true;
    petition.value = null; // Resetear
    statusError.value = ''; // Resetear error de estado
    try {
        const response = await axios.get(`${props.apiBaseUrl}/${props.petitionId}`);
        petition.value = response.data;
        currentStatus.value = petition.value?.status; // Sincronizar v-select
    } catch (error) {
        console.error("Error fetching petition details:", error);
        // Manejar error
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (newStatus) => {
    if (!petition.value || statusUpdating.value || newStatus === petition.value.status) return;

    statusUpdating.value = true;
    statusError.value = '';
    try {
        await axios.put(`${props.apiBaseUrl}/${petition.value.id}/status`, { status: newStatus });
        // Actualizar visualmente
        if (petition.value) {
            petition.value.status = newStatus;
        }
        currentStatus.value = newStatus; // Asegurar sincronización del select
        emit('updated'); // Notificar al listado que el estado cambió
        // Opcional: Mostrar un snackbar de éxito
    } catch (error) {
        console.error("Error updating status:", error);
        statusError.value = error.response?.data?.message || 'Error al actualizar el estado.';
        // Revertir el v-select al valor anterior si falla
        currentStatus.value = petition.value?.status;
        // Opcional: Mostrar snackbar de error
    } finally {
        statusUpdating.value = false;
    }
};

const submitReply = async () => {
    replyError.value = '';
    const { valid } = await replyFormRef.value.validate();
    if (!valid || !petition.value) return;

    replyLoading.value = true;
    try {
        const response = await axios.post(`${props.apiBaseUrl}/${petition.value.id}/replies`, newReply.value);
        if (!petition.value.replies) petition.value.replies = [];
        petition.value.replies.push(response.data);
        newReply.value.body = '';
        replyFormRef.value.resetValidation();

        // Si el admin responde y estaba 'Abierto', el backend lo pasa a 'En Progreso'
        // Actualizamos visualmente si es necesario
        if(petition.value.status === 'Abierto') {
            petition.value.status = 'En Progreso';
            currentStatus.value = 'En Progreso';
        }

        emit('updated'); // Notificar para refrescar fechas en listado
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

const isTenantReply = (reply) => {
    // Comprueba si el tipo es WebUser
    return reply.repliable_type?.includes('WebUser');
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
};

// --- Watchers ---
watch(() => props.petitionId, (newId) => {
    if (newId && props.modelValue) {
        fetchPetitionDetails();
    } else {
        petition.value = null;
    }
});

watch(() => props.modelValue, (newValue) => {
    if(newValue && props.petitionId) {
        fetchPetitionDetails();
    } else if (!newValue) {
        // Limpiar al cerrar
        petition.value = null;
        newReply.value.body = '';
        replyError.value = '';
        statusError.value = '';
        if (replyFormRef.value) replyFormRef.value.resetValidation();
    }
});

</script>
<template>
    <v-dialog v-model="dialog"   :scrim="false" transition="dialog-bottom-transition">
        <v-card :loading="loading">
            <v-toolbar dark color="indigo"> <!-- Color diferente para admin -->
                <v-btn icon dark @click="closeDialog">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
                <v-toolbar-title>Petición #{{ petition?.id }} - {{ petition?.webUser?.name || 'Inquilino' }}</v-toolbar-title>
                <v-spacer></v-spacer>
                <!-- Selector de Estado -->
                <v-select
                    v-if="petition"
                    v-model="currentStatus"
                    :items="statusOptions"
                    label="Estado"
                    density="compact"
                    hide-details
                    variant="solo-filled"
                    bg-color="indigo-lighten-1"
                    class="mr-4"
                    style="max-width: 200px;"
                    :disabled="statusUpdating"
                    @update:modelValue="updateStatus"
                >
                    <template v-slot:prepend-inner>
                        <v-progress-circular indeterminate size="20" v-if="statusUpdating"></v-progress-circular>
                    </template>
                </v-select>
                <v-chip :color="getStatusColor(petition?.status)" dark class="mr-4" v-else-if="petition">
                    {{ petition.status }}
                </v-chip>
            </v-toolbar>

            <v-card-text v-if="petition" class="pa-5">
                <!-- Detalles (similar al inquilino, puedes añadir más info si quieres) -->
                <v-row>
                    <v-col cols="12" md="6">
                        <p><strong>Inquilino:</strong> {{ petition.webUser?.name || 'N/A' }} ({{ petition.webUser?.email || 'N/A' }})</p>
                        <p><strong>Asunto:</strong> {{ petition.subject }}</p>
                        <p><strong>Tipo:</strong> {{ petition.type }}</p>
                        <p><strong>Fecha Creación:</strong> {{ formatDate(petition.created_at) }}</p>
                    </v-col>
                    <v-col cols="12" md="6">
                        <!-- Podrías añadir más detalles relevantes para el admin -->
                    </v-col>
                </v-row>
                <v-divider class="my-4"></v-divider>

                <!-- Mensaje Inicial del Inquilino -->
                <div class="mb-5 message-bubble tenant-message">
                    <p><strong>{{ petition.webUser?.name || 'Inquilino' }}</strong> ({{ formatDate(petition.created_at) }}):</p>
                    <p style="white-space: pre-wrap;">{{ petition.description }}</p>
                </div>

                <!-- Historial de Respuestas -->
                <v-timeline side="end" align="start" density="compact" v-if="petition.replies && petition.replies.length > 0">
                    <v-timeline-item
                        v-for="reply in petition.replies"
                        :key="reply.id"
                        :dot-color="isTenantReply(reply) ? 'blue' : 'deep-purple-lighten-1'"
                        size="small"
                    >
                        <div class="d-flex justify-space-between align-center">
                            <strong>{{ reply.repliable?.name || (isTenantReply(reply) ? 'Inquilino' : 'Tú (Admin)') }}</strong>
                            <span class="text-caption">{{ formatDate(reply.created_at) }}</span>
                        </div>
                        <div :class="['message-bubble', isTenantReply(reply) ? 'tenant-message' : 'admin-message']">
                            <p style="white-space: pre-wrap;">{{ reply.body }}</p>
                        </div>
                    </v-timeline-item>
                </v-timeline>
                <v-alert v-else type="info" variant="tonal" class="mt-4">
                    Aún no hay respuestas para esta petición.
                </v-alert>

                <!-- Formulario de Respuesta del Admin (siempre visible, excepto si está cargando) -->
                <v-divider class="my-4"></v-divider>
                <v-form ref="replyFormRef" @submit.prevent="submitReply">
                    <h3 class="text-h6 mb-3">Responder como Administrador</h3>
                    <v-textarea
                        v-model="newReply.body"
                        label="Tu respuesta"
                        rows="4"
                        auto-grow
                        :rules="[rules.required, rules.maxLength(200)]"
                        counter="200"
                        required
                        variant="outlined"
                    ></v-textarea>
                    <v-alert v-if="replyError" type="error" dense class="mt-2 mb-3">
                        {{ replyError }}
                    </v-alert>
                    <v-btn
                        color="indigo"
                        type="submit"
                        :loading="replyLoading"
                        class="mt-2"
                    >Enviar Respuesta</v-btn>
                </v-form>

            </v-card-text>

            <v-card-text v-else-if="loading || statusUpdating" class="text-center pa-10">
                <v-progress-circular indeterminate color="indigo" size="64"></v-progress-circular>
                <p class="mt-4">{{ statusUpdating ? 'Actualizando estado...' : 'Cargando detalles...' }}</p>
            </v-card-text>
            <v-card-text v-else class="text-center pa-10">
                <v-alert type="error">No se pudo cargar la información de la petición.</v-alert>
            </v-card-text>

        </v-card>
    </v-dialog>
</template>



<style scoped>
/* Reutilizar los estilos de message-bubble de TenantPetitionViewDialog */
.message-bubble {
    padding: 10px 15px;
    border-radius: 15px;
    margin-bottom: 10px;
    max-width: 80%;
    word-wrap: break-word;
}
.tenant-message {
    background-color: #e3f2fd; /* Light blue */
    margin-right: auto; /* Align tenant messages to the left */
    border-bottom-left-radius: 0;
}
.admin-message {
    background-color: #ede7f6; /* Light purple */
    margin-left: auto; /* Align admin messages to the right */
    border-bottom-right-radius: 0;
}
.v-timeline-item .v-timeline-item__body {
    width: 100%;
}
.v-timeline--vertical.v-timeline {
    grid-row-gap: 15px;
}
/* Estilo para el select de estado en la toolbar */
.v-toolbar .v-select {
    color: white !important;
}
.v-toolbar .v-select .v-field__input,
.v-toolbar .v-select .v-select__selection-text {
    color: white !important;
    padding-top: 0; /* Ajustar alineación vertical si es necesario */
    padding-bottom: 0;
}
.v-toolbar .v-select .v-field__outline::before,
.v-toolbar .v-select .v-field__outline::after {
    border-color: rgba(255, 255, 255, 0.7) !important; /* Color del borde */
}
.v-toolbar .v-select .v-label.v-field-label {
    color: rgba(255, 255, 255, 0.7) !important; /* Color del label flotante */
}

</style>
