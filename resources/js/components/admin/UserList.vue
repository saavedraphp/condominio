<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-account"></v-icon>
                 
                Gestión de Usuarios
                <v-spacer></v-spacer>

                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="showModal = true"
                >
                    Agregar Usuario
                </v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <v-data-table v-show="users.length"
                          :headers="headers"
                          :items="users"
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
                    <v-tooltip text="Configurar">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-cog"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="openSettings(item)"
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

                <!-- Puedes añadir slots para formatear otras columnas si es necesario -->
                <!-- Ejemplo para formatear fecha -->
                <template v-slot:item.fechaInicio="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template v-slot:item.fechaFin="{ value }">
                    {{ formatDate(value) }}
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
            <UserForm
                :user="selectedElement"
                @added="addUser"
                @edit="editUser"
                @close-modal="closeModal"
            >
            </UserForm>

        </v-dialog>
        <DeleteConfirmationModal
            v-model:show="dialogDelete"
            :item-name="deleteDialogItemName"
            :loading="isDeleting"
            @confirm="deleteUser"
            @cancel="closeDeleteModal"
        />
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>

<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import UserForm from "@/components/admin/UserForm.vue";


const mySnackbar = ref(null);

// --- Estado Reactivo ---
const headers = ref([        // Definición de las columnas de la tabla
    {title: 'Nombre', key: 'name', align: 'start', sortable: true},
    {title: 'Email', key: 'email', sortable: true},
    {title: 'Teléfono', key: 'phone', sortable: true},
    {title: 'Verificado', key: 'email_verified_at', sortable: true},
    {title: 'Estado', key: 'status', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const users = ref([]);
const loading = ref(true);
const search = ref('Buscando resultados');
const showModal = ref(false)
const dialogDelete = ref(false);
const isDeleting = ref(false);
const itemToDelete = ref(null);
const form = ref(null);

const selectedElement = ref(null)


onMounted(() => {
    getData();
})

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';
    return `${itemToDelete.value.name} ID: ${itemToDelete.value.id}`;
});

async function getData() {
    loading.value = true;

    try {
        const response = await axios.get(`/admin/users/`);
        users.value = response.data;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const addUser = async (item) => {
    try {
        const response = await axios.post('/admin/users/', item);
        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            users.value.push(response.data.data);
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
    }

    showModal.value = false;
};

const editUser = async (item) => {
    try {
        const response = await axios.put(`/admin/users/${item.id}`, {
            name: item.name,
            phone: item.phone,
            status: item.status ? 'active' : 'inactive'
        });

        if (response.data.success) {
            users.value = users.value.map(element => {
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

const deleteUser = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`/admin/users/${id}`)

        if (response.data && response.data.success) {
            users.value = users.value.filter(element => element.id !== id);
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

const openSettings = (item) => {
    window.location.href = `${window.location.origin}/admin/users/${item.id}/settings`;
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
