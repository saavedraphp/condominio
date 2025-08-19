<script setup>
import {ref, reactive, watch, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import SecurityForm from "@/components/admin/SecurityForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import QrCodeForm from "@/components/admin/QrCodeForm.vue";
import QrModal from "@/components/user/QrModal.vue";
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
    {title: 'Titulo', key: 'title', align: 'start', sortable: true},
    {title: 'Detalle', key: 'description', sortable: true},
    {title: 'Código', key: 'code', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const codes = ref([]);
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
        codes.value = response.data.data;

    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
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
            codes.value = codes.value.filter(element => element.id !== id);
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

const isModalOpen = ref(false);
const elementQr = ref(null);

const showQR = (element) => {
    elementQr.value = element;
    isModalOpen.value = true;
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
                <v-icon icon="mdi mdi-qrcode"></v-icon>
                 
                Gestión de Codigos QR del Sistema
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
                v-if="false"
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

            <v-data-table
                :headers="headers"
                :items="codes"
                :search="search"
                class="elevation-1"
                dense
                no-data-text="No hay resultados para mostrar"
            >
                <!-- Columna de Acciones Personalizada -->
                <template v-slot:item.actions="{ item }">
                    <v-btn icon small @click="showQR(item)">
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
                <template v-slot:item.status="{ value }">
                    <v-chip :color="value  === 'active' ? 'success' : 'grey'" size="small">
                        {{ value === 'active' ? 'Activo' : 'Inactivo' }}
                    </v-chip>
                </template>

            </v-data-table>
        </v-card>
        <QrCodeForm v-if="showModal"
                      v-model="showModal"
                      :element="selectedElement"
                      :routes="props.routes"
                      @added="reloadWithMessage"
                      @edited="reloadWithMessage"
                      @close-modal="closeModal"
                      @refresh-data="getData"
        >
        </QrCodeForm>
        <QrModal
            v-if="isModalOpen"
            v-model="isModalOpen"
            :element="elementQr" />
        <DeleteConfirmationModal
            v-model:show="dialogDelete"
            :item-name="deleteDialogItemName"
            :loading="isDeleting"
            @confirm="handleDelete"
            @cancel="closeDeleteModal"
        />
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
