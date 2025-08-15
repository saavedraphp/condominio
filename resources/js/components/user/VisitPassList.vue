<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import VisitPassForm from "@/components/user/VisitPassForm.vue";
import {formatDate} from "../../utils/functions.js";
import VirtualPassModal from "@/components/user/VirtualPassModal.vue";
// --- Props ---
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    house: {
        type: Object,
        required: true,
    },
    routes: {
        type: Object,
        required: true,
    },
});

const mySnackbar = ref(null);

// --- Estado Reactivo ---
const headers = ref([        // Definición de las columnas de la tabla
    {title: 'Título', key: 'title', align: 'start', sortable: true},
    { title: 'Vigencia', value: 'validity', sortable: false },
    {title: 'Código de Acceso', key: 'access_code', sortable: true},
    {title: 'Estado', key: 'status', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const list = ref([]);
const loading = ref(true);
const search = ref('');
const showModal = ref(false)
const dialogDelete = ref(false);
const isDeleting = ref(false);
const itemToDelete = ref(null);
const selectedElement = ref(null)


onMounted(() => {
    getData();
})

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';
    return `${itemToDelete.value.title} ID: ${itemToDelete.value.id}`;
});

async function getData() {
    loading.value = true;

    try {
        const response = await axios.get(`${props.routes.base}`);
        list.value = response.data.map(item => ({
            ...item,
            starts_at_format: item.starts_at ? formatDate(item.starts_at) : 'N/A',
            expires_at_format: item.expires_at ? formatDate(item.expires_at) : 'N/A',
        }));

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

const handleDelete = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`${props.routes.base}/${id}`)

        if (response.data && response.data.success) {
            list.value = list.value.filter(element => element.id !== id);
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

const getStatus = (pass) => {
    let now = new Date();
    now = new Date(now.getFullYear(), now.getMonth(), now.getDate()); // Normalizar a la fecha actual sin hora
    const start = new Date(pass.starts_at);
    const end = new Date(pass.expires_at);

    if (now > end) {
        return {text: 'Vencido', color: 'red-darken-2'};
    }
    /*if (now < start) {
        return {text: 'Próximo', color: 'blue-darken-1'};
    }*/
    return {text: 'Activo', color: 'green-darken-1'};
};

const isModalOpen = ref(false);
const selectedPassId = ref(null);

const showVirtualPass = (id) => {
    selectedPassId.value = id;
    isModalOpen.value = true;
};

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-account"></v-icon>
                 
                Gestión de Pases de Visita
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

            <v-data-table v-show="list.length"
                          :headers="headers"
                          :items="list"
                          :search="search"
                          :loading="loading"
                          class="elevation-1"
                          dense
            >
                <!-- Columna de Acciones Personalizada -->
                <template v-slot:item.validity="{ item }">
                    <div>
                        <strong>Inicio:</strong> {{ item.starts_at_format }}
                    </div>
                    <div>
                        <strong>Fin:</strong> {{ item.expires_at_format }}
                    </div>
                </template>
                <template v-slot:item.status="{ item }">
                    <v-chip :color="getStatus(item).color" variant="elevated" size="small">
                        {{ getStatus(item).text }}
                    </v-chip>
                </template>
                <template v-slot:item.actions="{ item }">
                    <v-btn icon small @click="showVirtualPass(item.id)">
                        <v-icon>mdi-qrcode</v-icon>
                    </v-btn>
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
                <!--                <template v-slot:item.status="{ value }">
                                    <v-chip :color="value  === 'active' ? 'success' : 'grey'" size="small">
                                        {{ value === 'active' ? 'Activo' : 'Inactivo' }}
                                    </v-chip>
                                </template>-->

            </v-data-table>
        </v-card>
        <VisitPassForm v-if="showModal"
                       v-model="showModal"
                       :element="selectedElement"
                       :house="props.house"
                       :routes="props.routes"
                       @added="reloadWithMessage"
                       @edited="reloadWithMessage"
                       @close-modal="closeModal"
                       @refresh-data="getData"
        >
        </VisitPassForm>
        <DeleteConfirmationModal
            v-model:show="dialogDelete"
            :item-name="deleteDialogItemName"
            :loading="isDeleting"
            @confirm="handleDelete"
            @cancel="closeDeleteModal"
        />
        <VirtualPassModal
            v-if="isModalOpen"
            v-model="isModalOpen"
            :pass-id="selectedPassId" />
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
