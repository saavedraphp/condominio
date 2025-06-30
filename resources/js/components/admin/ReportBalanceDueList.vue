<script setup>
import {ref, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {getStructureTypes} from "@/utils/functions.js";


const mySnackbar = ref(null);

const headers = ref([
    {title: 'Dirección', key: 'address', sortable: true},
    {title: 'Propietario', key: 'owner', align: 'start', sortable: true},
    {title: 'Saldo inicial', key: 'opening_balance', sortable: true},
    {title: 'Total Adeudado', key: 'amount_due', sortable: true},
]);

const houses = ref([]);
const loading = ref(true);
const search = ref('Buscando resultados');
const typeMap = getStructureTypes();
// --- METHODS ---
onMounted(() => {
    getHouses();
})

async function getHouses() {
    loading.value = true;

    try {
        const response = await axios.get(`/admin/reports/balance-due/index`);
        houses.value = response.data.map(item => ({
            ...item,
            type_structure: typeMap[item.ownership_structure] ?? 'N/A',
            status: item.status || 'inactive', // Aseguramos que siempre haya un estado
        }));

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}
// --FIN METHODS

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Reporte de Casas con Deuda
                <v-spacer></v-spacer>

            </v-card-title>

            <v-divider></v-divider>

            <v-data-table v-show="houses.length"
                          :headers="headers"
                          :items="houses"
                          class="elevation-1"
                          dense
            >
                <template v-slot:item.amount_due="{ value }">
                    <span style="color: darkred">{{ value}}</span>

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
