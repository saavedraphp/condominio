<script setup>
import {ref, onMounted, computed} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {getStructureTypes} from "@/utils/functions.js";
import {formatDate, formattedMoney} from "../../utils/functions.js";
import dayjs from "dayjs";

const mySnackbar = ref(null);

const headers = ref([
    {title: 'Fecha', key: 'payment_date', sortable: true},
    {title: 'Dirección', key: 'address', align: 'start', sortable: true},
    {title: 'Código de transacción', key: 'transaction_code', sortable: true},
    {title: 'Monto', key: 'amount', sortable: true, align: 'end'},
]);

const dateMow = new Date().toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});
const houses = ref([]);
const loading = ref(true);
const typeMap = getStructureTypes();
const totalIncomes = ref();
const totalExpenses = ref();
const totalBalance = ref();

const startDate = ref(dayjs('2025-05-01').format('YYYY-MM-DD'));//ref(null);
const endDate = ref(dayjs('2026-02-28').format('YYYY-MM-DD'));
const menuStartDate = ref(false);
const menuEndDate = ref(false);

// --- METHODS ---
onMounted(() => {
    getHouses();
})

async function getHouses() {
    loading.value = true;

    try {
        // Construimos los parámetros de la URL
        console.log(startDate.value, endDate.value);
        const params = new URLSearchParams();
        if (startDate.value) {
            params.append('start_date', dayjs(startDate.value).format('YYYY-MM-DD'));
        }
        if (endDate.value) {
            params.append('end_date', dayjs(endDate.value).format('YYYY-MM-DD'));
        }

        const response = await axios.get(`/admin/reports/income-statement/index?${params.toString()}`);
        houses.value = response.data.data;
        totalIncomes.value = formattedMoney(response.data.summary.total_incomes);
        totalExpenses.value = formattedMoney(response.data.summary.total_expenses);
        totalBalance.value = formattedMoney(response.data.summary.balance);

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

// Se llama al hacer clic en el botón "Aplicar Filtro"
const applyDateFilter = () => {
    getHouses();
};

// Limpia los filtros y carga todos los datos de nuevo
const clearFilters = () => {
    startDate.value = null;
    endDate.value = null;
    getHouses();
};

const previewReport = () => {
    if (startDate.value === null || endDate.value === null) {
        mySnackbar.value.show('Por favor, seleccione un rango de fecha para previsualizar el reporte.', 'error');
        return;
    }

    const url = `/admin/reports/income-statement/preview?start_date=${startDate.value}&end_date=${endDate.value}`;
    window.open(url, '_blank');
};


</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Reporte de Ingresos vs Egresos
                <v-spacer></v-spacer>
            </v-card-title>

            <v-card-text>
                <!-- Fila de Filtros -->
                <v-row align="center">
                    <!-- Filtro por Fechas (Server-Side) -->
                    <v-col cols="12" sm="3">
                        <v-text-field class="mt-5"
                                      v-model="startDate"
                                      label="Fecha Inicio"
                                      type="date"
                                      density="compact"
                                      variant="outlined"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="3">
                        <v-text-field class="mt-5"
                                      v-model="endDate"
                                      label="Fecha Fin"
                                      type="date"
                                      density="compact"
                                      variant="outlined"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6" class="d-flex justify-end align-center flex-wrap ga-2">
                        <v-btn @click="applyDateFilter" color="primary">Aplicar Filtro</v-btn>
                        <v-btn @click="clearFilters" color="grey">Limpiar</v-btn>
                        <v-btn @click="previewReport" color="secondary" prepend-icon="mdi-printer">
                            Previsualizar
                        </v-btn>
                    </v-col>
                </v-row>
                <v-divider></v-divider>
                <h2>RESUMEN</h2>
                <v-row>
<v-col cols="12" class="d-flex justify-end align-center flex-wrap ga-2">
    <v-chip
        color="primary"
        variant="elevated"
        size="large"
    >
        <strong>INGRESOS: {{ totalIncomes }}</strong>
    </v-chip>
    <v-chip
        color="primary"
        variant="elevated"
        size="large"
    >
        <strong>EGRESOS: {{ totalExpenses }}</strong>
    </v-chip>
    <v-chip
        color="primary"
        variant="elevated"
        size="large"
    >
        <strong>EN CAJA: {{ totalBalance }}</strong>
    </v-chip>
</v-col>
                </v-row>
                <v-divider></v-divider>

            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
<style scoped>
/* Puedes añadir estilos específicos aquí si los necesitas */
.v-card-title {
    background-color: #f5f5f5; /* Un fondo ligero para el título como en tu imagen */
    border-bottom: 1px solid #e0e0e0;
}
</style>
