<script setup>

import {computed, onMounted, ref} from "vue";
import axios from "axios";
import AnnualBudgetForm from "@/components/admin/AnnualBudgetForm.vue";
import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";

const pros = defineProps({
    urlBase: {
        type: String,
        required: true
    },
});

const mySnackbar = ref(null);

const headers = ref([
    {title: 'Presupuesto', key: 'budget_type.name', align: 'start', sortable: true},
    {title: 'Año', key: 'year', sortable: true},
    {title: 'Monto', key: 'amount', sortable: true},
    {title: '% gastado', key: 'percentage_spent', sortable: true},
    {title: 'Saldo', key: 'remaining_amount', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const annualBudgets = ref([]);
const loading = ref(true);
const search = ref('Buscando resultados');
const showModal = ref(false)
const dialogDelete = ref(false);
const isDeleting = ref(false);
const itemToDelete = ref(null);
const selectedElement = ref(null)

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';
    return `${itemToDelete.value.budget_type.name} ${itemToDelete.value.year}`;
});

async function getAnnualBudget() {
    loading.value = true;

    try {
        const response = await axios.get(`${pros.urlBase}/`);
        annualBudgets.value = response.data;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const openModalEdit = (item) => {
    selectedElement.value = {...item};
    showModal.value = true;
};

const deleteAnnualBudget = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`${pros.urlBase}/${id}`)

        if (response.data && response.data.success) {
            annualBudgets.value = annualBudgets.value.filter(element => element.id !== id);
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

const openModalCreate = () => {
    selectedElement.value = null;
    showModal.value = true;
};

const closeDeleteModal = () => {
    dialogDelete.value = false;
    setTimeout(() => {
        itemToDelete.value = null;
        isDeleting.value = false;
    }, 300);
};

const openDeleteModal = (item) => {
    itemToDelete.value = item;
    dialogDelete.value = true;
};

const reloadWithMessage = (message) => {
    mySnackbar.value.show(message, 'success');
    getAnnualBudget();
};

onMounted(() => {
    getAnnualBudget();
})
</script>

<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-cash-clock"></v-icon>
                 
                Gestión de presupuestos anuales
                <v-spacer></v-spacer>

                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="openModalCreate"
                >
                    Agregar Presupuesto
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-data-table v-show="annualBudgets.length"
                          :headers="headers"
                          :items="annualBudgets"
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
            </v-data-table>
            <AnnualBudgetForm v-if="showModal"
                v-model="showModal"
                :element="selectedElement"
                :url-base="urlBase"
                @annual-budget-created="reloadWithMessage"
                @annual-budget-edited="reloadWithMessage"
            />
            <DeleteConfirmationModal
                v-model:show="dialogDelete"
                :item-name="deleteDialogItemName"
                :loading="isDeleting"
                @confirm="deleteAnnualBudget"
                @cancel="closeDeleteModal"
            />
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>

<style scoped>

</style>
