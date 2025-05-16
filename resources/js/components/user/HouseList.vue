<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import House from "@/components/admin/House.vue";


const mySnackbar = ref(null);

const headers = ref([
    {title: 'Unid', key: 'property_unit', align: 'start', sortable: true},
    {title: 'Cod Pago', key: 'payment_code', sortable: true},
    {title: 'Direccion', key: 'address', sortable: true},
    {title: 'Area Cons', key: 'construction_area', sortable: true},
    {title: '% Part', key: 'participation_percentage', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const houses = ref([]);
const loading = ref(true);
const search = ref('Buscando resultados');
const showModal = ref(false)


const selectedElement = ref(null)

// --- METHODS ---
onMounted(() => {
    getHouses();
})

async function getHouses() {
    loading.value = true;

    try {
        const response = await axios.get(`/user/houses/`);
        houses.value = response.data;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const showPageDashboard = (item) => {
    window.location.href = `/user/houses/${item.id}/dashboard/`;
}
// --FIN METHODS

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Gestión de Casas

            </v-card-title>

            <v-divider></v-divider>

            <div v-if="houses.length">
            <v-data-table v-show="houses.length"
                          :headers="headers"
                          :items="houses"
                          class="elevation-1"
                          dense
            >
                <!-- Columna de Acciones Personalizada -->
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Administrar">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-cog"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="showPageDashboard(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                </template>
            </v-data-table>
            </div>
            <v-alert
                v-else
                type="info"
                variant="tonal"
                border="start"
                prominent
                icon="mdi-information-outline"
                class="ma-4"
            >
          <span class="text-body-1 font-weight-medium">
            Aún no se le ha asignado una casa para su administración. Por favor, contacte con el administrador.
          </span>
            </v-alert>
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
