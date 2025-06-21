<script setup>
import {ref, reactive, computed, onMounted} from 'vue';
import axios from "axios";
import {formatDate} from "@/utils/functions.js";
import Snackbar from "@/components/Snackbar.vue";

const props = defineProps({
    urlBase: {
        type: String,
        required: true
    },
});
// import { useRouter } from 'vue-router'; // Si navegas a otra página para detalles

// const router = useRouter();

const headers = ref([
    { title: 'Fecha de Apertura', key: 'opening_date', sortable: true },
    { title: 'Saldo Inicial', key: 'opening_balance', sortable: true, align: 'end' },
    { title: 'Saldo Actual', key: 'current_balance', sortable: false, align: 'end' }, // No sortable porque es calculado
    { title: 'Estado', key: 'status', sortable: true },
    { title: 'Fecha de Cierre', key: 'closing_date', sortable: true },
    { title: 'Saldo de Cierre', key: 'closing_balance', sortable: true, align: 'end' },
    { title: 'Acciones', key: 'actions', sortable: false, align: 'center' },
]);

const pettyCashFunds = ref([]); // Aquí irían los datos de la API
const totalItems = ref(0);
const loading = ref(false);
const itemsPerPage = ref(10);
const mySnackbar = ref(null);

const showOpenFundDialog = ref(false);
const editingFund = ref(null); // Guarda el item que se está editando, o null si es nuevo
const savingFund = ref(false);
const fundForm = reactive({
    id: null,
    opening_date: new Date().toISOString().substr(0, 10),
    opening_balance: null,
    responsible_person: '',
    description: '',
});

const showCloseFundDialog = ref(false);
const closingFund = ref(false);
const fundToClose = ref({}); // El fondo que se va a cerrar
const closeFundForm = reactive({
    closing_date: new Date().toISOString().substr(0, 10),
    counted_closing_balance: null,
});

// Simulación de carga de datos
/*const loadItems = async ({ page, itemsPerPage, sortBy }) => {
    loading.value = true;
    // Simulación de API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    // Aquí harías tu llamada a la API de Laravel
    // Ejemplo de datos:
    const serverItems = [
        { id: 1, white_label_id: 1, opening_date: '2024-01-01', opening_balance: 500.00, current_balance: 350.00, status: 'open', closing_date: null, closing_balance: null, responsible_person: 'Admin', description: 'Initial fund', has_transactions: true },
        { id: 2, white_label_id: 1, opening_date: '2023-12-01', opening_balance: 300.00, current_balance: 0.00, status: 'closed', closing_date: '2023-12-31', closing_balance: 10.50, responsible_person: 'Admin', description: 'December fund', has_transactions: true },
    ];
    pettyCashFunds.value = serverItems; // Asigna los datos obtenidos
    totalItems.value = serverItems.length; // Total real de la BD
    loading.value = false;
};*/

onMounted(() => {
    getPettyCash();
});

async function getPettyCash() {
    loading.value = true;
    try {
        const response = await axios.get(`${props.urlBase}`);
        pettyCashFunds.value = response.data.data.data.map(data => ({
            ...data,
            opening_date: formatDate(data.opening_date),
            closing_date: formatDate(data.closing_date),
        }));
    } catch (error) {
        console.log('Error al cargar los datos:', error);
        mySnackbar.value.show(error.response?.data?.message || 'Error al cargar los datos.', 'error');
    } finally {
        loading.value = false;
    }
}

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value);
};

const getStatusColor = (status) => {
    if (status === 'open') return 'green';
    if (status === 'closed') return 'red';
    return 'grey';
};

const openDetails = (item) => {
    console.log('View details for:', item);
    // Idealmente, navegar a una nueva ruta: router.push(`/admin/petty-cash/funds/${item.id}/transactions`);
    // O abrir un modal más grande o un drawer para mostrar transacciones.
    // Para este ejemplo, solo logueamos.
    window.location.href = `${window.location.origin}/admin/petty-cash/funds/${item.id}/list`;
};


const editFund = (item) => {
    editingFund.value = item;
    fundForm.id = item.id;
    fundForm.opening_date = item.opening_date;
    fundForm.opening_balance = item.opening_balance;
    fundForm.responsible_person = item.responsible_person;
    fundForm.description = item.description;
    showOpenFundDialog.value = true;
};

const saveFund = async () => {
    savingFund.value = true;
    // Aquí la lógica para guardar/actualizar en la API
    console.log('Saving fund:', fundForm);
    await new Promise(resolve => setTimeout(resolve, 1000)); // Simular API
    if (editingFund.value) {
        // Actualizar item en pettyCashFunds.value
    } else {
        // Añadir nuevo item a pettyCashFunds.value
    }
    showOpenFundDialog.value = false;
    savingFund.value = false;
    resetFundForm();
    // Recargar items: loadItems({ page: 1, itemsPerPage: itemsPerPage.value, sortBy: [] });
};

const resetFundForm = () => {
    editingFund.value = null;
    fundForm.id = null;
    fundForm.opening_date = new Date().toISOString().substr(0, 10);
    fundForm.opening_balance = null;
    fundForm.responsible_person = '';
    fundForm.description = '';
};

const deleteFund = async (item) => {
    if (confirm(`Are you sure you want to delete fund ${item.id}? This can only be done if it has no transactions.`)) {
        console.log('Deleting fund:', item);
        // Lógica API para eliminar
        await new Promise(resolve => setTimeout(resolve, 1000)); // Simular API
        // Recargar items
    }
};

const openCloseFundDialog = (item) => {
    fundToClose.value = item;
    closeFundForm.closing_date = new Date().toISOString().substr(0, 10);
    closeFundForm.counted_closing_balance = null; // Limpiar el campo
    showCloseFundDialog.value = true;
};

const calculatedDifference = computed(() => {
    if (fundToClose.value && closeFundForm.counted_closing_balance !== null) {
        return parseFloat(closeFundForm.counted_closing_balance) - parseFloat(fundToClose.value.current_balance);
    }
    return 0;
});

const balanceDifferenceColor = computed(() => {
    const diff = calculatedDifference.value;
    if (diff > 0) return 'text-green';
    if (diff < 0) return 'text-red';
    return '';
});

const confirmCloseFund = async () => {
    closingFund.value = true;
    // Aquí la lógica para cerrar el fondo en la API
    const payload = {
        fund_id: fundToClose.value.id,
        closing_date: closeFundForm.closing_date,
        counted_closing_balance: closeFundForm.counted_closing_balance,
    };
    console.log('Closing fund with payload:', payload);
    await new Promise(resolve => setTimeout(resolve, 1000)); // Simular API
    showCloseFundDialog.value = false;
    closingFund.value = false;
    // Recargar items
};

// Carga inicial
// loadItems({ page: 1, itemsPerPage: itemsPerPage.value, sortBy: [] });

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-icon start icon="mdi-cash-multiple"></v-icon>
                 
                Gestión de cajas chicas
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="showOpenFundDialog = true">
                    <v-icon start icon="mdi-plus"></v-icon>
                    Agregar Caja chica
                </v-btn>
            </v-card-title>

            <v-data-table
                :headers="headers"
                :items="pettyCashFunds"
                :loading="loading"
                class="elevation-1"
                item-value="id"
            >
                <template v-slot:item.opening_balance="{ item }">
                    {{ formatCurrency(item.opening_balance) }}
                </template>
                <template v-slot:item.current_balance="{ item }">
                    {{ formatCurrency(item.current_balance) }} <!-- Calculado -->
                </template>
                <template v-slot:item.closing_balance="{ item }">
                    {{ item.counted_closing_balance ? formatCurrency(item.counted_closing_balance) : '-' }}
                </template>
                <template v-slot:item.status="{ item }">
                    <v-chip :color="getStatusColor(item.status)" small>
                        {{ item.status.toUpperCase() }}
                    </v-chip>
                </template>
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Ver Detalle y Transacciones">
                        <template v-slot:activator="{ props }">
                            <v-btn icon="mdi-eye" variant="text" color="info" size="small" @click="openDetails(item)" v-bind="props"></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Cerrar Fondo" v-if="item.status === 'open'">
                        <template v-slot:activator="{ props }">
                            <v-btn icon="mdi-lock-check-outline" variant="text" color="warning" size="small" @click="openCloseFundDialog(item)" v-bind="props"></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Editar" v-if="item.status === 'open' && !item.has_transactions"> <!-- Solo si no tiene transacciones -->
                        <template v-slot:activator="{ props }">
                            <v-btn icon="mdi-pencil" variant="text" color="primary" size="small" @click="editFund(item)" v-bind="props"></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Eliminar" v-if="item.status === 'open' && !item.has_transactions"> <!-- Solo si no tiene transacciones -->
                        <template v-slot:activator="{ props }">
                            <v-btn icon="mdi-delete" variant="text" color="error" size="small" @click="deleteFund(item)" v-bind="props"></v-btn>
                        </template>
                    </v-tooltip>
                </template>
            </v-data-table>
        </v-card>

        <!-- Dialog para Abrir/Editar Fondo de Caja Chica -->
        <v-dialog v-model="showOpenFundDialog" persistent max-width="600px">
            <v-card>
                <v-card-title>
                    <span class="headline">{{ editingFund ? 'Editar el Fondo de Caja Chica' : 'Abrir nuevo fondo de caja chica' }}</span>
                </v-card-title>
                <v-card-text>
                    <v-container>
                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    label="Fecha de Apertura*"
                                    v-model="fundForm.opening_date"
                                    type="date"
                                    variant="outlined"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    label="Saldo Inicial*"
                                    v-model="fundForm.opening_balance"
                                    type="number"
                                    prefix="S/"
                                    variant="outlined"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" v-if="false">
                                <v-text-field
                                    label="Responsible Person (Optional)"
                                    v-model="fundForm.responsible_person"
                                    variant="outlined"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    label="Descripción/(Optional)"
                                    v-model="fundForm.description"
                                    rows="3"
                                    variant="outlined"
                                ></v-textarea>
                            </v-col>
                        </v-row>
                    </v-container>
                    <small>*Campos Requeridos</small>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="flat" @click="showOpenFundDialog = false">Cancelar</v-btn>
                    <v-btn color="primary"  variant="flat" :loading="savingFund" @click="saveFund">Grabar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog para Cerrar Fondo de Caja Chica -->
        <v-dialog v-model="showCloseFundDialog" persistent max-width="600px">
            <v-card>
                <v-card-title>
                    <span class="headline">Cerrar el fondo de caja chica</span>
                </v-card-title>
                <v-card-text>
                    <v-container>
                        <p><strong>Caja Chica:</strong> {{ fundToClose.id }}</p>
                        <p><strong>Saldo inicial:</strong> {{ formatCurrency(fundToClose.opening_balance) }}</p>
                        <p><strong>Saldo actual calculado:</strong> {{ formatCurrency(fundToClose.current_balance) }}</p>
                        <hr class="my-3">
                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    label="Fecha de Cierre*"
                                    v-model="closeFundForm.closing_date"
                                    type="date"
                                    required
                                    variant="outlined"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    label="Saldo de cierre contado*"
                                    v-model="closeFundForm.counted_closing_balance"
                                    type="number"
                                    prefix="$"
                                    required
                                    variant="outlined"
                                    hint="Efectivo real contado al cierre"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" v-if="closeFundForm.counted_closing_balance !== null">
                                <p :class="balanceDifferenceColor">
                                    <strong>Difference:</strong> {{ formatCurrency(calculatedDifference) }}
                                    <span v-if="calculatedDifference > 0">(Surplus)</span>
                                    <span v-if="calculatedDifference < 0">(Deficit)</span>
                                </p>
                            </v-col>
                        </v-row>
                    </v-container>
                    <small>*Campos Requeridos</small>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="flat" @click="showCloseFundDialog = false">Cancelar</v-btn>
                    <v-btn color="primary" variant="flat" :loading="closingFund" @click="confirmCloseFund">Confirmar Cierre</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
