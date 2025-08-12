<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import SecurityForm from "@/components/admin/SecurityForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
// --- Props ---
const props = defineProps({
    routes: {
        type: Object,
        required: true,
    },
});

const mySnackbar = ref(null);

// --- Estado Reactivo ---
const headers = ref([        // Definición de las columnas de la tabla
    {title: 'Nombre', key: 'name', align: 'start', sortable: true},
    {title: 'Email', key: 'email', sortable: true},
    {title: 'Teléfono', key: 'phone', sortable: true},
    {title: 'Estado', key: 'status', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const users = ref([]);
const loading = ref(true);
const search = ref('');
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
        const response = await axios.get(`${props.routes.base}`);
        users.value = response.data;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}
const reloadWithMessage = (message) => {
    mySnackbar.value.show(message, 'success');
    getData();
};

const deleteSecurity = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`${props.routes.base}/${id}`)

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

const openModalAdd = () => {
    selectedElement.value = null;
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

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-account"></v-icon>
                 
                Gestión de Personal de Seguridad
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="openModalAdd"
                    class="d-none d-sm-flex"
                >
                    Agregar
                </v-btn>

                <v-btn
                    color="primary"
                    icon="mdi-plus"
                    @click="openModalAdd"
                    class="d-sm-none"
                    aria-label="Agregar"
                ></v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-text-field
                v-model="search"
                density="compact"
                label="Buscar en los resultados..."
                prepend-inner-icon="mdi-magnify"
                variant="solo-filled"
                flat
                hide-details
                single-line
                class="pa-2"
            ></v-text-field>
            <v-divider></v-divider>

            <v-data-table v-show="users.length"
                          :headers="headers"
                          :items="users"
                          :search="search"
                          :loading="loading"
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
                <template v-slot:item.status="{ value }">
                    <v-chip :color="value  === 'active' ? 'success' : 'grey'" size="small">
                        {{ value === 'active' ? 'Activo' : 'Inactivo' }}
                    </v-chip>
                </template>

            </v-data-table>
        </v-card>
        <SecurityForm v-if="showModal"
                      v-model="showModal"
                      :element="selectedElement"
                      :routes="props.routes"
                      @added="reloadWithMessage"
                      @edited="reloadWithMessage"
                      @close-modal="closeModal"
                      @refresh-data="getData"
                     >
        </SecurityForm>
        <DeleteConfirmationModal
            v-model:show="dialogDelete"
            :item-name="deleteDialogItemName"
            :loading="isDeleting"
            @confirm="deleteSecurity"
            @cancel="closeDeleteModal"
        />
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
