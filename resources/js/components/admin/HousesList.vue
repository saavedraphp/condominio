<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import House from "@/components/admin/House.vue";
import {getStructureTypes} from "@/utils/functions.js";


const mySnackbar = ref(null);

const headers = ref([
    {title: 'Id', key: 'id', sortable: true},
    {title: 'Dirección', key: 'address', sortable: true},
    {title: 'Propietario', key: 'owner_name', sortable: true},
    {title: 'Unid', key: 'property_unit', align: 'start', sortable: true},
    {title: 'Tipo', key: 'type_structure', sortable: true},
    {title: 'Saldo inicial', key: 'opening_balance', sortable: true},
    {title: '% Part', key: 'participation_percentage', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const houses = ref([]);
const loading = ref(true);
const search = ref('Buscando resultados');
const showModal = ref(false)
const dialogDelete = ref(false);
const isDeleting = ref(false);
const itemToDelete = ref(null);
const selectedElement = ref(null)
const typeMap = getStructureTypes();
// --- METHODS ---
onMounted(() => {
    getHouses();
})

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';
    return `${itemToDelete.value.address} ID: ${itemToDelete.value.property_unit}`;

});

async function getHouses() {
    loading.value = true;

    try {
        const response = await axios.get(`/admin/houses/`);
        houses.value = response.data.map(item => ({
            ...item,
            type_structure: typeMap[item.ownership_structure] ?? 'N/A',
            status: item.status || 'inactive', // Aseguramos que siempre haya un estado
            owner_name: item.owner[0]?.name || 'Sin propietario'
        }));

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const addHouse = async (item) => {
    try {
        const response = await axios.post('/admin/houses/', item);
        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            await getHouses();
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
    }

    showModal.value = false;
};

const editHouse = async (item) => {
    try {
        const response = await axios.put(`/admin/houses/${item.id}`, item);
        if (response.data.success) {
            await getHouses();
            mySnackbar.value.show(response.data.message, 'success');
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response.data.errors, 'error');
        console.log(error);
    }

    showModal.value = false;
};

const deleteHouse = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`/admin/houses/${id}`)

        if (response.data && response.data.success) {
            houses.value = houses.value.filter(element => element.id !== id);
            mySnackbar.value.show(response.data.message, 'success');

        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        mySnackbar.value.show(error.response?.data?.errors, 'error');

    } finally {
        closeDeleteModal();
    }
};

const openModalEdit = (item) => {
    selectedElement.value = {...item};
    showModal.value = true;
};

const closeModal = (() => {
    selectedElement.value = null;
    showModal.value = false;
});

const openDeleteModal = (item) => {
    itemToDelete.value = item;
    dialogDelete.value = true;
};

const closeDeleteModal = () => {
    dialogDelete.value = false;
    setTimeout(() => {
        itemToDelete.value = null;
        isDeleting.value = false;
    }, 300);
};

// --FIN METHODS

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Gestión de Casas
                <v-spacer></v-spacer>

                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="showModal = true"
                >
                    Agregar Casa
                </v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <v-data-table v-show="houses.length"
                          :headers="headers"
                          :items="houses"
                          class="elevation-1"
                          dense
            >
                <!-- Columna de Acciones Personalizada -->
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Editar">
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
                    <v-tooltip text="Eliminar">
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
                <template v-slot:item.email_verified_at="{ value }">
                    <v-chip :color="value ? 'success' : 'grey'" size="small">
                        {{ value ? 'Verificado' : 'Pendiente' }}
                    </v-chip>
                </template>
                <template v-slot:item.status="{ value }">
                    <v-chip :color="value  === 'active' ? 'success' : 'grey'" size="small">
                        {{ value === 'active' ? 'Activo' : 'Inactivo' }}
                    </v-chip>
                </template>

            </v-data-table>
        </v-card>

        <!-- Diálogo para Agregar/Editar Anuncio -->

        <v-dialog v-model="showModal" persistent max-width="600px">
            <House
                :house="selectedElement"
                @house-added="addHouse"
                @house-edit="editHouse"
                @close-modal="closeModal"
            >
            </House>
        </v-dialog>

        <!-- Diálogo de Confirmación de Eliminación -->
        <DeleteConfirmationModal
            v-model:show="dialogDelete"
            :item-name="deleteDialogItemName"
            :loading="isDeleting"
            @confirm="deleteHouse"
            @cancel="closeDeleteModal"
        />
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
