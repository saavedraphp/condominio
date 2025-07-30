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
    {title: 'Monto', key: 'amount_formatted', sortable: true, align: 'end'},
]);

const dateMow = new Date().toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});
const houses = ref([]);
const loading = ref(true);
const search = ref('');
const typeMap = getStructureTypes();
const totalAmountDue = ref();

const startDate = ref(null);
const endDate = ref(null);
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
        const params = new URLSearchParams();
        if (startDate.value) {
            params.append('start_date', dayjs(startDate.value).format('YYYY-MM-DD'));
        }
        if (endDate.value) {
            params.append('end_date', dayjs(endDate.value).format('YYYY-MM-DD'));
        }

        const response = await axios.get(`/admin/reports/payments/index?${params.toString()}`);
        //const response = await axios.get(`/admin/reports/payments/index`);
        houses.value = response.data.data.map(item => ({
            ...item,
            type_structure: typeMap[item.ownership_structure] ?? 'N/A',
            status: item.status || 'inactive', // Aseguramos que siempre haya un estado
            amount_formatted: formattedMoney(item.amount),
        }));
        totalAmountDue.value = formattedMoney(response.data.total_amount);

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

// Se llama al hacer clic en el botón "Aplicar Filtro"
const applyDateFilter = () => {
    search.value = '';
    getHouses();
};

// Limpia los filtros y carga todos los datos de nuevo
const clearFilters = () => {
    startDate.value = null;
    endDate.value = null;
    search.value = ''; // Opcional: también limpiar el buscador de texto
    getHouses();
};

const previewReport = () => {
    if (startDate.value === null || endDate.value === null) {
        mySnackbar.value.show('Por favor, seleccione un rango de fecha para previsualizar el reporte.', 'error');
        return;
    }

    const url = `/admin/reports/payments/preview?start_date=${startDate.value}&end_date=${endDate.value}`;
    window.open(url, '_blank');
};


</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Reporte de pagos
                <v-spacer></v-spacer>
                <v-chip
                    color="primary"
                    variant="elevated"
                    size="large"
                >
                    <strong>El total es: {{ totalAmountDue }}</strong>
                </v-chip>

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
                <!-- Filtro de Texto (Client-Side) -->
                <v-text-field
                    v-model="search"
                    density="compact"
                    label="Buscar en los resultados..."
                    prepend-inner-icon="mdi-magnify"
                    variant="solo-filled"
                    flat
                    hide-details
                    single-line
                    class="pa-2"
                ></v-text-field>
            </v-card-text>
            <v-divider></v-divider>

            <v-data-table v-show="houses.length"
                          :headers="headers"
                          :items="houses"
                          :search="search"
                          :loading="loading"
                          class="elevation-1"
                          dense
            >
                <template v-slot:item.amount_due="{ value }">
                    <span style="color: darkred">{{ formattedMoney(value) }}</span>
                </template>
            </v-data-table>
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
