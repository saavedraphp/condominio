<script setup>
import {ref, computed, onMounted} from 'vue';

const props = defineProps({
    status: {
        type: Boolean,
        default: false
    },
    user: {
        type: Object,
        required: true
    },
    debt: {
        type: Number,
        default: 0.00
    }
});

// Nombre de la empresa, podría venir de una config o ser estático
const companyName = ref('LA ESQUINA DEL VOCAL'); // O el nombre que corresponda

// Datos del usuario (esto vendría de tu API)
const userData = ref({
    name: 'Cargando...',
    debt: 0.00,
});
const statusMessage = ref('Cargando...');
const hasDebt = ref(false);

// Estado de verificación del usuario (esto también vendría de tu API)
const userVerified = ref(false); // Por defecto no verificado hasta que la API responda

// Lógica para el color de la deuda
const debtColor = computed(() => {
    return userData.value.debt > 0 ? 'orange-darken-2' : 'blue-darken-1';
});

// Simulación de carga de datos desde la API después de escanear el QR
// En una aplicación real, aquí llamarías a tu API
onMounted(() => {
    userData.value = {
        name: props.user.name || 'Desconocido',
        debt: props.debt || 0.00,
    };
    userVerified.value = props.status;
    statusMessage.value = props.debt > 0
        ? 'Presenta pagos pendientes.'
        : 'Se encuentra al día con sus pagos.';
    hasDebt.value = props.debt > 0;
});

</script>
<template>
    <v-app style="background-color: #f0f2f5;">
        <v-main>
            <v-container class="fill-height d-flex align-center justify-center pa-4">
                <v-card class="mx-auto pa-4" max-width="450" elevation="2">
                    <v-card-title class="text-h5 text-center font-weight-bold mb-2 primary--text">
                        {{ companyName }}
                    </v-card-title>

                    <v-divider class="my-3"></v-divider>

                    <!-- Datos del Usuario -->
                    <v-card-text>
                        <div class="mb-3">
                            <p class="text-subtitle-1 mb-1">
                                <strong>Nombre:</strong> {{ userData.name }}
                            </p>
                        </div>

                        <div class="mb-4 d-flex align-center">
                            <p class="text-subtitle-1 mb-0 mr-2">
                                <strong>Adeuda:</strong>
                            </p>
                            <v-chip
                                :color="debtColor"
                                label
                                text-color="white"
                                class="font-weight-bold"
                            >
                                S/ {{ userData.debt.toFixed(2) }}
                            </v-chip>
                        </div>
                    </v-card-text>

                    <v-divider class="my-3"></v-divider>

                    <!-- Estado de Verificación -->
                    <div class="text-center my-4">
                        <v-icon
                            size="80"
                            :color="hasDebt  ? 'red' : 'success'"
                            class="mb-2"
                        >
                            {{ hasDebt  ? 'mdi-shield-alert-outline' : 'mdi-check-decagram' }}
                        </v-icon>
                        <p
                            class="text-h6 font-weight-medium"
                            :class="hasDebt ? 'text-grey-darken-1' : 'text-success'"
                        >
                            {{ statusMessage }}
                        </p>
                    </div>

                </v-card>
            </v-container>
        </v-main>
    </v-app>
</template>
<style scoped>
/* Puedes añadir estilos personalizados si es necesario */
.primary--text { /* Si no tienes definido 'primary' en tu tema Vuetify */
    color: #1976D2; /* Un azul por defecto, ajústalo a tu tema */
}

.v-card {
    border-radius: 8px;
}

.v-chip {
    font-size: 1rem; /* Ajusta si es necesario */
}
</style>
