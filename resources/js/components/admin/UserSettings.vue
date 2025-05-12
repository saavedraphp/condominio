<script setup>
import {computed, onMounted, ref} from "vue";
import House from "@/components/user/House.vue";
import axios from "axios";
import AssignedHouseForm from "@/components/admin/AssignedHouseForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import Snackbar from "@/components/Snackbar.vue";
import VehicleList from "@/components/admin/VehicleList.vue";

const props = defineProps({
    user: Object
});

const houses = ref([]);
const loading = ref(true)
const error = ref(null)
const showModal = ref(false)
const itemToDelete = ref(null);
const dialogDelete = ref(false);
const isDeleting = ref(false);
const mySnackbar = ref(null);

const TABS_KEYS = {
    'HOUSES': 'houses',
    'VEHICLES': 'vehicles',
};

const headers = ref([
    {title: 'Identificador', key: 'property_unit', align: 'start', sortable: true},
    {title: 'Direccion', key: 'address', sortable: true},
    {title: 'Propietario', key: 'pivot.is_owner', sortable: true},
    {title: 'Recidente', key: 'pivot.is_resident', sortable: true},
    {title: 'Gestor', key: 'pivot.is_manager', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const activeKey = ref(TABS_KEYS.HOUSES);

onMounted(() => {
    getHousesByUserId();
})

async function getHousesByUserId() {
    loading.value = true;

    try {
        const response = await axios.get(`/admin/user/${props.user.id}/house-assignments`);
        houses.value = response.data;
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const openDeleteModal = (item) => {
    itemToDelete.value = item;
    dialogDelete.value = true;
};

const gotToMembers = (item) => {
    window.location.href = `${window.location.origin}/admin/user/${props.user.id}/house/${item.id}/members/list`;
};

const deleteHouseAssignment = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`/admin/user/${props.user.id}/house-assignments/${id}`)

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

const closeDeleteModal = () => {
    dialogDelete.value = false;
    setTimeout(() => {
        itemToDelete.value = null;
        isDeleting.value = false;
    }, 300);
};

const closeModal = (() => {
    showModal.value = false;
});

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';
    return `${itemToDelete.value.property_unit} ID: ${itemToDelete.value.id}`;
});

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <v-container>
        <p v-if="loading">Cargango datos</p>
        <p v-else-if="error"> {{ error }}</p>
        <v-card>
            <v-toolbar density="compact" flat color="transparent">
                <v-btn
                    icon="mdi-arrow-left"
                    @click="goBack"
                    aria-label="Volver"
                ></v-btn>
                Lista de usuarios
                <!-- Opcional: Título en la barra de herramientas -->
                <!-- <v-toolbar-title class="text-body-1">Configuración</v-toolbar-title> -->

                <!-- Opcional: Espaciador para empujar elementos a la derecha -->
                <!-- <v-spacer></v-spacer> -->
                <!-- <v-btn icon="mdi-dots-vertical"></v-btn> -->
            </v-toolbar>
            <v-card-title class="text-start text-h6 justify-center py-4">
                Usuario : {{ props.user.name }}
            </v-card-title>
            <v-divider></v-divider>
            <v-tabs v-model="activeKey">
                <v-tab :value="TABS_KEYS.HOUSES">Casas e Integrantes</v-tab>
                <v-tab :value="TABS_KEYS.VEHICLES">Vehículos</v-tab>
            </v-tabs>
            <v-card-text>
                <v-window v-model="activeKey">
                    <v-window-item :value="TABS_KEYS.HOUSES">
                        <div class="d-flex justify-end mb-4">
                            <v-btn
                                color="primary"
                                prepend-icon="mdi-plus"
                                @click="showModal = true"
                            >
                                Asignar Casa
                            </v-btn>
                        </div>
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
                                <v-tooltip text="Integrantes">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon="mdi mdi-account-group"
                                            variant="text"
                                            color="primary"
                                            size="small"
                                            class="me-2"
                                            @click="gotToMembers(item)"
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
                            <template v-slot:item.pivot.is_owner="{ value }">
                                <v-chip :color="value ? 'success' : 'grey'" size="small">
                                    {{ value ? 'SI' : 'NO' }}
                                </v-chip>
                            </template>
                            <template v-slot:item.pivot.is_resident="{ value }">
                                <v-chip :color="value ? 'success' : 'grey'" size="small">
                                    {{ value ? 'SI' : 'NO' }}
                                </v-chip>
                            </template>
                            <template v-slot:item.pivot.is_manager="{ value }">
                                <v-chip :color="value ? 'success' : 'grey'" size="small">
                                    {{ value ? 'SI' : 'NO' }}
                                </v-chip>
                            </template>
                        </v-data-table>
                    </v-window-item>
                    <v-window-item :value="TABS_KEYS.VEHICLES">
                        <VehicleList
                            :user="user"
                        >
                        </VehicleList>
                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>
        <v-dialog v-model="showModal" persistent max-width="600px">
            <AssignedHouseForm
                :user-id="props.user.id"
                @added-assigned="getHousesByUserId"
                @close-modal="closeModal"
            >
            </AssignedHouseForm>
        </v-dialog>
        <DeleteConfirmationModal
            v-model:show="dialogDelete"
            :item-name="deleteDialogItemName"
            :loading="isDeleting"
            @confirm="deleteHouseAssignment"
            @cancel="closeDeleteModal"
        />
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>

<style scoped>

</style>
