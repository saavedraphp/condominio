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
                    @click="showModal = true"
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
                <template v-slot:item.fechaInicio="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template v-slot:item.fechaFin="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template v-slot:item.activo="{ value }">
                    <v-chip :color="value ? 'success' : 'grey'" size="small">
                        {{ value ? 'Activo' : 'Inactivo' }}
                    </v-chip>
                </template>

            </v-data-table>
        </v-card>

        <!-- Diálogo para Agregar/Editar Anuncio -->

        <v-dialog v-model="showModal" persistent max-width="600px">
            <AdForm
                :ad="selectedElement"
                @ad-added="addAd"
                @ad-edit="adEdit"
                @close-modal="closeModal"
            >
            </AdForm>

        </v-dialog>

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

<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import AdForm from "@/components/admin/AdForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";

const mySnackbar = ref(null);

// --- Estado Reactivo ---
const headers = ref([        // Definición de las columnas de la tabla
    {title: 'Título', key: 'title', align: 'start', sortable: true},
    {title: 'Fecha Inicio', key: 'start_day', sortable: true},
    {title: 'Fecha Fin', key: 'end_day', sortable: true},
    {title: 'Estado', key: 'status', sortable: true},
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

const addAd = async (item) => {
    try {
        const response = await axios.post('/admin/ads/', item);
        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            ads.value.push(response.data.data);
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
    }

    showModal.value = false;
};

const adEdit = async (item) => {
    try {
        const response = await axios.put(`/admin/ads/${item.id}`, {
            title: item.title,
            description: item.description,
            start_day: item.start_day,
            end_day: item.end_day,
            status: item.status ? 'active' : 'inactive'
        });

        if (response.data.success) {
            ads.value = ads.value.map(element => {
                if (element.id === item.id) {
                    return response.data.data;
                }
                return element;
            });
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

const deleteAd = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`/admin/ads/${id}`)

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



// Función auxiliar para formatear fechas (ejemplo)
function formatDate(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        // Asegurarse que la fecha sea válida y ajustar por zona horaria si es necesario
        if (isNaN(date.getTime())) return dateString; // Devolver original si no es válida
        // Sumar la diferencia de zona horaria para evitar que cambie el día
        const adjustedDate = new Date(date.getTime() + date.getTimezoneOffset() * 60000);
        return adjustedDate.toLocaleDateString('es-ES'); // Formato local español
    } catch (e) {
        return dateString; // Devolver original en caso de error
    }
}


</script>

<style scoped>
/* Puedes añadir estilos específicos aquí si los necesitas */
.v-card-title {
    background-color: #f5f5f5; /* Un fondo ligero para el título como en tu imagen */
    border-bottom: 1px solid #e0e0e0;
}
</style>
