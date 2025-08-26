<script setup>
import {computed, onMounted, ref} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {formattedMoney, getDate} from "@/utils/functions.js";

const props = defineProps({
    routes: {
        type: Object,
        required: true
    }
});

const mySnackbar = ref(null);

const headers = ref([
    {
        title: '#',
        align: 'start',
        sortable: false,
        key: 'correlative',
    },
    {title: 'Asociado', key: 'user_name'},
    {title: 'Propiedad', key: 'houses_addresses', width: '45%'},
    {title: 'Deuda Total', key: 'total_due', align: 'end'},
]);

const data = ref([]);
const loading = ref(true);
const search = ref('');

const totalAmountDue = ref();
const isDownloading = ref(false);
const currentPage = ref(1);

// --- METHODS ---
onMounted(() => {
    getData();
})

const debtFilterItems = computed(() => {
    return [
        {text: 'Todos', value: null},
        {text: 'Sin Deuda', value: 'no_debt'},
        {text: 'Con Deuda', value: 'with_debt'}
    ];
});

const associates = ref([]);

async function getData() {
    loading.value = true;

    try {
        // Construimos los parámetros de la URL
        const filterParams = {};
        data.value = [];

        if (selectedDebt.value) {
            filterParams.debt_status = selectedDebt.value;
        }

        const response = await axios.get(`${props.routes.base}`, {
            params: filterParams
        });

        data.value = response.data.data;
        totalAmountDue.value = formattedMoney(response.data.total_amount_due);

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
        console.error('Error al obtener los registros:', error);
    } finally {
        loading.value = false;
    }
}

const applyDateFilter = () => {
    search.value = '';
    getData();
};

const clearFilters = () => {
    selectedDebt.value = null;
    search.value = '';
    getData();
};

const selectedDebt = ref(null);

async function downloadExcel() {
    isDownloading.value = true;
    const filterParams = {};

    if (selectedDebt.value) {
        filterParams.debt_status = selectedDebt.value;
    }

    try {
        // Hacemos la petición a la ruta de Laravel
        const response = await axios.get(`${props.routes.export_excel}`, {
            params: filterParams,
            responseType: 'blob',
        });

        // Crear una URL temporal para el archivo 'blob'
        const url = window.URL.createObjectURL(new Blob([response.data]));

        // Crear un enlace <a> temporal para iniciar la descarga
        const link = document.createElement('a');
        link.href = url;
        const toDate = getDate();
        const fileName = `reporte-balance-asociados-${toDate}.xlsx`; // Nombre por defecto
        link.setAttribute('download', fileName);

        // Añadir el enlace al DOM, hacer clic y luego removerlo
        document.body.appendChild(link);
        link.click();
        link.remove();

        // Liberar la URL del objeto blob para limpiar memoria
        window.URL.revokeObjectURL(url);

    } catch (error) {
        console.error('Error al descargar el archivo Excel:', error);
        mySnackbar.value.show('Ocurrió un error al generar el archivo.', 'error');
    } finally {
        isDownloading.value = false;
    }
}

const formatAddresses = (houses) => {
    if (!houses || houses.length === 0) return 'N/A';
    return houses.map(h => h.address).join(' / ');
};

const dateMow = new Date().toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});
</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title>
                <v-row align="center" justify="space-between" class="ma-0">

                    <!-- Columna para el Título -->
                    <v-col cols="12" sm="auto" class="py-1">
                        <div class="d-flex align-center">
                            <v-icon icon="mdi mdi-home" class="me-2"></v-icon>
                            <span>Reporte de balance por Asociado a la fecha: {{ dateMow }}</span>
                        </div>
                    </v-col>

                    <!-- Columna para las Acciones (Botón y Chip) -->
                    <v-col cols="12" sm="auto" class="py-1">
                        <div class="d-flex align-center justify-start justify-sm-end flex-wrap ga-2">
                            <v-btn
                                color="primary"
                                prepend-icon="mdi-file-excel"
                                :loading="isDownloading"
                                @click="downloadExcel"
                            >
                                Exportar
                            </v-btn>
                            <v-chip
                                color="primary"
                                variant="elevated"
                                size="large"
                            >
                                <strong>{{ totalAmountDue }}</strong>
                            </v-chip>
                        </div>
                    </v-col>
                </v-row>
            </v-card-title>

            <v-card-text>
                <!-- Fila de Filtros -->
                <v-row align="center" class="mt-5">
                    <v-col cols="12">
                        <v-select
                            v-model="selectedDebt"
                            :items="debtFilterItems"
                            clearable
                            label="Filtrar por deuda"
                            item-title="text"
                            item-value="value"
                            variant="outlined"
                            density="compact"
                        ></v-select>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="12" class="d-flex justify-end align-center flex-wrap ga-2">
                        <v-btn @click="applyDateFilter" color="primary">Aplicar Filtro</v-btn>
                        <v-btn @click="clearFilters" color="grey">Limpiar</v-btn>
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

            <v-data-table
                :headers="headers"
                :items="data"
                :search="search"
                :loading="loading"
                class="elevation-1"
                dense
            >
                <!-- ESTE ES EL SLOT PARA EL CORRELATIVO -->
                <template v-slot:item.correlative="{ index }">
                    <span>{{ (currentPage - 1) * 5 + index + 1 }}</span>
                </template>
                <template v-slot:item.total_due="{ value }">
                    <span :style="{ color: value > 0 ? 'darkred' : '' }">{{ formattedMoney(value) }}</span>
                </template>

                <template v-slot:no-data>
                    <div class="text-center pa-4">
                        No se encontraron registros que coincidan con la búsqueda o los filtros aplicados.
                    </div>
                </template>
            </v-data-table>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
<style scoped>

</style>
