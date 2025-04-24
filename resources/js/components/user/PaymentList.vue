<script setup>
import {ref, onMounted, computed} from 'vue';
import axios from "axios";
import PaymentForm from "@/components/user/PaymentForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import Snackbar from "@/components/Snackbar.vue";

const props = defineProps({
    user: Object,
    house: Object
});

const headers = ref([
    {title: 'Año', key: 'year', align: 'left', sortable: true},
    {title: 'Monto Anual', key: 'totalAmount', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);


// --- Estados Reactivos ---
const loading = ref(false); // Para la tabla principal
const loadingDetails = ref(false); // Para la tabla del modal
const paymentSummary = ref([]); // Almacenará { year: number, totalAmount: number, details?: PaymentDetail[] }
const dialogVisible = ref(false);
const selectedYearData = ref(null); // Almacenará el objeto summary completo del año seleccionado
const mySnackbar = ref(null);

const isDownloading = ref(false);
const downloadError = ref(null);

const payments = ref([]);
const showModal = ref(false)
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false); // Para el estado de carga

const currentlyDownloading = ref(null); // Opcional: para saber qué botón específico está cargando

const selectedElement = ref({
    house_id: props.house.id
})


const fetchYearDetails = async (year) => {
    loadingDetails.value = true;
    await new Promise(resolve => setTimeout(resolve, 800)); // Simular delay
    const details = payments.value.filter(p => new Date(p.payment_date).getFullYear() === year)
        .sort((a, b) => new Date(b.payment_date) - new Date(a.payment_date)); // Ordenar por fecha descendente

    // Actualiza el objeto 'selectedYearData' con los detalles cargados
    if (selectedYearData.value && selectedYearData.value.year === year) {
        selectedYearData.value.details = details;
    }
    loadingDetails.value = false;
    return details; // Devuelve los detalles por si acaso
};


// Mostrar el modal con los detalles
const showYearDetails = async (summary) => {
    selectedYearData.value = {...summary, details: []}; // Copia el summary e inicializa detalles vacío
    dialogVisible.value = true;
    // Cargar (o recargar) los detalles solo cuando se abre el modal
    await fetchYearDetails(summary.year);
};

// Cerrar el modal
const closeDialog = () => {
    dialogVisible.value = false;
    selectedYearData.value = null; // Limpiar datos seleccionados al cerrar
};

const closeModal = (() => {
    showModal.value = false;
    selectedElement.value = null;
});


// Descargar el PDF consolidado del año (simulado)
const downloadYearPdf = (year) => {
    // Aquí iría la lógica real:
    // 1. Llamar a un endpoint del backend que genere el PDF para ese año.
    // 2. Recibir el archivo (blob) o una URL al archivo.
    // 3. Iniciar la descarga en el navegador.
    alert(`Funcionalidad "Descargar PDF ${year}" no implementada en este ejemplo.`);
};

// Formatear moneda (simple)
const formatCurrency = (value) => {
    if (value === null || value === undefined) {
        return '0.00';
    }

    const numericValue = Number(value);
    if (isNaN(numericValue)) {
        return '0.00';
    }

    try {
        return value.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    } catch (error) {
        console.error("Error formatting currency:", error, "Input:", value);
        return value.toFixed(2);
    }
};

// Formatear fecha (simple)
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        const userTimezoneOffset = date.getTimezoneOffset() * 60000;
        const localDate = new Date(date.getTime() + userTimezoneOffset); // Ajusta a la zona horaria local para mostrar
        return localDate.toLocaleDateString('es-ES', { // Formato dd/mm/yyyy
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    } catch (e) {
        console.error("Error formatting date:", dateString, e);
        return dateString; // Devuelve el original si falla
    }
};


async function getPayments() {
    loading.value = true;
    try {
        const response = await axios.get(`/user/house/${props.house.id}/payments/`);
        payments.value = response.data;

        const summary = payments.value.reduce((acc, payment) => {
            const year = new Date(payment.payment_date).getFullYear();
            if (!acc[year]) {
                acc[year] = {year: year, totalAmount: 0};
            }
            acc[year].totalAmount += parseFloat(payment.amount);
            return acc;
        }, {});
        paymentSummary.value = Object.values(summary).sort((a, b) => b.year - a.year); // Ordenar por año descendente
        // --- FIN: Simulación API ---
        loading.value = false;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

const handlePaymentAdded = (message) => {
    mySnackbar.value.show(message, 'success');
    getPayments();
};
const downloadPaymentFile = async (payment) => {
    const url = `/user/payments/${payment.id}/download`;
    currentlyDownloading.value = paymentId; // Opcional
    await handleAxiosDownload(url, `comprobante-${payment.id}.pdf`); // Fallback filename
    currentlyDownloading.value = null; // Opcional
};


const handleDownloadYearClick = async (year) => {
    currentlyDownloading.value = year; // Opcional
    await downloadYearlySummary(year);
    currentlyDownloading.value = null; // Opcional
}

/**
 * Inicia la descarga del resumen PDF anual.
 * @param {number} year El año para el resumen.
 */
const downloadYearlySummary = async (year) => {
    console.log(`Iniciando descarga PDF para año: ${year}`);
    const url = `/user/payments/download-year/${year}`;
    await handleAxiosDownload(url, `Comprobantes-${year}.pdf`); // Fallback filename
};

const handleAxiosDownload = async (url, fallbackFilename = 'download') => {
    isDownloading.value = true;
    downloadError.value = null;
    let objectUrl = null; // Para limpiar después

    try {
        const response = await axios.get(url, {
            responseType: 'blob', // ¡Importante! Esperamos datos binarios
            // Puedes añadir headers aquí si necesitas, ej. Authorization:
            // headers: { 'Authorization': `Bearer ${your_token}` }
        });

        // --- Éxito: Procesar el Blob ---
        const blob = new Blob([response.data], {type: response.headers['content-type']});

        // Extraer nombre de archivo del header Content-Disposition (si existe)
        let filename = fallbackFilename;
        const contentDisposition = response.headers['content-disposition'];
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename\*?=['"]?(?:UTF-\d['"]*)?([^;"']+)['"]?;?/i);
            if (filenameMatch && filenameMatch[1]) {
                // Decodificar si está codificado (ej. filename*=UTF-8''nombre%20con%20espacios.pdf)
                try {
                    filename = decodeURIComponent(filenameMatch[1]);
                } catch (e) {
                    // Si falla la decodificación, usa el valor crudo (menos común)
                    filename = filenameMatch[1];
                    console.warn("Could not decode filename from Content-Disposition, using raw value:", filename);
                }
            }
        }

        // Crear enlace temporal y simular click
        objectUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = objectUrl;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link); // Limpiar el DOM

        return null; // Indica éxito

    } catch (error) {
        // --- Manejo de Errores ---
        let errorMessage = 'Ocurrió un error desconocido durante la descarga.';

        if (error.response) {
            // El servidor respondió con un estado de error (4xx, 5xx)
            console.error('Error response:', error.response);
            // El error JSON está en el Blob, necesitamos leerlo
            if (error.response.data instanceof Blob) {
                try {
                    // Intenta leer el Blob como texto y luego parsearlo como JSON
                    const errorText = await error.response.data.text();
                    const errorJson = JSON.parse(errorText);
                    errorMessage = errorJson.message || `Error ${error.response.status}: Respuesta inesperada del servidor.`;
                } catch (parseError) {
                    console.error('Error parsing error blob:', parseError);
                    errorMessage = `Error ${error.response.status}: No se pudo leer el mensaje de error del servidor.`;
                }
            } else {
                // Si la respuesta no es un Blob (poco común con responseType: 'blob', pero posible)
                errorMessage = error.response.data?.message || `Error ${error.response.status}`;
            }

        } else if (error.request) {
            // La solicitud se hizo pero no se recibió respuesta (problema de red?)
            console.error('Error request:', error.request);
            errorMessage = 'No se pudo conectar con el servidor para iniciar la descarga.';
        } else {
            // Error al configurar la solicitud
            console.error('Error message:', error.message);
            errorMessage = `Error al preparar la descarga: ${error.message}`;
        }
        downloadError.value = errorMessage; // Almacenar mensaje para mostrar al usuario
        return errorMessage; // Devolver el mensaje de error

    } finally {
        isDownloading.value = false;
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl); // ¡Importante! Liberar memoria
        }
    }
}

const openModalEdit = (item) => {
    selectedElement.value = {...item};
    showModal.value = true;
};

const deleteModalItemName = computed(() => {
    if (!itemToDelete.value) return '';
    return `${itemToDelete.value.title} ID: ${itemToDelete.value.id}`;

});

const openDeleteModal = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deletePayment = async () => {
    try {
        if (!itemToDelete.value) return;
        const paymentId = itemToDelete.value.id;
        const houseId = itemToDelete.value.house_id;

        const response = await axios.delete(`/user/house/${houseId}/payments/${paymentId}`)

        if (response.data && response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            await getPayments();
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        mySnackbar.value.show(error.response?.data?.errors, 'error');
    } finally {
        closeDialog();
        closeDeleteModal();
    }
};
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    setTimeout(() => {
        itemToDelete.value = null;
        isDeleting.value = false;
    }, 300);
};


// --- Ciclo de Vida ---
onMounted(() => {
    getPayments();
});

</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-history" class="me-2"></v-icon>
                Historial de Pagos
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="showModal = true"
                    class="d-none d-sm-flex"
                >
                    Agregar pagos
                </v-btn>

                <v-btn
                    color="primary"
                    icon="mdi-plus"
                    @click="showModal = true"
                    class="d-sm-none"
                    aria-label="Agregar pagos"
                ></v-btn>
            </v-card-title>

            <v-divider></v-divider>
            <v-data-table v-show="paymentSummary.length"
                          :headers="headers"
                          :items="paymentSummary"
                          hide-default-footer
                          class="elevation-1"
                          dense
            >

                <template v-slot:item.totalAmount="{ value }">
                    S/ {{ formatCurrency(value) }}

                </template>
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Ver Detalle del Año">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-eye"
                                variant="text"
                                color="blue-darken-1"
                                size="small"
                                class="me-1"
                                @click="showYearDetails(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Descargar Resumen PDF del Año" v-if="false">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-file-pdf-box"
                                variant="text"
                                color="red-darken-1"
                                size="small"
                                :disabled="isDownloading"
                                :loading="isDownloading && currentlyDownloading === item.year"
                                @click="handleDownloadYearClick(item.year)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                </template>
            </v-data-table>
        </v-card>

        <!-- Modal para Detalles del Año -->
        <v-dialog v-model="dialogVisible" max-width="800px" persistent>
            <v-card>
                <v-card-title class="d-flex align-center justify-space-between">
                    <span class="headline">Detalle de Pagos - {{ selectedYearData?.year }}</span>
                    <v-btn icon="mdi-close" variant="text" @click="closeDialog" v-if="false" density="compact"></v-btn>
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text>
                    <v-table density="compact" fixed-header height="400px">
                        <thead>
                        <tr>
                            <th class="text-left">Fecha de Pago</th>
                            <th class="text-left">Archivo</th>
                            <th class="text-right">Monto</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="loadingDetails">
                            <td colspan="4" class="text-center">
                                <v-progress-circular indeterminate color="primary"></v-progress-circular>
                                <p>Cargando detalles...</p>
                            </td>
                        </tr>
                        <tr v-else-if="!selectedYearData?.details || selectedYearData.details.length === 0">
                            <td colspan="4" class="text-center">No hay pagos detallados para este año.</td>
                        </tr>
                        <tr v-for="payment in selectedYearData?.details" :key="payment.id">
                            <td>{{ formatDate(payment.payment_date) }}</td>
                            <td :title="payment.title">
                                <div class="truncate-text">
                                    <v-icon small class="me-1" v-if="payment.file_path">mdi-file-document</v-icon>
                                    {{ payment.title || 'N/A' }}
                                </div>
                            </td>
                            <td class="text-right">S/ {{ formatCurrency(payment.amount) }}</td>
                            <td class="text-center">
                                <v-tooltip text="Descargar Comprobante">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon="mdi-download"
                                            variant="text"
                                            color="green-darken-1"
                                            size="x-small"
                                            :disabled="!payment.file_path || isDownloading"
                                            :loading="isDownloading && currentlyDownloading === payment.id"
                                            @click="downloadPaymentFile(payment)"
                                        ></v-btn>
                                    </template>
                                </v-tooltip>
                                <v-tooltip text="Ver">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon="mdi-eye"
                                            variant="text"
                                            color="primary"
                                            size="small"
                                            class="me-2"
                                            @click="openModalEdit(payment)"
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
                                            @click="openDeleteModal(payment)"
                                        ></v-btn>
                                    </template>
                                </v-tooltip>
                            </td>
                        </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue-darken-1" variant="text" @click="closeDialog">
                        Cerrar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-alert
            v-if="downloadError"
            type="error"
            closable
            class="mt-4"
            @update:modelValue="downloadError = null"
        >
            {{ downloadError }}
        </v-alert>

        <!-- Puedes añadir un spinner global también -->
        <v-overlay :model-value="isDownloading" class="align-center justify-center" persistent>
            <v-progress-circular color="primary" indeterminate size="64"></v-progress-circular>
            <div class="text-center mt-2">Descargando...</div>
        </v-overlay>
        <v-dialog v-model="showModal" persistent max-width="600px">
            <PaymentForm
                :payment="selectedElement"
                :house="props.house"
                @payment-added="handlePaymentAdded"
                @close-modal="closeModal"
            >
            </PaymentForm>
        </v-dialog>
        <DeleteConfirmationModal
            v-model:show="showDeleteModal"
            :item-name="deleteModalItemName"
            :loading="isDeleting"
            @confirm="deletePayment"
            @cancel="closeDeleteModal"
        />
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>


<style scoped>
.v-card-title .v-icon {
    vertical-align: middle; /* Alinear mejor el icono del título */
}

/* Asegurar que la tabla dentro del modal no se desborde horizontalmente */
.v-dialog .v-table {
    overflow-x: auto;
}
</style>
