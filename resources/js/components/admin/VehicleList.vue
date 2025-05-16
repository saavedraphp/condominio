<script setup>
import {computed, onMounted, ref} from "vue";
import axios from "axios";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import Snackbar from "@/components/Snackbar.vue";
import VehicleForm from "@/components/admin/VehicleForm.vue";

const props = defineProps({
    user: Object,
    house: Object,
    isAdmin: {
        type: Boolean,
        default: false
    }
});

let apiBaseMemberUrl = `${window.location.origin}/admin/user/${props.user.id}/vehicles`;
const loading = ref(true)
const showModal = ref(false)
const vehicles = ref([])

const selectedVehicle = ref(null);
const dialogDeleteVisible = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);
const mySnackbar = ref(null);

const header = computed(() => {
    const baseHeaders = [
        { title: 'Marca', key: 'brand' },
        { title: 'Modelo', key: 'model' },
        { title: 'Número de placa', key: 'plate_number' },
    ];

    if (props.isAdmin) {
        return [
            ...baseHeaders,
            { title: "Acciones", key: "actions", sortable: false },
        ];
    }

    return baseHeaders;
});

async function getVehicles() {
    loading.value = true;
    try {
        let url = props.isAdmin
            ? apiBaseMemberUrl
            : `/user/vehicles/`;
        const response = await axios.get(`${url}`);
        vehicles.value = response.data;
    } catch (error) {
        error.value = 'Error al obtener los vehículos';
        console.error(error);
    } finally {
        loading.value = false;
    }
}

const showModalVehicle = (member) => {
    selectedVehicle.value = {...member};
    showModal.value = true;
};

const openDeleteDialog = (item) => {
    itemToDelete.value = item;
    dialogDeleteVisible.value = true;
};

const closeDeleteDialog = () => {
    dialogDeleteVisible.value = false;
    setTimeout(() => {
        itemToDelete.value = null;
    }, 300);
};

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';

    return itemToDelete.value.plate_number;
});

const deleteVehicle = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`${apiBaseMemberUrl}/${id}`)

        if (response.data && response.data.success) {
            vehicles.value = vehicles.value.filter(vehicle => vehicle.id !== id);
            mySnackbar.value.show(response.data.message, 'success');

        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        console.error('Error al eliminar el vehículo:', error);
        const errorMessage = error.response?.data?.message || 'Ocurrió un error al eliminar.';
        mySnackbar.value.show(`Error: ${errorMessage}`, 'error');
    } finally {
        closeDeleteDialog();
    }
};

onMounted(() => {
    getVehicles();
})
</script>

<template>
    <div class="d-flex justify-end mb-4">
        <v-btn
            v-show="props.isAdmin"
            color="primary"
            prepend-icon="mdi-plus"
            @click="showModal = true"
        >
            Agregar Vehículo
        </v-btn>
    </div>
    <v-data-table
        :headers="header"
        :items="vehicles"
        class="elevation-1"
        density="compact"
    >
        <template v-slot:item.name="{ item }">
            <v-tooltip location="top">
                <template v-slot:activator="{ props }">
                    <span v-bind="props" class="truncate-text">
                        {{ item.model }}
                    </span>
                </template>
                <span>{{ item.model }}</span>
            </v-tooltip>
        </template>
        <template v-slot:item.actions="{ item }">
            <div class="d-flex align-center">
                <v-tooltip text="Editar">
                    <template v-slot:activator="{ props }">
                        <v-btn
                            v-bind="props"
                            icon="mdi-pencil"
                            variant="text"
                            color="primary"
                            size="small"
                            class="me-2"
                            @click="showModalVehicle(item)"
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
                            @click="openDeleteDialog(item)"
                        ></v-btn>
                    </template>
                </v-tooltip>
            </div>
        </template>
    </v-data-table>
    <VehicleForm
        v-model="showModal"
        :vehicle="selectedVehicle"
        :user="user"
        :url-base="apiBaseMemberUrl"
        @vehicle-created="getVehicles"
        @vehicle-edited="getVehicles"
    />
    <DeleteConfirmationModal
        v-model:show="dialogDeleteVisible"
        :item-name="deleteDialogItemName"
        :loading="isDeleting"
        @confirm="deleteVehicle"
        @cancel="closeDeleteDialog"
    />
    <Snackbar ref="mySnackbar"/>
</template>
