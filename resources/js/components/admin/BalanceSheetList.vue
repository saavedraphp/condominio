<script setup>
import {ref, onMounted, computed, watch} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {formattedMoney} from "../../utils/functions.js";
import dayjs from "dayjs";
import {useField} from "vee-validate";

const mySnackbar = ref(null);
const loading = ref(true);
const selectedYear = ref(null);
const selectedMonth = ref(null);
async function getBalance() {
    loading.value = true;

    try {
        // Construimos los parámetros de la URL
        const params = new URLSearchParams();
        params.append('anho', selectedYear.value);
        params.append('month', selectedMonth.value);


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

// Limpia los filtros y carga todos los datos de nuevo
const clearFilters = () => {
    selectedYear.value = null;
    selectedMonth.value = null;
};

const previewReport = () => {
    if (selectedYear.value === null || selectedMonth.value === null) {
        mySnackbar.value.show('Por favor, seleccione un año y mes  para previsualizar el reporte.', 'error');
        return;
    }

    const url = `/admin/reports/balance-sheet/preview?anho=${selectedYear.value}&month=${selectedMonth.value}`;
    window.open(url, '_blank');
};

const monthOptions = ref([
    {name: 'Enero', value: 1},
    {name: 'Febrero', value: 2},
    {name: 'Marzo', value: 3},
    {name: 'Abril', value: 4},
    {name: 'Mayo', value: 5},
    {name: 'Junio', value: 6},
    {name: 'Julio', value: 7},
    {name: 'Agosto', value: 8},
    {name: 'Septiembre', value: 9},
    {name: 'Octubre', value: 10},
    {name: 'Noviembre', value: 11},
    {name: 'Diciembre', value: 12},
]);

const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let year = currentYear; year >= 2020; year--) {
        years.push(year);
    }
    return years;
});

const selectedMonthName = computed(() => {
    const month = monthOptions.value.find(m => m.value === selectedMonth.value);
    return month ? month.name : '';
});

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Reporte de Balance
                <v-spacer></v-spacer>
            </v-card-title>

            <v-card-text>
                <!-- Fila de Filtros -->
                <v-row align="center" class="mt-5">
                    <v-col cols="12" md="3">
                        <v-select
                            v-model="selectedYear"
                            :items="yearOptions"
                            label="Año"
                            variant="outlined"
                            density="compact"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" sm="3">
                        <v-select
                            v-model="selectedMonth"
                            :items="monthOptions"
                            item-title="name"
                            item-value="value"
                            label="Mes"
                            variant="outlined"
                            density="compact"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" sm="6" class="d-flex justify-end align-center flex-wrap ga-2">
                        <v-btn @click="clearFilters" color="grey">Limpiar</v-btn>
                        <v-btn @click="previewReport" color="secondary" prepend-icon="mdi-printer">
                            Previsualizar
                        </v-btn>
                    </v-col>
                </v-row>
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
