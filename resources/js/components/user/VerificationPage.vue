<script setup>
import { ref, computed, onMounted } from 'vue';

// Nombre de la empresa, podría venir de una config o ser estático
const companyName = ref('LA ESQUINA DEL VOCAL'); // O el nombre que corresponda

// Datos del usuario (esto vendría de tu API)
const userData = ref({
    name: 'Cargando...',
    debt: 0.00,
});

// Estado de verificación del usuario (esto también vendría de tu API)
const userVerified = ref(false); // Por defecto no verificado hasta que la API responda

// Lógica para el color de la deuda
const debtColor = computed(() => {
    return userData.value.debt > 0 ? 'orange-darken-2' : 'blue-darken-1';
});

// Simulación de carga de datos desde la API después de escanear el QR
// En una aplicación real, aquí llamarías a tu API
onMounted(() => {
    // Simula una llamada a la API
    setTimeout(() => {
        // Caso 1: Usuario con deuda y verificado
        userData.value = {
            name: 'Luis Saavedra',
            debt: 100.00,
        };
        userVerified.value = true;

        // Caso 2: Usuario sin deuda y verificado
        // userData.value = {
        //   name: 'Ana Torres',
        //   debt: 0.00,
        // };
        // userVerified.value = true;

        // Caso 3: Usuario no verificado (quizás el QR no es válido o no se encuentra)
        // userData.value = {
        //   name: 'Desconocido',
        //   debt: 0.00,
        // };
        // userVerified.value = false;

    }, 1500); // Simula un retraso de red
});

</script>
<template>
    <v-app style="background-color: #f0f2f5;">
        <v-main>
            <v-container class="fill-height d-flex align-center justify-center pa-4">
                <v-card class="mx-auto pa-4" max-width="450" elevation="2">
                    <!-- Nombre de la Empresa (similar a tu referencia) -->
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
                            :color="userVerified ? 'success' : 'grey-lighten-1'"
                            class="mb-2"
                        >
                            {{ userVerified ? 'mdi-check-decagram' : 'mdi-shield-alert-outline' }}
                        </v-icon>
                        <p
                            class="text-h6 font-weight-medium"
                            :class="userVerified ? 'text-success' : 'text-grey-darken-1'"
                        >
                            {{ userVerified ? 'USUARIO VERIFICADO' : 'USUARIO NO VERIFICADO' }}
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
