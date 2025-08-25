<script setup>
import {ref, computed, onMounted} from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    attributes: {
        type: Object,
        default: () => ({})
    },
    debt: {
        type: [Number, Object],
        default: null
    },
    status: {
        type: Boolean,
        default: false
    }
});

// Nombre de la empresa, podría venir de una config o ser estático
const companyName = ref('Condominio Islas de San Pedro'); // O el nombre que corresponda

const hasDebt = computed(() => props.debt > 0);
const isEnabled = computed(() => !hasDebt.value || props.user.has_payment_arrangement);
const debtColor = computed(() => isEnabled.value ? 'blue-darken-1' : 'orange-darken-2');

// 4. Mensaje dinámico para el usuario
const statusMessage = computed(() =>
    isEnabled.value
        ? 'Se encuentra al día con sus pagos.'
        : 'Presenta pagos pendientes.'
);
const userData = computed(() => ({
    name: props.user.name || 'Desconocido',
    debtFormatted: props.debt ? props.debt.toFixed(2) : null,
}));

// Estado de verificación del usuario (esto también vendría de tu API)
const userVerified = ref(false); // Por defecto no verificado hasta que la API responda
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
                        <div v-if="props.debt !== null">
                        <div class="info-usuario">
                            <!-- Primera línea: Nombre -->
                            <p class="nombre">
                                <strong>Nombre:</strong> {{ userData.name }}
                            </p>
                            <!-- Segunda línea: Dirección (Casa) -->
                            <p class="direccion">
                                {{ attributes?.houses?.address || 'Dirección no disponible' }}
                            </p>
                        </div>

                        <div v-if="attributes.vehicles && attributes.vehicles.length > 0">
                            <v-list lines="two" density="compact">
                                <v-list-subheader>VEHÍCULOS</v-list-subheader>
                                <v-list-item
                                    v-for="vehicle in attributes.vehicles"
                                    :key="vehicle.id"
                                    :title="`${vehicle.brand} ${vehicle.model}`"
                                    :subtitle="`Placa: ${vehicle.plate_number}`"
                                >
                                    <template v-slot:prepend>
                                        <v-icon color="primary">mdi-car</v-icon>
                                    </template>
                                </v-list-item>
                            </v-list>
                        </div>
                        <div v-else>
                            <!-- Es mejor poner el v-alert dentro de un v-card-text para el padding correcto -->
                            <v-card-text>
                                <v-alert
                                    type="info"
                                    variant="tonal"
                                    text="No hay vehículos registrados para esta casa."
                                ></v-alert>
                            </v-card-text>
                        </div>
                        </div>
                        <div v-else>
                            <!-- Primera línea: Nombre -->
                            <p class="nombre">
                                <strong>Nombre:</strong> {{ userData.name }}
                            </p>
                            <v-divider></v-divider>
                            <v-alert
                                     density="compact"
                                     type="info"
                                     title="Cuenta Pendiente de Activación"
                                     text="Tu cuenta ha sido creada, pero aún falta que un administrador asocie tu propiedad. Por favor, ponte en contacto con soporte para finalizar la configuración."
                            ></v-alert>
                        </div>

                    </v-card-text>
                    <v-divider class="my-3"></v-divider>
                    <!-- Estado de Verificación -->
                    <div class="text-center my-4" v-if="props.debt !== null">
                        <v-icon
                            size="80"
                            :color="isEnabled  ?  'success': 'red'"
                            class="mb-2"
                        >
                            {{ isEnabled  ? 'mdi mdi-check-circle-outline' : 'mdi mdi-close-circle' }}
                        </v-icon>
                        <p
                            class="text-h6 font-weight-medium"
                            :class="isEnabled ? 'text-success' : 'text-grey-darken-1'"
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
.info-usuario {
    text-align: center;
    margin-bottom: 1rem;
}

.nombre {
    margin-bottom: 0;
    font-size: 16px;
}

.direccion {
    margin-top: 4px;
    font-size: 28px;
    font-weight: bold;
}
</style>
