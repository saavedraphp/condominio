<template>
    <v-container>
        <v-card>
            <v-card-title>
                Reporte de Presupuestos vs. Gastos Reales
            </v-card-title>
            <v-card-subtitle>
                Filtra y genera el reporte.
            </v-card-subtitle>
            <v-card-text>
                <v-row>
                    <v-col cols="12" md="4">
                        <v-select
                            v-model="filters.year"
                            :items="yearOptions"
                            label="Año"
                            density="compact"
                            variant="outlined"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-select
                            v-model="filters.month"
                            :items="monthOptions"
                            label="Gastos hasta el mes de (Opcional)"
                            clearable
                            density="compact"
                            variant="outlined"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" md="4" class="d-flex align-top">
                        <v-btn
                            color="primary"
                            @click="fetchReportData"
                            :loading="loading"
                            class="mr-2"
                        >
                            Ver Reporte
                        </v-btn>
                        <v-btn
                            color="secondary"
                            @click="downloadPdf"
                            :disabled="!reportData"
                            :loading="downloadingPdf"
                        >
                            Descargar PDF
                        </v-btn>
                    </v-col>
                </v-row>

                <v-divider class="my-4"></v-divider>

                <div v-if="loading" class="text-center">
                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                </div>

                <div v-if="error" class="text-center pa-4">
                    <v-alert type="error" dense>{{ error }}</v-alert>
                </div>

                <div v-if="reportData && !loading && !error">
                    <h2 class="text-h6 mb-2">{{ reportData.report_owner || 'Propietarios' }}</h2>
                    <h3 class="text-subtitle-1 mb-4" style="color: #1976D2;">{{ reportData.report_title }}</h3>

                    <v-table density="compact" class="mb-6">
                        <thead>
                        <tr>
                            <th class="text-left">Descripción</th>
                            <th class="text-right">Cantidad ({{ reportData.currency_symbol }})</th>
                            <th class="text-right">Porcentaje</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template v-for="item in reportData.items" :key="item.budget_type_name">
                            <tr style="background-color: #f5f5f5;">
                                <td colspan="3"><strong>{{ item.budget_type_name }}</strong></td>
                            </tr>
                            <tr>
                                <td>
                                    Presupuesto Anual de {{ item.description }}:<br />
                                    Gastos de {{ item.description }} a {{ reportData.month_label }} {{ reportData.year }}:
                                </td>
                                <td class="text-right">
                                    {{ formatCurrency(item.budgeted_amount, reportData.currency_symbol) }}<br />
                                    {{ formatCurrency(item.spent_amount, reportData.currency_symbol) }}
                                </td>
                                <td class="text-right">
                                    <br /> <!-- Alinea con la segunda línea -->
                                    <strong>{{ item.percentage_spent.toFixed(2) }}%</strong>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="!reportData.items.length">
                            <td colspan="3" class="text-center">No hay datos de presupuesto para este período.</td>
                        </tr>
                        </tbody>
                    </v-table>

                    <v-card variant="outlined" class="pa-4">
                        <v-row>
                            <v-col cols="8">Presupuesto Total ({{ reportData.year }}):</v-col>
                            <v-col cols="4" class="text-right">{{ formatCurrency(reportData.totals.total_budgeted, reportData.currency_symbol) }}</v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="8">Gastos realizados ({{ reportData.year }} {{ reportData.month_label }}):</v-col>
                            <v-col cols="2" class="text-right">{{ formatCurrency(reportData.totals.total_spent, reportData.currency_symbol) }}</v-col>
                            <v-col cols="2" class="text-right"><strong>{{ reportData.totals.overall_percentage_spent.toFixed(2) }}%</strong></v-col>
                        </v-row>
                    </v-card>
                </div>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios'; // Asegúrate de tener axios instalado y configurado

const filters = ref({
    year: new Date().getFullYear(),
    month: null, // null para indicar "año completo" inicialmente
    white_label_id: 1, // Reemplaza con tu lógica para obtener el white_label_id
});

const reportData = ref(null);
const loading = ref(false);
const downloadingPdf = ref(false);
const error = ref(null);

const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let i = 0; i < 10; i++) {
        years.push(currentYear - 5 + i);
    }
    return years;
});

const monthOptions = computed(() => [
    { title: 'Enero', value: 1 }, { title: 'Febrero', value: 2 },
    { title: 'Marzo', value: 3 }, { title: 'Abril', value: 4 },
    { title: 'Mayo', value: 5 }, { title: 'Junio', value: 6 },
    { title: 'Julio', value: 7 }, { title: 'Agosto', value: 8 },
    { title: 'Septiembre', value: 9 }, { title: 'Octubre', value: 10 },
    { title: 'Noviembre', value: 11 }, { title: 'Diciembre', value: 12 },
]);

const formatCurrency = (value, symbol = 'S/') => {
    if (typeof value !== 'number') return value;
    return `${symbol} ${value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
};

const fetchReportData = async () => {
    loading.value = true;
    error.value = null;
    reportData.value = null;
    try {
        const params = { ...filters.value };
        if (!params.month) delete params.month; // No enviar 'month' si es null

        const response = await axios.get('/user/reports/budget-summary-data', { params });
        reportData.value = response.data;
        reportData.value.report_owner = "Propietarios de Islas Cerdeñas"; // Añadido para UI
    } catch (err) {
        console.error("Error fetching report data:", err);
        error.value = "Error al cargar el reporte. " + (err.response?.data?.message || err.message);
    } finally {
        loading.value = false;
    }
};

const downloadPdf = () => {
    downloadingPdf.value = true;
    error.value = null;
    // Construir URL para descarga
    // Reemplaza '/reports/budget-summary/download' con tu endpoint real si es diferente
    let url = `/user/reports/budget-summary/download?year=${filters.value.year}&white_label_id=${filters.value.white_label_id}`;
    if (filters.value.month) {
        url += `&month=${filters.value.month}`;
    }

    // Abrir en una nueva pestaña para iniciar la descarga
    window.open(url, '_blank');

    // No hay una forma fácil de saber cuándo termina la descarga del navegador.
    // Simplemente quitamos el estado de carga después de un breve retraso.
    setTimeout(() => {
        downloadingPdf.value = false;
    }, 2000); // Ajusta si es necesario
};

onMounted(() => {
    // Opcional: Cargar datos iniciales al montar el componente
    // fetchReportData();
});
</script>

<style scoped>
/* Puedes añadir estilos específicos aquí si es necesario */
.v-table th.text-right, .v-table td.text-right {
    text-align: right !important;
}
</style>
