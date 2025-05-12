<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import ShowPaymentService from "@/components/user/ShowPaymentService.vue";
import {formatDate} from "@/utils/functions.js";
import PaymentServiceForm from "@/components/admin/PaymentServiceForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";

const props = defineProps({
    houseId: {
        type: String,
        default: null
    },
    typeServiceId: {
        type: Number,
        default: null
    },
    isAdmin: {
        type: Boolean,
        default: false
    }
});
const TYPE_SERVICE = {
    ELECTRICITY: 1,
    WATER: 2
};
const apiBaseForm = `${window.location.origin}/admin/consumption`;

const formTitle = computed(() => props.typeServiceId === TYPE_SERVICE.ELECTRICITY ? 'eléctrico' : 'agua');
const typeServiceIcon = computed(() => props.typeServiceId === TYPE_SERVICE.ELECTRICITY ? 'mdi mdi-lightning-bolt' : 'mdi mdi-water-pump');
const typeServiceUnit = computed(() => props.typeServiceId === TYPE_SERVICE.ELECTRICITY ? 'kWh' : 'm³');

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
const showModalForm = ref(false)
const showModalReadOnly = ref(false)
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);


const selectedElement = ref(null)

// --- METHODS ---
onMounted(() => {
    getConsumption();
})

async function getConsumption() {
    loading.value = true;

    let url = props.isAdmin
        ? `/admin/consumption/`
        : `/user/houses/${props.houseId}/consumption/`;

    try {
        const response = await axios.get(url, {
            params: {
                type_service: props.typeServiceId,
            }
        });
        data.value = response.data.map(item => ({
            ...item,
            payment_date_format: formatDate(item.payment_date),
        }));
    } catch (error) {
        mySnackbar.value.show(error.response.data.message, 'error');
    } finally {
        loading.value = false;
    }
}

const updateList = (message) => {
    mySnackbar.value.show(message, 'success');
    getConsumption();
};

const openModal = (item) => {
    selectedElement.value = {...item};
    showModalForm.value = true;
};

const openModalReadonly = (item) => {
    selectedElement.value = {...item};
    showModalReadOnly.value = true;
};
const closeModalReadOnly = (() => {
    showModalReadOnly.value = false;
    selectedElement.value = null;
});

const openModalEdit = (item) => {
    selectedElement.value = {...item, payment_date: item.payment_date};
    showModalForm.value = true;
};

const openDeleteModal = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteModalItemName = computed(() => {
    if (!itemToDelete.value) return '';
    return `${itemToDelete.value.quantity} ID: ${itemToDelete.value.id}`;

});

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    setTimeout(() => {
        itemToDelete.value = null;
        isDeleting.value = false;
    }, 300);
};

const deleteDocument = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;
        console.log(apiBaseForm);
        const response = await axios.delete(`${apiBaseForm}/${id}`)

        if (response.data && response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            await getConsumption();
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        mySnackbar.value.show(error.response?.data?.errors, 'error');
    } finally {
        closeDeleteModal();
    }
};
// --FIN METHODS


</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon :icon="typeServiceIcon"></v-icon>
                 
                Historial de consumo {{ formTitle }}
                <v-spacer></v-spacer>
                <v-btn
                    v-if="isAdmin"
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="showModalForm = true"
                >
                    Agregar consumo
                </v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <v-data-table v-show="data.length"
                          :headers="headers"
                          :items="data"
                          class="elevation-1"
                          dense
            >
                <template v-slot:item.quantity="{ item }">
                    <span>{{ item.quantity }}({{typeServiceUnit}})</span>
                </template>
                <template v-slot:item.payment_date="{ item }">
                    <span>{{ item.payment_date_format }}</span>
                </template>
                <template v-slot:item.replace="{ item }">
                    <span>{{ !!item.replace ? 'Si' : 'No'}}</span>
                </template>
                <!-- Columna de Acciones Personalizada -->
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Ver" v-if="!isAdmin">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-eye"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="openModalReadonly(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Ver" v-if="isAdmin">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-pencil"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="openModalEdit(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Eliminar" v-if="isAdmin">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-delete"
                                variant="text"
                                color="error"
                                size="small"
                                @click="openDeleteModal(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                </template>
            </v-data-table>
        </v-card>
        <Snackbar ref="mySnackbar"/>
        <v-dialog v-model="showModalReadOnly" persistent max-width="600px">
            <ShowPaymentService
                :element="selectedElement"
                :type-service-unit="typeServiceUnit"
                @close-modal="closeModalReadOnly"
            >
            </ShowPaymentService>
        </v-dialog>
        <PaymentServiceForm
            v-model="showModalForm"
            :element="selectedElement"
            :type-service-unit="typeServiceUnit"
            :type-service-id="typeServiceId"
            :url-base="apiBaseForm"
            :is-admin="props.isAdmin"
            @payment-created="updateList"
            @payment-updated="updateList"
        />
        <DeleteConfirmationModal
            v-model:show="showDeleteModal"
            :item-name="deleteModalItemName"
            :loading="isDeleting"
            @confirm="deleteDocument"
            @cancel="closeDeleteModal"
        />
    </v-container>
</template>

