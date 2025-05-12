<script setup>
import { ref, reactive, onMounted } from 'vue';
// IMPORTANTE: Registrar ApexCharts globalmente (main.js) o localmente aquí
import VueApexCharts from 'vue3-apexcharts';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {formatDate, formatDateTime} from "../../utils/functions.js";

const props = defineProps({
    userId: String
});
const loading = ref(true)
const isLoadingAds = ref(false)
const error = ref(null)
const user = ref(null)
const mySnackbar = ref(null);

const deuda = ref(985.00);
const ads = ref([]);

// --- Configuración de Gráficos (Ejemplos) ---

// Gráfico Consumo Agua
const chartOptionsConsumo = reactive({
    chart: {
        id: 'consumo-agua',
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    xaxis: {
        categories: ['Oct', 'Nov', 'Dic', 'Ene', 'Feb', 'Mar'], // Últimos 6 meses
        labels: { style: { colors: '#888' } }
    },
    yaxis: {
        labels: { style: { colors: '#888' } }
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
            stops: [0, 90, 100]
        }
    },
    tooltip: {
        theme: 'dark', // O 'light' si prefieres
        x: { format: 'MMM' },
        y: { formatter: (val) => `${val} m³` }
    },
    colors: ['#008FFB'], // Azul
});
const chartDataConsumo = reactive({
    series: [], // Se llenará en onMounted
});

// Gráfico Historial Pagos (Donut)
const chartOptionsPagos = reactive({
    chart: {
        id: 'historial-pagos',
        toolbar: { show: false },
    },
    labels: ['Pagado a Tiempo', 'Pagado con Retraso', 'Pendiente'],
    colors: ['#00E396', '#FEB019', '#FF4560'], // Verde, Naranja, Rojo
    legend: { position: 'bottom' },
    tooltip: {
        theme: 'dark',
        y: { formatter: (val) => `${val} pagos` }
    },
    dataLabels: {
        formatter: (val, opts) => opts.w.globals.series[opts.seriesIndex], // Muestra el valor
    },
});
const chartDataPagos = reactive({
    series: [], // Se llenará en onMounted
});

// --- Funciones Auxiliares ---
const formatCurrency = (value) => {
    return value.toLocaleString('es-PE', { style: 'currency', currency: 'PEN' }); // Formato Soles Perú
};

// --- Carga de Datos (Simulada) ---
onMounted(() => {
    getUserData();
    getAdsData();
    // Aquí harías tus llamadas a la API para obtener los datos reales
    // Simulamos una carga con retraso:
    setTimeout(() => {
        // Datos para gráfico de consumo
        chartDataConsumo.series = [{
            name: 'Consumo Agua',
            data: [12, 15, 13, 17, 14, 18] // Datos de ejemplo
        }];

        // Datos para gráfico de pagos
        chartDataPagos.series = [4, 1, 1]; // 4 a tiempo, 1 con retraso, 1 pendiente (ejemplo)

        // Actualizar datos de anuncios, deuda, etc., si vienen de API
    }, 1500); // Simula 1.5 segundos de carga
});

async function getAdsData() {
    isLoadingAds.value = true;
    try {
        const response = await axios.get('/user/ads');
        ads.value = response.data;
    } catch (error) {
        error.value = 'Error al obtener los anuncios';
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        isLoadingAds.value = false;
    }
}
async function getUserData() {
    loading.value = true;

    try {
        const response = await axios.get('/user/get-user-data');
        user.value = response.data;

    } catch (error) {
        error.value = 'Error al obtener los datos del usuario';
        console.error(error);
    } finally {
        loading.value = false;
    }
}
const goToHouses = () => {
    window.location.href = '/user/houses/list';
}

</script>

<script>
// Necesario si NO registras ApexCharts globalmente en tu main.js
// Si ya lo registraste globalmente, puedes quitar este bloque <script> normal

export default {
    components: {
        apexchart: VueApexCharts,
    }
}
</script>
<template>
    <v-container fluid>
        <p v-if="loading">Cargango datos</p>
        <p v-else-if="error"> {{ error }}</p>
        <v-row v-else dense>
            <!-- Columna Izquierda (Bienvenida y Deuda) -->
            <v-col cols="12">
                <v-row dense>
                    <!-- Card Bienvenida -->
                    <v-col cols="12">
                        <v-card class="pa-3 mb-4" elevation="2">
                            <div class="d-flex align-center">
                                <v-avatar color="primary" size="40" class="mr-3">
                                    <v-icon icon="mdi-account-circle"></v-icon>
                                    <!-- O puedes poner iniciales: <span class="text-h6">SA</span> -->
                                </v-avatar>
                                <div>
                                    <div class="text-h6 font-weight-medium">
                                        ¡Bienvenido, {{ user.name }}!
                                    </div>
                                    <div class="text-body-2 text-medium-emphasis">
                                        Número de Usuario: <strong>{{ userId }}</strong>
                                    </div>
                                </div>
                            </div>
                        </v-card>
                    </v-col>

                    <!-- Card/Alert Deuda -->
                    <v-col cols="12">
                        <!-- Opción 1: Usando v-alert mejorado -->
                        <v-alert
                            v-if="deuda > 0"
                            type="warning"
                            variant="tonal"
                            border="start"
                            prominent
                            icon="mdi-alert-circle-outline"
                            class="mb-4"
                        >
                            <div class="text-subtitle-1 font-weight-medium">Deuda Acumulada</div>
                            <div class="text-h5 font-weight-bold text-warning">
                                S/ {{ formatCurrency(deuda) }}
                            </div>
                            <template v-slot:append>
                                <v-btn color="warning" variant="elevated" size="small" v-if="false">
                                    Pagar Ahora
                                </v-btn>
                            </template>
                        </v-alert>
                        <!-- Opción 2: Mensaje si no hay deuda -->
                        <v-alert
                            v-else
                            type="success"
                            variant="tonal"
                            border="start"
                            icon="mdi-check-circle-outline"
                            density="compact"
                            class="mb-4"
                        >
                            Estás al día con tus pagos.
                        </v-alert>
                    </v-col>

                    <!-- Card Anuncios -->
                    <v-col cols="12">
                        <v-card elevation="2" class="mb-4">
                            <v-card-item>
                                <v-card-title class="d-flex align-center">
                                    <v-icon start icon="mdi-bullhorn-variant-outline"></v-icon>
                                    Nuevos Anuncios
                                </v-card-title>
                            </v-card-item>
                            <v-divider></v-divider>
                            <div v-if="isLoadingAds">
                                <v-skeleton-loader
                                    v-for="n in 3" :key="'skel-' + n"
                                    class="mx-auto border mb-2"
                                type="list-item-two-line"
                                boilerplate
                                ></v-skeleton-loader>
                            </div>
                            <v-list
                                    v-else-if="!isLoadingAds && ads && ads.length > 0"
                                    lines="two" density="compact"
                            >
                                <v-list-item
                                    v-for="ad in ads"
                                    :key="ad.id"
                                    :href="ad.link || '#'"
                                    target="_blank"
                                >
                                    <v-list-item-title class="font-weight-medium">{{ ad.title }}</v-list-item-title>
                                    <v-list-item-subtitle>{{ formatDate(ad.start_day) }} al {{ formatDate(ad.end_day) }}</v-list-item-subtitle>
                                    <template v-slot:append>
                                        <v-icon icon="mdi-chevron-right"></v-icon>
                                    </template>
                                </v-list-item>
                                <!-- Mensaje si no hay anuncios -->
                                <v-list-item v-if="!ads.length">
                                    <v-list-item-title class="text-center text-disabled">No hay anuncios recientes.</v-list-item-title>
                                </v-list-item>
                            </v-list>
                            <v-card-actions v-if="ads.length > 5">
                                <v-spacer></v-spacer>
                                <v-btn variant="text" color="primary" size="small">Ver todos</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-card
                            class="mx-auto"
                            elevation="4"
                        >
                        <v-card-item>
                            <div>
                                <div class="text-overline mb-1">
                                    ADMINISTRACIÓN
                                </div>
                                <div class="text-h6 mb-1">
                                    Gestión de Casas
                                </div>
                                <div class="text-caption">Administra propiedades residenciales.</div>
                            </div>
                        </v-card-item>

                        <v-card-text>
                            Accede a las herramientas para añadir, editar o eliminar información sobre las casas disponibles.
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="primary"
                                variant="elevated"
                            @click="goToHouses"
                            >
                            Ir a Casas
                            </v-btn>
                        </v-card-actions>
                        </v-card>
                    </v-col>

                    <!-- Card para Gestión de Autos -->
                    <v-col cols="12" md="6">
                        <v-card
                            class="mx-auto"
                            elevation="4"
                        >
                            <v-card-item>
                                <div>
                                    <div class="text-overline mb-1">
                                        ADMINISTRACIÓN
                                    </div>
                                    <div class="text-h6 mb-1">
                                        Gestión de Autos
                                    </div>
                                    <div class="text-caption">Administra la flota de vehículos.</div>
                                </div>
                            </v-card-item>

                            <v-card-text>
                                Accede a las herramientas para añadir, editar o eliminar información sobre los autos.
                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn
                                    color="secondary"
                                variant="elevated"
                                @click="navegarAAutos"
                                >
                                Ir a Autos
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>
            </v-col>

<!--            Columna Derecha (Gráficos) -->
            <v-col cols="12" md="7" v-if="false">
                <v-row dense>
                    <!-- Card Gráfico de Consumo (Ejemplo) -->
                    <v-col cols="12" lg="6">
                        <v-card elevation="2" class="mb-4">
                            <v-card-item>
                                <v-card-title class="d-flex align-center">
                                    <v-icon start icon="mdi-chart-line"></v-icon>
                                    Consumo de Agua (m³)
                                </v-card-title>
                            </v-card-item>
                            <v-divider></v-divider>
                            <v-card-text>
                                <!-- Aquí va el componente del gráfico -->
                                <apexchart
                                    v-if="chartDataConsumo.series.length > 0"
                                    type="area"
                                    height="250"
                                    :options="chartOptionsConsumo"
                                    :series="chartDataConsumo.series"
                                ></apexchart>
                                <div v-else class="text-center text-disabled pa-5">
                                    Cargando datos de consumo...
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <!--  Card Gráfico Historial Pagos (Ejemplo) -->
                    <v-col cols="12" lg="6">
                        <v-card elevation="2" class="mb-4">
                            <v-card-item>
                                <v-card-title class="d-flex align-center">
                                    <v-icon start icon="mdi-chart-pie"></v-icon>
                                    Historial de Pagos (Últimos 6 meses)
                                </v-card-title>
                            </v-card-item>
                            <v-divider></v-divider>
                            <v-card-text>
                                <!--  Aquí va el componente del gráfico -->
                                <apexchart
                                    v-if="chartDataPagos.series.length > 0"
                                    type="donut"
                                    height="250"
                                    :options="chartOptionsPagos"
                                    :series="chartDataPagos.series"
                                ></apexchart>
                                <div v-else class="text-center text-disabled pa-5">
                                    Cargando historial de pagos...
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Puedes añadir más cards/gráficos aquí -->

                </v-row>
            </v-col>
            <Snackbar ref="mySnackbar"/>
        </v-row>
    </v-container>
</template>



<style scoped>
/* Ajustes finos si son necesarios */
.v-card-item .v-card-title {
    font-size: 1.1rem; /* Ajusta tamaño si quieres */
}

/* Asegurar que los links dentro de las listas se vean bien */
.v-list-item a {
    text-decoration: none;
    color: inherit;
}

.v-alert .v-btn {
    margin-left: 16px; /* Espacio entre texto y botón en el alert */
}
</style>
