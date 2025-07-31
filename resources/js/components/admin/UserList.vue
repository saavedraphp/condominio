<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import UserForm from "@/components/admin/UserForm.vue";
import {route} from "ziggy-js";


const mySnackbar = ref(null);

// --- Estado Reactivo ---
const headers = ref([        // Definición de las columnas de la tabla
    {title: 'Nombre', key: 'name', align: 'start', sortable: true},
    {title: 'Email', key: 'email', sortable: true},
    {title: 'Teléfono', key: 'phone', sortable: true},
    {title: 'Arreglo de pago', key: 'has_payment_arrangement', align: 'center', sortable: true},
    {title: 'Asociado', key: 'is_associated', align: 'center', sortable: true},
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
            email: item.email,
            has_payment_arrangement: item.has_payment_arrangement,
            is_associated: item.is_associated,
            status: item.status ? 'active' : 'inactive'
        });

        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            await getData();
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

const impersonateUser = (user) => {
    // Construimos la URL completa dinámicamente
    const url = `${window.location.origin}/admin/users/impersonate-in-new-tab/${user.id}`;
    //const url = route('admin.impersonate.new_tab', { user: user.id });
    console.log(url);

    // Usamos window.open() para abrir la URL en una nueva pestaña/ventana
    window.open(url, '_blank');
}
</script>
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
                    class="d-none d-sm-flex"
                >
                    Agregar Usuario
                </v-btn>

                <v-btn
                    color="primary"
                    icon="mdi-plus"
                    @click="showModal = true"
                    class="d-sm-none"
                    aria-label="Agregar Usuario"
                ></v-btn>
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
                    <v-tooltip text="Login">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi mdi-login"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="impersonateUser(item)"
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

                <template v-slot:item.email_verified_at="{ value }">
                    <v-chip :color="value ? 'success' : 'grey'" size="small">
                        {{ value ? 'Verificado' : 'Pendiente' }}
                    </v-chip>
                </template>
                <template v-slot:item.has_payment_arrangement="{ value }">
                    <v-chip :color="value  === true ? 'success' : 'grey'" size="small">
                        {{ value === true ? 'Si' : 'No' }}
                    </v-chip>
                </template>
                <template v-slot:item.is_associated="{ value }">
                    <v-chip :color="value  === true ? 'success' : 'grey'" size="small">
                        {{ value === true ? 'Si' : 'No' }}
                    </v-chip>
                </template>
                <template v-slot:item.status="{ value }">
                    <v-chip :color="value  === 'active' ? 'success' : 'grey'" size="small">
                        {{ value === 'active' ? 'Activo' : 'Inactivo' }}
                    </v-chip>
                </template>

            </v-data-table>
        </v-card>
        <UserForm v-if="showModal"
                  v-model="showModal"
                  :user="selectedElement"
                  @added="addUser"
                  @edit="editUser"
                  @close-modal="closeModal"
                  @refresh-data="getData"
        >
        </UserForm>
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
