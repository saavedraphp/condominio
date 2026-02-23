<script setup>
import {ref, onMounted, computed} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {getStructureTypes} from "@/utils/functions.js";
import {formattedMoney, getDate} from "../../utils/functions.js";

const mySnackbar = ref(null);

const headers = ref([
    {title: 'Dirección', key: 'address', sortable: true},
    {title: 'Propietario', key: 'owner', align: 'start', sortable: true},
    {title: 'Arreglo de Pago', key: 'has_payment_arrangement', sortable: true},
    {title: 'Total Adeudado', key: 'amount_due', sortable: true},
]);

const dateMow = new Date().toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});

const typeHousesFilterItems = computed(() => {
    return [
        {text: 'Todos', value: null},
        {text: 'Departamentos', value: 'deparments'},
    ];
});

const selectedHouse = ref(null);

const houses = ref([]);
const loading = ref(true);
const search = ref('Buscando resultados');
const typeMap = getStructureTypes();
const totalAmountDue = ref();
// --- METHODS ---
onMounted(() => {
    getHouses();
})

async function getHouses() {
    loading.value = true;
    const filterParams = {};

    if (selectedHouse.value) {
        filterParams.type_house = selectedHouse.value;
    }

    try {
        const response = await axios.get(`/admin/reports/balance-due/index`,{
            params: filterParams
        });
        houses.value = response.data.data.map(item => ({
            ...item,
            type_structure: typeMap[item.ownership_structure] ?? 'N/A',
            status: item.status || 'inactive', // Aseguramos que siempre haya un estado
        }));
        totalAmountDue.value = formattedMoney(response.data.total_amount_due);

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const applyDateFilter = () => {
    getHouses();
};

const isDownloading =  ref(false);

async function downloadExcel() {
    isDownloading.value = true;
    const filterParams = {};

    if (selectedHouse.value) {
        filterParams.type_house = selectedHouse.value;
    }

    try {
        // Hacemos la petición a la ruta de Laravel
        const response = await axios.get('/admin/debts/export/excel', {
            params: filterParams,
            responseType: 'blob', // ¡MUY IMPORTANTE! Esto le dice a axios que espere datos binarios
        });

        // Crear una URL temporal para el archivo 'blob'
        const url = window.URL.createObjectURL(new Blob([response.data]));

        // Crear un enlace <a> temporal para iniciar la descarga
        const link = document.createElement('a');
        link.href = url;
        const toDate = getDate();
        const fileName = `debts-list-${toDate}.xlsx`; // Nombre por defecto
        link.setAttribute('download', fileName);

        // Añadir el enlace al DOM, hacer clic y luego removerlo
        document.body.appendChild(link);
        link.click();
        link.remove();

        // Liberar la URL del objeto blob para limpiar memoria
        window.URL.revokeObjectURL(url);

    } catch (error) {
        console.error('Error al descargar el archivo Excel:', error);
        mySnackbar.value.show('Ocurrió un error al generar el archivo.','error');
    } finally {
        isDownloading.value = false;
    }
}
</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Reporte de Casas con Deuda a la fecha: {{ dateMow }}
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    prepend-icon="mdi-file-excel"
                    :loading="isDownloading"
                    @click="downloadExcel"
                    class="mr-4"
                >
                    Exportar
                </v-btn>
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
                    <v-col cols="12" sm="6">
                        <v-select class="mt-5"
                            v-model="selectedHouse"
                            :items="typeHousesFilterItems"
                            clearable
                            label="Filtrar por Tipo de casa "
                            item-title="text"
                            item-value="value"
                            variant="outlined"
                            density="compact"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" sm="6" class="d-flex justify-end align-center flex-wrap ga-2">
                        <v-btn @click="applyDateFilter" color="primary">Aplicar Filtro</v-btn>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-divider></v-divider>
            <v-data-table v-show="houses.length"
                          :headers="headers"
                          :items="houses"
                          class="elevation-1"
                          dense
            >
                <template v-slot:item.amount_due="{ value }">
                    <span style="color: darkred">{{ formattedMoney(value) }}</span>
                </template>
                <template v-slot:item.has_payment_arrangement="{ value }">
                    <v-chip :color="value === 'Sí' ? 'success' : 'grey'" size="small">
                        {{ value }}
                    </v-chip>
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
