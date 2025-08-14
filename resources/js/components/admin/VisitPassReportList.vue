<script setup>
import {ref, onMounted, computed} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import dayjs from "dayjs";
import AccessLogDetailDialog from "@/components/admin/AccessLogDetailDialog.vue";
import {formatLogStatus} from "@/utils/statusFormatter.js";

const props = defineProps({
    routes: {
        type: Object,
        required: true
    }
});

const mySnackbar = ref(null);

const headers = ref([
    {title: 'Fecha y Hora', key: 'created_at', sortable: true},
    {title: 'Título del Pase', key: 'pass.title', align: 'start', sortable: true},
    {title: 'Estado', key: 'status', sortable: true},
    {title: 'Personal', key: 'security.name', sortable: true},
    {title: 'Dirección', key: 'property.address', sortable: true, align: 'start'},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},

]);

const accessLogs = ref([]);
const loading = ref(true);
const search = ref('');

const startDate = ref(null);
const endDate = ref(null);

const isDetailDialogVisible = ref(false);
const selectedLog = ref(null);

const showDetails = (item) => {
    selectedLog.value = item;
    isDetailDialogVisible.value = true;
};
// --- METHODS ---
onMounted(() => {
    initializeDates();
    getData();
    fetchSecurityStaff();
})

const initializeDates = () => {
    const firstDate = new Date();
    firstDate.setDate(firstDate.getDate() - 30);
    startDate.value = firstDate.toISOString().substring(0, 10);
    endDate.value = new Date().toISOString().substring(0, 10);
};

const securityFilterItems = computed(() => {
    const allOption = {text: 'Todos', value: null};
    return [allOption, ...SecurityStaff.value];
});

const SecurityStaff = ref([]);

async function fetchSecurityStaff() {
    try {
        const response = await axios.get(`${props.routes.securities}`);
        SecurityStaff.value = response.data.map(item => ({
            text: item.name,
            value: item.id
        }));
    } catch (error) {
        console.error('Error al obtener el personal de seguridad:', error);
        mySnackbar.value.show('Lo sentimos, hubo un problema al cargar el personal de seguridad.', 'error');
        return [];
    }
}

async function getData() {
    loading.value = true;

    try {
        // Construimos los parámetros de la URL
        const filterParams = {};
        accessLogs.value = []; // Limpiamos los gastos antes de cargar nuevos datos
        if (startDate.value) {
            filterParams.start_date = dayjs(startDate.value).format('YYYY-MM-DD');
        }
        if (endDate.value) {
            filterParams.end_date = dayjs(endDate.value).format('YYYY-MM-DD');
        }

        // Simplemente asigna el array. ¡Axios hará el resto!
        if (selectedSecurity.value) {
            filterParams.security_id = selectedSecurity.value;
        }

        const response = await axios.get(`${props.routes.base}`, {
            params: filterParams
        });

        accessLogs.value = response.data.data;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
        console.error('Error al obtener los gastos:', error);
    } finally {
        loading.value = false;
    }
}

const applyDateFilter = () => {

    if (!startDate.value || !endDate.value) {
        mySnackbar.value.show('Por favor, selecciona ambas fechas.', 'error');
        return;
    }
    if (dayjs(startDate.value).isAfter(dayjs(endDate.value))) {
        mySnackbar.value.show('La fecha de inicio no puede ser posterior a la fecha de fin.', 'error');
        return;
    }
    search.value = '';
    getData();
};

const clearFilters = () => {
    initializeDates();
    selectedSecurity.value = null;
    search.value = '';
    getData();
};

const selectedSecurity = ref(null);

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Reporte de Pases de Visitas
                <v-spacer></v-spacer>
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
                            v-model="selectedSecurity"
                            :items="securityFilterItems"
                            clearable
                            label="Filtrar por Personal de Seguridad"
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
                :items="accessLogs"
                :search="search"
                :loading="loading"
                class="elevation-1"
                dense
            >
                <template v-slot:item.status="{ value }">
                    <v-chip
                        :color="formatLogStatus(value).color"
                        size="small"
                        label
                    >
                        {{ formatLogStatus(value).text }}
                    </v-chip>
                </template>
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Ver Detalles">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-eye"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="showDetails(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                </template>
                <template v-slot:no-data>
                    <div class="text-center pa-4">
                        No se encontraron registros que coincidan con la búsqueda o los filtros aplicados.
                    </div>
                </template>
            </v-data-table>
        </v-card>
        <Snackbar ref="mySnackbar"/>
        <AccessLogDetailDialog
            v-model="isDetailDialogVisible"
            :log="selectedLog"
        />
    </v-container>
</template>
