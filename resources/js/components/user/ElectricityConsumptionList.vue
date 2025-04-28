<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import ShowPaymentService from "@/components/user/ShowPaymentService.vue";
import {formatDate} from "@/utils/functions.js";

const props = defineProps({
    houseId: {
        type: String,
        required: true
    },
    typeServiceId: {
        type: String,
        required: true
    }
});

const formTitle = computed(() => props.typeServiceId === true ? 'electrico' : 'agua');
const mySnackbar = ref(null);

const headers = ref([
    {title: 'Fecha', key: 'payment_date', align: 'start', sortable: true},
    {title: 'Consumo', key: 'quantity', sortable: true},
    {title: 'Observaciones', key: 'observations', sortable: true},
    {title: 'Remplazo', key: 'replace', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const data = ref([]);
const loading = ref(true);
const search = ref('Buscando resultados');
const showModal = ref(false)


const selectedElement = ref(null)

// --- METHODS ---
onMounted(() => {
    getData();
})

async function getData() {
    loading.value = true;

    try {
        const response = await axios.get(`/user/houses/${props.houseId}/water-records/`, {
            params: {
                type_service: props.typeServiceId,
            }
        });
        data.value = response.data.map(item => ({
            ...item,
            payment_date: formatDate(item.payment_date),
            replace: item.replace ? 'Sí' : 'No',
        }));
    } catch (error) {
        mySnackbar.value.show(error.response.data.message, 'error');
    } finally {
        loading.value = false;
    }
}

const openModal = (item) => {
    selectedElement.value = {...item};
    showModal.value = true;
};

const closeModal = (() => {
    showModal.value = false;
    selectedElement.value = null;
});

// --FIN METHODS

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-clipboard-text-clock-outline"></v-icon>
                 
                Historial de consumo {{ formTitle }}

            </v-card-title>

            <v-divider></v-divider>

            <v-data-table v-show="data.length"
                          :headers="headers"
                          :items="data"
                          class="elevation-1"
                          dense
            >
                <!-- Columna de Acciones Personalizada -->
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Ver Imagen">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-eye"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="openModal(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                </template>
            </v-data-table>
        </v-card>
        <Snackbar ref="mySnackbar"/>
        <v-dialog v-model="showModal" persistent max-width="600px">
            <ShowPaymentService
                :element="selectedElement"
                @close-modal="closeModal"
            >
            </ShowPaymentService>
        </v-dialog>
    </v-container>
</template>

