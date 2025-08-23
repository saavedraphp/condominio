<script setup>

import {computed, onMounted, ref} from "vue";
import axios from "axios";
import AnnualBudgetForm from "@/components/admin/AnnualBudgetForm.vue";
import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import OtherExpensesForm from "@/components/admin/OtherExpensesForm.vue";
import {formatDate, formattedMoney} from "@/utils/functions.js";

const pros = defineProps({
    routes: {
        type: Object,
        required: true
    },
});

const mySnackbar = ref(null);

const headers = ref([
    {title: 'Gasto', key: 'title', align: 'start', sortable: true},
    {title: 'Detalle', key: 'description', sortable: true},
    {title: 'Total', key: 'amount', sortable: true},
    {title: 'Fecha', key: 'date_format', sortable: true},
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
    return `${itemToDelete.value.title} (ID: ${itemToDelete.value.id})`;
});

async function getOtherExpenses() {
    loading.value = true;
    try {
        const response = await axios.get(`${pros.routes['base']}`, {
            params: {
                budget_scope: pros.budgetScope
            }
        });
        annualBudgets.value = response.data.map(item => ({
            ...item,
            date_format: item.date ? formatDate(item.date) : null, // Aseguramos que la fecha sea un objeto Date
        }));

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const openModalEdit = (item) => {
    selectedElement.value = JSON.parse(JSON.stringify(item));
    showModal.value = true;
};

const deleteOtherExpense = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`${pros.routes.base}/${id}`)

        if (response.data && response.data.success) {
            await getOtherExpenses();
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
    getOtherExpenses();
};

const reloadData = () => {
    getOtherExpenses();
};

onMounted(() => {
    getOtherExpenses();
})
</script>

<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-briefcase-check-outline"></v-icon>
                 
                Gastos para Islas Cerdeñas
                <v-spacer></v-spacer>

                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="openModalCreate"
                >
                    Agregar Gasto
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-data-table v-show="annualBudgets.length"
                          :headers="headers"
                          :items="annualBudgets"
                          class="elevation-1"
                          dense
            >
                <template v-slot:item.amount="{ value }">
                    <span>{{ formattedMoney(value) }}</span>
                </template>
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
            <OtherExpensesForm v-if="showModal"
                               v-model="showModal"
                               :expense="selectedElement"
                               :routes="pros.routes"
                               @other-expenses-saved="reloadWithMessage"
                               @refresh-data="reloadData"
            />
            <DeleteConfirmationModal
                v-model:show="dialogDelete"
                :item-name="deleteDialogItemName"
                :loading="isDeleting"
                @confirm="deleteOtherExpense"
                @cancel="closeDeleteModal"
            />
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>

<style scoped>

</style>
