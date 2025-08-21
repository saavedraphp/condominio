<script setup>
import { computed } from 'vue';
import {formatDateCustom, formatDateForDisplay, formatDateTime} from "../../utils/functions.js";

// Props:
// modelValue: para controlar la visibilidad con v-model
// log: el objeto completo del AccessLog con sus relaciones cargadas
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    log: {
        type: Object,
        default: null,
    },
});

// Emits: para que v-model funcione correctamente
const emit = defineEmits(['update:modelValue']);

// Función para cerrar el diálogo
const closeDialog = () => {
    emit('update:modelValue', false);
};

// Lógica para el estado (color y texto)
const statusInfo = computed(() => {
    const status = props.log?.status?.toLowerCase();
    switch (status) {
        case 'success':
        case 'exitoso':
            return { text: 'Acceso Permitido', color: 'success' };
        case 'failed_expired':
        case 'caducado':
            return { text: 'Pase Caducado', color: 'warning' };
        case 'failed_not_found':
        case 'invalido':
            return { text: 'Pase Inválido', color: 'error' };
        default:
            return { text: status || 'Desconocido', color: 'grey' };
    }
});

// Lógica para el header del diálogo
const headerClass = computed(() => statusInfo.value.color);
const headerIcon = computed(() => {
    const status = props.log?.status?.toLowerCase();
    switch (status) {
        case 'success':
        case 'exitoso':
            return 'mdi-check-circle-outline';
        case 'expired':
        case 'caducado':
            return 'mdi-alert-circle-outline';
        case 'invalid':
        case 'invalido':
            return 'mdi-close-circle-outline';
        default:
            return 'mdi-information-outline';
    }
});
const headerTitle = computed(() => `Detalle de Registro - ${statusInfo.value.text}`);

// Función para formatear fechas (puedes usar una librería como date-fns o moment.js)

</script>
<template>
    <v-dialog :model-value="modelValue" max-width="550px" @update:model-value="closeDialog">
        <v-card>
            <!-- Título Dinámico -->
            <v-card-title class="d-flex align-center" :class="headerClass">
                <v-icon :icon="headerIcon" start></v-icon>
                <span>{{ headerTitle }}</span>
            </v-card-title>

            <v-card-text class="pt-4">
                <div v-if="log">
                    <!-- Sección: Datos del Registro de Acceso -->
                    <p class="text-overline">Detalles del Registro</p>
                    <v-list lines="one" density="compact">
                        <v-list-item title="Vigilante" :subtitle="log.security?.name || 'No disponible'"></v-list-item>
                        <v-list-item title="Fecha y Hora del Escaneo">
                            <v-list-item-subtitle>{{ formatDateTime(log.created_at) }}</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item title="Estado del Registro">
                            <template v-slot:subtitle>
                                <v-chip :color="statusInfo.color" size="small" variant="tonal">
                                    {{ statusInfo.text }}
                                </v-chip>
                            </template>
                        </v-list-item>
                        <v-list-item v-if="log.remarks" title="Observaciones del Vigilante" :subtitle="log.remarks"></v-list-item>
                    </v-list>

                    <v-divider class="my-3"></v-divider>

                    <!-- Sección: Datos del Pase de Visita -->
                    <p class="text-overline">Información del Pase</p>
                    <v-list lines="one" density="compact">
                        <v-list-item title="Propiedad" :subtitle="log.property?.address || 'No disponible'"></v-list-item>
                        <v-list-item title="Generado por" :subtitle="log.creator?.name || 'No disponible'"></v-list-item>
                        <v-list-item title="Título del Pase" :subtitle="log.pass.title || 'Sin título'"></v-list-item>
                        <v-list-item title="Código del Pase" :subtitle="log.code_entered || 'Sin Código'"></v-list-item>
                        <v-list-item title="Vigencia del Pase">
                            <v-list-item-subtitle>
                                {{ formatDateForDisplay(log.pass?.starts_at) }} - {{ formatDateForDisplay(log.pass?.expires_at) }}
                            </v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </div>

                <!-- Mensaje si no hay datos -->
                <div v-else class="text-center pa-8">
                    <p>No hay información para mostrar.</p>
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn variant="flat" color="grey" @click="closeDialog">
                    Cerrar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
<style scoped>
/* Clases de color para el header. Asegúrate que Vuetify las reconozca. */
.success { background-color: #4CAF50; color: white; }
.warning { background-color: #FB8C00; color: white; }
.error { background-color: #F44336; color: white; }
.grey { background-color: #607D8B; color: white; }

.text-overline {
    color: rgba(0, 0, 0, 0.6);
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.1666666667em;
    line-height: 1rem;
    text-transform: uppercase;
}
</style>
