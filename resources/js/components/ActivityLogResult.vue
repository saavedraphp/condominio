<!-- src/components/results/ActivityLogResult.vue -->
<template>
    <v-card>
        <v-card-title class="d-flex align-center">
            <v-icon color="success" icon="mdi-check-circle" class="mr-2"></v-icon>
            Estado de Marcación
        </v-card-title>
        <v-card-subtitle>Actividad registrada exitosamente.</v-card-subtitle>

        <v-list density="compact">
            <v-list-item
                :title="data.user.name"
                subtitle="Personal"
                prepend-icon="mdi-account"
            ></v-list-item>

            <v-list-item
                :title="data.qr_code.title"
                subtitle="Ubicación"
                prepend-icon="mdi-map-marker"
            ></v-list-item>

            <v-list-item
                :title="formattedTimestamp"
                subtitle="Hora de la marcación"
                prepend-icon="mdi-clock-outline"
            ></v-list-item>
        </v-list>

        <v-divider></v-divider>

        <v-card-text>
            <!-- Show this form only if no remarks have been submitted yet -->
            <div v-if="!remarksSubmitted">
                <v-textarea
                    v-model="remarks"
                    label="Añadir comentarios opcionales o informar un incidente"
                    rows="3"
                    variant="outlined"
                    auto-grow
                ></v-textarea>
            </div>

            <!-- Show a confirmation message after submission -->
            <v-alert
                v-if="false"
                type="info"
                variant="tonal"
                text="Se han añadido observaciones.."
            ></v-alert>
        </v-card-text>

        <v-card-actions>
            <v-btn
                @click="$emit('reset')"
                variant="flat"
                color="grey"
            >
                Regresar al Scanner
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn
                v-if="!remarksSubmitted"
                @click="submitRemarks"
                color="primary"
                variant="flat"
                :disabled="!remarks.trim()"
                :loading="isLoading"
            >
                Add Comment
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';

// PROPS: The component receives the successful log data from its parent.
// This data should come directly from your Laravel API response.
const props = defineProps({
    data: {
        type: Object,
        required: true,
        // Example structure of the 'data' prop
        // {
        //   id: 1,
        //   user: { name: 'John Doe' },
        //   qr_code: { title: 'Lobby Entrance' },
        //   created_at: '2023-10-28T10:30:00.000000Z',
        //   remarks: null
        // }
    }
});

// EMITS: The component communicates actions back to the parent.
const emit = defineEmits(['reset', 'add-comment']);

// --- Component State ---
const remarks = ref('');
const isLoading = ref(false);
const remarksSubmitted = true;//ref(props.data.remarks !== null);

// --- Computed Properties ---
const formattedTimestamp = computed(() => {
    if (!props.data.created_at) return 'N/A';
    return new Date(props.data.created_at).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
});

// --- Methods ---
const submitRemarks = () => {
    if (!remarks.value.trim()) return;

    isLoading.value = true;

    // Emit an event to the parent component, sending the log ID and the remarks.
    // The parent component is responsible for making the API call.
    emit('add-comment', {
        logId: props.data.id,
        remarks: remarks.value,
    });

    // For immediate UI feedback, we can assume success.
    // The parent should handle API errors.
    // We stop the loading and show the confirmation message.
    setTimeout(() => {
        isLoading.value = false;
        remarksSubmitted.value = true;
    }, 500); // A small delay to feel more responsive
};
</script>

<style scoped>
/* Add any custom styles here if needed */
</style>
