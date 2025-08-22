<script setup>
import {ref, onMounted, computed} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {getStructureTypes} from "@/utils/functions.js";
import {formatDate, formattedMoney} from "../../utils/functions.js";
import dayjs from "dayjs";

const mySnackbar = ref(null);

const headers = ref([
    {title: 'Fecha', key: 'date', sortable: true},
    {title: 'Título', key: 'title', align: 'start', sortable: true},
    {title: 'Detalle', key: 'detail_limited', sortable: true},
    {title: 'Tipo', key: 'type', sortable: true},
    {title: 'Monto', key: 'amount', sortable: true, align: 'end'},
]);

const dateMow = new Date().toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});
const expenses = ref([]);
const loading = ref(true);
const search = ref('');
const typeMap = getStructureTypes();
const totalAmount = ref();

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
        const filterParams = {};
        expenses.value = []; // Limpiamos los gastos antes de cargar nuevos datos
        if (startDate.value) {
            filterParams.start_date = dayjs(startDate.value).format('YYYY-MM-DD');
        }
        if (endDate.value) {
            filterParams.end_date = dayjs(endDate.value).format('YYYY-MM-DD');
        }

        // Simplemente asigna el array. ¡Axios hará el resto!
        if (selectedTypes.value && selectedTypes.value.length > 0) {
            filterParams.types = selectedTypes.value;
        }

        const response = await axios.get('/admin/reports/expenses/index', {
            params: filterParams
        });

        expenses.value = response.data.data.map(item => ({
            ...item,
            detail_limited: item.description.length > 30 ? item.description.substring(0, 30) + '...' : item.description,
        }));
        totalAmount.value = formattedMoney(response.data.totals.total_amount);

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
        console.error('Error al obtener los gastos:', error);
    } finally {
        loading.value = false;
    }
}

const applyDateFilter = () => {
    search.value = '';
    if (selectedTypes.value.length === 0) {
        mySnackbar.value.show('Por favor, seleccione al menos un tipo de gasto para filtrar.', 'error');
        return;
    }
    getHouses();
};

const clearFilters = () => {
    startDate.value = null;
    endDate.value = null;
    selectedTypes.value = expenseTypes.value.map(type => type.value);
    search.value = '';
    getHouses();
};

const previewReport = () => {
    const baseUrl = '/admin/reports/expenses/preview';
    const params = new URLSearchParams();

    if (startDate.value === null || endDate.value === null) {
        mySnackbar.value.show('Por favor, seleccione un rango de fecha para previsualizar el reporte.', 'error');
        return;
    }

    if (selectedTypes.value.length === 0) {
        mySnackbar.value.show('Por favor, seleccione al menos un tipo de gasto para previsualizar el reporte.', 'error');
        return;
    }

    params.append('start_date', dayjs(startDate.value).format('YYYY-MM-DD'));
    params.append('end_date', dayjs(endDate.value).format('YYYY-MM-DD'));

    if (selectedTypes.value && selectedTypes.value.length > 0) {
        selectedTypes.value.forEach(type => {
            // Se usa 'types[]' para que el backend de Laravel lo interprete como un array.
            params.append('types[]', type);
        });
    }

    const finalUrl = `${baseUrl}?${params.toString()}`;
    window.open(finalUrl, '_blank');
};

// Modelo para nuestro v-select. ¡Este array será enviado al backend!

// Opciones disponibles para el filtro de tipos
const expenseTypes = ref([
    {text: 'Gastos de Asociación', value: 'ASOCIACION'},
    {text: 'Gastos de Edificio', value: 'EDIFICIO'},
    {text: 'Gastos de Isla Cerdeña', value: 'ISLA CERDEÑA'},
]);

const selectedTypes = ref(expenseTypes.value.map(type => type.value));

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Reporte de Gastos
                <v-spacer></v-spacer>
                <v-chip
                    color="primary"
                    variant="elevated"
                    size="large"
                >
                    <strong>El total es: {{ totalAmount }}</strong>
                </v-chip>

            </v-card-title>

            <v-card-text>
                <!-- Fila de Filtros -->
                <v-row align="center" class="mt-5">
                    <!-- Filtro por Fechas (Server-Side) -->
                    <v-col cols="12" sm="3">
                        <v-text-field
                            v-model="startDate"
                            label="Fecha Inicio"
                            type="date"
                            density="compact"
                            variant="outlined"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="3">
                        <v-text-field
                            v-model="endDate"
                            label="Fecha Fin"
                            type="date"
                            density="compact"
                            variant="outlined"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-select
                            v-model="selectedTypes"
                            :items="expenseTypes"
                            label="Tipos de Gasto"
                            item-title="text"
                            item-value="value"
                            multiple
                            chips
                            closable-chips
                            variant="outlined"
                            density="compact"
                        ></v-select>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="12" sm="6" class="d-flex justify-end align-center flex-wrap ga-2">
                        <v-btn @click="applyDateFilter" color="primary">Aplicar Filtro</v-btn>
                        <v-btn @click="clearFilters" color="grey">Limpiar</v-btn>
                        <v-btn @click="previewReport" color="secondary" prepend-icon="mdi-printer">
                            Previsualizar
                        </v-btn>
                    </v-col>
                </v-row>
                <v-divider></v-divider>
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

            <v-data-table v-show="expenses.length"
                          :headers="headers"
                          :items="expenses"
                          item-value="unique_id"
                          :search="search"
                          :loading="loading"
                          class="elevation-1"
                          dense
            >
                <template v-slot:item.amount="{ value }">
                    <span>{{ formattedMoney(value) }}</span>
                </template>
                <template #item.detail_limited="{ item }">
                    <v-tooltip location="top">
                        <template #activator="{ props }">
                          <span v-bind="props">
                            {{ item.detail_limited }}
                          </span>
                        </template>
                        <span>{{ item.description }}</span>
                    </v-tooltip>
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
