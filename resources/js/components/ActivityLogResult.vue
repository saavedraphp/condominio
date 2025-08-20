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
                :disabled="!remarks.trim() || isLoading"
                :loading="isLoading"
            >
                Agregar Comentario
            </v-btn>
        </v-card-actions>
        <Snackbar ref="mySnackbar"/>
    </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";

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
const mySnackbar = ref(null);
// --- Component State ---
const remarks = ref('');
const isLoading = ref(false);
const remarksSubmitted = ref(props.data.remarks !== null);

// --- Computed Properties ---
const formattedTimestamp = computed(() => {
    if (!props.data.created_at) return 'N/A';
    return new Date(props.data.created_at).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
});

const submitRemarks = async () => {

    let  logId = props.data.log_id;

    if (!logId) return;

    isLoading.value = true;
    try {
        // Endpoint para actualizar solo las observaciones del registro ya creado
        await axios.patch(`/security/qr-handler/${logId}`, {
            remarks: remarks.value
        });
        mySnackbar.value.show('Observación añadido exitosamente.', 'success');
        setTimeout(() => {
            emit('reset');
        }, 2000);

    } catch (error) {
        mySnackbar.value.show('Ocurrio un error al registrar la Observación.', 'error');
        console.error("Error guardando observaciones:", error);
        // Opcional: mostrar un snackbar de error
    } finally {
        isLoading.value = true;
    }
};
</script>

<style scoped>
/* Add any custom styles here if needed */
</style>
