<template>
    <v-dialog
        :model-value="show"
        @update:model-value="emitCancel"
        max-width="500px"
        persistent
    >
    <v-card>
        <v-card-title class="text-h5">{{ title }}</v-card-title>
        <v-card-text>
            <!-- Mensaje personalizable -->
            {{ message }}
            <div v-if="itemName" class="font-weight-bold mt-2">{{ itemName }}</div>
            <div>Esta acción no se puede deshacer.</div>
        </v-card-text>
        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn
                color="blue darken-1"
                text
                @click="emitCancel"
                :disabled="loading"
            >
                Cancelar
            </v-btn>
            <v-btn
                color="red darken-1"
                text
                @click="emitConfirm"
                :loading="loading"
                :disabled="loading"
            >
                Eliminar
            </v-btn>
        </v-card-actions>
    </v-card>
    </v-dialog>
</template>

<script setup>
// --- Props ---
// Propiedades que el componente padre pasará al modal
const props = defineProps({
    // Controla la visibilidad del modal (usa v-model en el padre)
    show: {
        type: Boolean,
        required: true,
    },
    // Título del modal
    title: {
        type: String,
        default: 'Confirmar Eliminación',
    },
    // Mensaje principal del modal
    message: {
        type: String,
        default: '¿Estás seguro de que deseas eliminar este elemento?',
    },
    // (Opcional) Nombre o identificador del ítem a mostrar
    itemName: {
        type: String,
        default: '',
    },
    // (Opcional) Para mostrar un estado de carga en el botón de eliminar
    loading: {
        type: Boolean,
        default: false,
    }
});

// --- Emits ---
// Eventos que el modal enviará al componente padre
const emit = defineEmits([
    'update:show', // Necesario para que v-model funcione en `show`
    'confirm',     // Se emite cuando se hace clic en "Eliminar"
    'cancel',      // Se emite cuando se hace clic en "Cancelar" (o se cierra)
]);

// --- Métodos (Handlers de eventos internos) ---
const emitConfirm = () => {
    // Notifica al padre que se confirmó la acción
    emit('confirm');
    // ¡Importante! El modal NO se cierra aquí. El padre decidirá cuándo cerrarlo
    // después de que la operación de eliminación termine (éxito o fracaso).
};

const emitCancel = () => {
    // Notifica al padre que se canceló y pide cerrar el modal
    emit('update:show', false); // Actualiza el v-model en el padre
    emit('cancel'); // Emite evento de cancelación por si el padre necesita hacer algo más
};
</script>

<style scoped>
/* Estilos si son necesarios */
</style>
