<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import AdForm from "@/components/admin/AdForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import {formatDate} from "@/utils/functions.js";

const mySnackbar = ref(null);

// --- Estado Reactivo ---
const headers = ref([        // Definición de las columnas de la tabla
    {title: 'Título', key: 'title', align: 'start', sortable: true},
    {title: 'Fecha Inicio', key: 'start_day', sortable: true},
    {title: 'Fecha Fin', key: 'end_day', sortable: true},
    {title: 'Estado', key: 'active', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);
const ads = ref([]); // Datos de los anuncios (vendrán del servidor)
const loading = ref(true);   // Indicador de carga
const search = ref('Buscando resultados');      // Término de búsqueda (si lo implementas)
const showModal = ref(false)
const dialogDelete = ref(false); // Controla visibilidad del diálogo Delete
const isDeleting = ref(false); // Para el estado de carga
const itemToDelete = ref(null); // Item a eliminar
const form = ref(null); // Referencia al v-form
const apiBaseForm = `${window.location.origin}/admin/ads`;

// Modelo para el item editado/nuevo
const selectedElement = ref(null)

// --- METHODS ---
onMounted(() => {
    getData();
})

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';
    // Devuelve una representación del ítem (nombre, placa, id, etc.)
    return `${itemToDelete.value.title} ID: ${itemToDelete.value.id}`;


});

async function getData() {
    loading.value = true;

    try {
        const response = await axios.get(`/admin/ads/`);
        ads.value = response.data;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const updateList = (message) => {
    mySnackbar.value.show(message, 'success');
    getData();
};

const deleteAd = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`${apiBaseForm}/${id}`)

        if (response.data && response.data.success) {
            ads.value = ads.value.filter(element => element.id !== id);
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

const handleModalAdd = () => {
    selectedElement.value = null;
    showModal.value = true;
};

const openModalEdit = (item) => {
    selectedElement.value = {...item};
    showModal.value = true;
};

const closeModal = () => {
    selectedElement.value = null;
    showModal.value = false;
};

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

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-bullhorn-variant"></v-icon>
                 
                Gestión de Anuncios

                <v-spacer></v-spacer>

                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="handleModalAdd"
                >
                    Agregar Anuncio
                </v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <v-data-table v-show="ads.length"
                          :headers="headers"
                          :items="ads"
                          class="elevation-1"
                          dense
            >
                <!-- Columna de Acciones Personalizada -->
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Editar Anuncio">
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
                    <v-tooltip text="Eliminar Anuncio">
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

                <!-- Puedes añadir slots para formatear otras columnas si es necesario -->
                <!-- Ejemplo para formatear fecha -->
                <template v-slot:item.start_day="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template v-slot:item.end_day="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template v-slot:item.active="{ value }">
                    <v-chip :color="value  ? 'success' : 'grey'" size="small">
                        {{ value ? 'Activo' : 'Inactivo' }}
                    </v-chip>
                </template>

            </v-data-table>
        </v-card>
        <AdForm
            v-model="showModal"
            :element="selectedElement"
            :url-base="apiBaseForm"
            @ad-created="updateList"
            @ad-updated="updateList"
            @close-modal="closeModal"
        >
        </AdForm>


        <!-- Diálogo de Confirmación de Eliminación -->
        <DeleteConfirmationModal
            v-model:show="dialogDelete"
            :item-name="deleteDialogItemName"
            :loading="isDeleting"
            @confirm="deleteAd"
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
