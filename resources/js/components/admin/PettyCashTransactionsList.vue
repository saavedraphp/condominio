<script setup>
import { ref, reactive, onMounted, computed } from 'vue';

const props = defineProps({
    fundId: {
        type: Number,
        required: true,
    },
    urlBase: {
        type: String,
        required: true,
    },
});

// Simulación de fundId si no usas Vue Router
const fundId = ref(1); // Para probar. En una app real, esto vendría de la ruta.


const fundDetails = ref({}); // Detalles del fondo cargados de la API
const transactions = ref([]); // Transacciones del fondo
const totalTransactions = ref(0);
const loadingTransactions = ref(false);
const transactionsPerPage = ref(10);

const transactionHeaders = ref([
    { title: 'Fecha', key: 'transaction_date', sortable: true },
    { title: 'Descripción', key: 'description', sortable: true, width: '30%' },
    { title: 'Tipo', key: 'type', sortable: true },
    { title: 'Monto', key: 'amount', sortable: true, align: 'end' },
    { title: 'Imagen', key: 'file_path', sortable: false, align: 'center' },
    { title: 'Acciones', key: 'actions', sortable: false, align: 'center' },
]);

const showTransactionDialog = ref(false);
const editingTransaction = ref(null);
const savingTransaction = ref(false);

const transactionForm = reactive({
    id: null,
    transaction_date: new Date().toISOString().substr(0, 10),
    description: '',
    type: 'expense', // Default a egreso
    amount: null,
    file: null, // Para el v-file-input
    existing_file_path: null, // Para mostrar nombre de archivo existente al editar
});

// Simulación de carga de datos
const loadFundDetails = async () => {
    // API call para obtener detalles del fondo
    console.log(`Loading details for fund ${fundId.value}`);
    await new Promise(resolve => setTimeout(resolve, 500));
    // Datos de ejemplo
    const exampleFund = {
        id: fundId.value,
        white_label_id: 1,
        opening_date: '2024-01-01',
        opening_balance: 500.00,
        current_balance: 320.00, // Este se calcularía en el backend
        status: 'open', // 'open' o 'closed'
        closing_date: null,
        counted_closing_balance: null,
        // difference: -5.50, // (counted_closing_balance - current_balance at closing time)
        responsible_person: 'John Doe',
        description: 'Petty cash for office supplies January 2024',
    };
    // Si está cerrado, calcular la diferencia
    if (exampleFund.status === 'closed' && exampleFund.counted_closing_balance !== null) {
        // Supongamos que el current_balance en el momento del cierre era 325.50 y se contaron 320.00
        // Esto requiere que el 'current_balance_at_closing' se guarde o se pueda recalcular.
        // Para simplificar, asumimos que 'current_balance' es el balance *antes* de cerrar si está 'open',
        // y el balance *calculado en el momento del cierre* si está 'closed'.
        // Aquí, para el ejemplo, digamos que el current_balance era el teórico y counted_closing_balance el real.
        exampleFund.difference = exampleFund.counted_closing_balance - exampleFund.current_balance; // Esto no es del todo correcto, ver nota en modelo.
    }

    fundDetails.value = exampleFund;
};

const loadTransactions = async ({ page, itemsPerPage, sortBy }) => {
    if (!fundId.value) return;
    loadingTransactions.value = true;
    // API call para obtener transacciones del fondo
    console.log(`Loading transactions for fund ${fundId.value}, page: ${page}, itemsPerPage: ${itemsPerPage}`);
    await new Promise(resolve => setTimeout(resolve, 1000));
    const serverTransactions = [
        { id: 1, petty_cash_id: fundId.value, transaction_date: '2024-01-02', description: 'Stationery purchase', type: 'expense', amount: 50.00, file_path: 'receipts/stationery.pdf' },
        { id: 2, petty_cash_id: fundId.value, transaction_date: '2024-01-03', description: 'Urgent courier', type: 'expense', amount: 30.00, file_path: null },
        { id: 3, petty_cash_id: fundId.value, transaction_date: '2024-01-05', description: 'Reimbursement for parking', type: 'expense', amount: 100.00, file_path: 'receipts/parking.jpg' },
    ];
    transactions.value = serverTransactions;
    totalTransactions.value = serverTransactions.length;
    loadingTransactions.value = false;
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

const getStatusColor = (status) => {
    if (status === 'open') return 'green';
    if (status === 'closed') return 'red';
    return 'grey';
};

const handleFileUpload = (event) => {
    // El v-file-input ya asigna el archivo a transactionForm.file
    // Si necesitas hacer algo más cuando el archivo cambia, hazlo aquí.
    console.log("File selected:", transactionForm.file);
};


const editTransaction = (item) => {
    editingTransaction.value = item;
    transactionForm.id = item.id;
    transactionForm.transaction_date = item.transaction_date;
    transactionForm.description = item.description;
    transactionForm.type = item.type;
    transactionForm.amount = item.amount;
    transactionForm.file = null; // Reset file input
    transactionForm.existing_file_path = item.file_path;
    showTransactionDialog.value = true;
};

const saveTransaction = async () => {
    savingTransaction.value = true;

    // Para Laravel, si envías un archivo, necesitas FormData
    const formData = new FormData();
    formData.append('transaction_date', transactionForm.transaction_date);
    formData.append('description', transactionForm.description);
    formData.append('type', transactionForm.type);
    formData.append('amount', transactionForm.amount);
    if (transactionForm.file && transactionForm.file.length > 0) { // v-file-input puede devolver un array
        formData.append('file', transactionForm.file[0]);
    }
    formData.append('petty_cash_fund_id', fundId.value); // Asegúrate de que el backend lo asocie al fondo correcto
    // Si estás editando, necesitas enviar el ID de la transacción y el método PUT
    if (editingTransaction.value) {
        formData.append('_method', 'PUT'); // Para simular PUT con POST en HTML forms
        // La URL sería algo como `/api/petty-cash-transactions/${editingTransaction.value.id}`
        console.log('Updating transaction:', editingTransaction.value.id, Object.fromEntries(formData));
    } else {
        // La URL sería algo como `/api/petty-cash-transactions`
        console.log('Saving new transaction:', Object.fromEntries(formData));
    }

    // Simular API call
    await new Promise(resolve => setTimeout(resolve, 1500));

    // Después de guardar:
    // - Actualizar el current_balance en fundDetails.value (podría venir de la respuesta API)
    // - Recargar la lista de transacciones
    // - Recargar los detalles del fondo (para el balance actualizado)

    savingTransaction.value = false;
    closeTransactionDialog();
    await loadFundDetails(); // Recargar detalles del fondo
    await loadTransactions({ page: 1, itemsPerPage: transactionsPerPage.value, sortBy: [] }); // Recargar transacciones
};

const resetTransactionForm = () => {
    editingTransaction.value = null;
    transactionForm.id = null;
    transactionForm.transaction_date = new Date().toISOString().substr(0, 10);
    transactionForm.description = '';
    transactionForm.type = 'expense';
    transactionForm.amount = null;
    transactionForm.file = null;
    transactionForm.existing_file_path = null;
};

const closeTransactionDialog = () => {
    showTransactionDialog.value = false;
    resetTransactionForm();
};

const deleteTransaction = async (item) => {
    if (confirm(`Are you sure you want to delete transaction: ${item.description}?`)) {
        console.log('Deleting transaction:', item);
        // Lógica API para eliminar
        // URL: /api/petty-cash-transactions/${item.id} con método DELETE
        await new Promise(resolve => setTimeout(resolve, 1000)); // Simular API

        // Después de eliminar:
        // - Actualizar el current_balance en fundDetails.value
        // - Recargar la lista de transacciones
        // - Recargar los detalles del fondo
        await loadFundDetails();
        await loadTransactions({ page: 1, itemsPerPage: transactionsPerPage.value, sortBy: [] });
    }
};

const downloadFile = (filePath) => {
    // Lógica para descargar el archivo, ej: window.open(`/storage/${filePath}`, '_blank');
    // Asegúrate que la ruta sea accesible públicamente o tengas un endpoint para servir archivos protegidos.
    alert(`Download file: ${filePath}`);
};


onMounted(async () => {
    await loadFundDetails();
    // La carga inicial de transacciones se hará por el v-data-table-server
});

const goToBack = () => {
    window.history.back();
};

</script>
<template>
    <v-container fluid>
        <!-- Resumen del Fondo de Caja Chica -->
        <v-card class="mb-4">
            <v-toolbar density="compact" flat color="transparent">
                <v-btn
                    icon="mdi-arrow-left"
                    @click="goToBack"
                    aria-label="Volver"
                ></v-btn>
                Regresar
            </v-toolbar>
            <v-card-title>
                Caja Chica#{{ fundDetails.id }} - Detalle
            </v-card-title>
            <v-card-text>
                <v-row class="mt-3">
                    <v-col cols="12" md="3"><strong>Fecha de Apertura:</strong> {{ fundDetails.opening_date }}</v-col>
                    <v-col cols="12" md="3"><strong>Saldo Inicial:</strong> {{ formatCurrency(fundDetails.opening_balance) }}</v-col>
                    <v-col cols="12" md="3"><strong>Saldo Actual:</strong> {{ formatCurrency(fundDetails.current_balance) }}</v-col>
                    <v-col cols="12" md="3"><strong>Estado:</strong>
                        <v-chip :color="getStatusColor(fundDetails.status)" small>{{ fundDetails.status?.toUpperCase() || '' }}</v-chip>
                    </v-col>
                    <div v-if="false">
                    <v-col cols="12" v-if="fundDetails.responsible_person"><strong>Responsable:</strong> {{ fundDetails.responsible_person }}</v-col>
                    </div>
                    <v-col cols="12" v-if="fundDetails.description"><strong>Descripción:</strong> {{ fundDetails.description }}</v-col>
                    <v-col cols="12" md="3" v-if="fundDetails.status === 'closed'"><strong>Fecha de Cierre:</strong> {{ fundDetails.closing_date }}</v-col>
                    <v-col cols="12" md="3" v-if="fundDetails.status === 'closed'"><strong>Saldo de cierre contado:</strong> {{ formatCurrency(fundDetails.counted_closing_balance) }}</v-col>
                    <v-col cols="12" md="3" v-if="fundDetails.status === 'closed' && fundDetails.difference !== undefined">
                        <strong>Diferencia:</strong>
                        <span :class="fundDetails.difference >= 0 ? 'text-green' : 'text-red'">
               {{ formatCurrency(fundDetails.difference) }}
               {{ fundDetails.difference > 0 ? '(Surplus)' : fundDetails.difference < 0 ? '(Deficit)' : '' }}
             </span>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <!-- Gestión de Transacciones -->
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-icon start icon="mdi-format-list-bulleted"></v-icon>
                Transacciones de Caja Chica
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="showTransactionDialog = true" :disabled="fundDetails.status === 'closed'">
                    <v-icon start icon="mdi-plus"></v-icon>
                    Adicionar Transacción
                </v-btn>
            </v-card-title>

            <v-data-table-server
                :headers="transactionHeaders"
                :items="transactions"
                :items-length="totalTransactions"
                :loading="loadingTransactions"
                :items-per-page="transactionsPerPage"
                @update:options="loadTransactions"
                class="elevation-1"
            >
                <template v-slot:item.amount="{ item }">
          <span :class="item.type === 'expense' ? 'text-red' : 'text-green'">
            {{ item.type === 'expense' ? '-' : '+' }}{{ formatCurrency(item.amount) }}
          </span>
                </template>
                <template v-slot:item.type="{ item }">
                    <v-chip :color="item.type === 'expense' ? 'error' : 'success'" small>
                        {{ item.type?.toUpperCase() || '' }}
                    </v-chip>
                </template>
                <template v-slot:item.file_path="{ item }">
                    <v-btn v-if="item.file_path" icon="mdi-paperclip" variant="text" size="small" @click="downloadFile(item.file_path)"></v-btn>
                    <span v-else>-</span>
                </template>
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Edit Transaction" v-if="fundDetails.status !== 'closed'">
                        <template v-slot:activator="{ props }">
                            <v-btn icon="mdi-pencil" variant="text" color="primary" size="small" @click="editTransaction(item)" v-bind="props"></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Delete Transaction" v-if="fundDetails.status !== 'closed'">
                        <template v-slot:activator="{ props }">
                            <v-btn icon="mdi-delete" variant="text" color="error" size="small" @click="deleteTransaction(item)" v-bind="props"></v-btn>
                        </template>
                    </v-tooltip>
                </template>
            </v-data-table-server>
        </v-card>

        <!-- Dialog para Añadir/Editar Transacción -->
        <v-dialog v-model="showTransactionDialog" persistent max-width="700px">
            <v-card>
                <v-card-title>
                    <span class="headline">{{ editingTransaction ? 'Editar Transacción' : 'Adicionar Transacción' }}</span>
                </v-card-title>
                <v-card-text>
                    <v-container>
                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    label="Fecha de transacción*"
                                    v-model="transactionForm.transaction_date"
                                    type="date"
                                    required
                                    variant="outlined"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-select
                                    label="Tipo*"
                                    v-model="transactionForm.type"
                                    :items="['income', 'expense']"
                                    required
                                    variant="outlined"
                                ></v-select>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    label="Descripción*"
                                    v-model="transactionForm.description"
                                    variant="outlined"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    label="Monto*"
                                    v-model="transactionForm.amount"
                                    type="number"
                                    prefix="S/"
                                    variant="outlined"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-file-input
                                    label="Imagen*"
                                    v-model="transactionForm.file"
                                    show-size
                                    prepend-icon="mdi-paperclip"
                                    @change="handleFileUpload"
                                    accept=".jpg,.jpeg,.png"
                                    variant="outlined"
                                ></v-file-input>
                                <small v-if="editingTransaction && transactionForm.existing_file_path">Current file: {{ transactionForm.existing_file_path.split('/').pop() }}</small>
                            </v-col>
                        </v-row>
                    </v-container>
                    <small>*Campos Requeridos</small>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey"  variant="flat" @click="closeTransactionDialog">Cancel</v-btn>
                    <v-btn color="primary"  variant="flat" :loading="savingTransaction" @click="saveTransaction">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </v-container>
</template>
