<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Html5QrcodeScanner, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import axios from 'axios';
import {formatDateForDisplay} from "@/utils/functions.js";

// --- State para el Scanner y la UI ---
const scanError = ref(null);
let html5QrcodeScanner = null;

// --- State para el Ingreso Manual ---
const manualCode = ref('');
const isSubmittingManual = ref(false);

// --- State para el Diálogo de Resultado ---
const showResultDialog = ref(false);
const loadingResult = ref(false);
const resultData = ref(null); // Almacenará los datos del pase si es exitoso
const resultError = ref(null); // Almacenará el mensaje de error
const remarks = ref(''); // Para las observaciones del vigilante
const accessLogId = ref(null); // Para saber qué registro de bitácora actualizar con las observaciones

// --- Lógica de Validación Centralizada ---
const validatePass = async (code) => {
    if (!code) return;

    // Reiniciar estados y mostrar diálogo de carga
    resultData.value = null;
    resultError.value = null;
    remarks.value = '';
    accessLogId.value = null;
    loadingResult.value = true;
    showResultDialog.value = true;

    // Detener el scanner para evitar lecturas múltiples
    await stopScanner();

    try {
        // La API debe manejar la creación del registro en access_logs
        const response = await axios.post('/security/validate-pass', { code });

        // Backend debería devolver el estado y los datos del pase
        // Ejemplo de respuesta exitosa: { status: 'SUCCESS', data: {..., log_id: 123} }
        if (response.data.status === 'SUCCESS') {
            resultData.value = response.data.data;
            accessLogId.value = response.data.data.log_id; // Guardamos el ID del log
        } else {
            // Para otros casos como 'EXPIRED' o 'NOT_FOUND', el backend debería enviar un mensaje claro
            resultError.value = response.data.message || 'El pase no es válido.';
        }

    } catch (err) {
        console.error("Error verificando el pase:", err);
        if (err.response && err.response.data && err.response.data.message) {
            resultError.value = err.response.data.message;
        } else {
            resultError.value = "Error de conexión. Intente de nuevo.";
        }
    } finally {
        loadingResult.value = false;
        isSubmittingManual.value = false;
    }
};

// --- Manejo del Scanner ---
const onScanSuccess = (decodedText, decodedResult) => {
    console.log(`Código escaneado = ${decodedText}`);
    validatePass(decodedText);
};

const onScanFailure = (error) => { /* Ignorar errores 'not found' */ };

const startScanner = () => {
    scanError.value = null;
    try {
        if (!html5QrcodeScanner || html5QrcodeScanner.getState() === 3 /* STOPPED */) {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader",
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    supportedScanTypes: [],
                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
                },
                false
            );
        }
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    } catch (err) {
        scanError.value = "No se pudo iniciar el scanner. Revise los permisos de la cámara.";
    }
};

const stopScanner = async () => {
    try {
        if (html5QrcodeScanner && html5QrcodeScanner.getState() !== 3 /* STOPPED */) {
            await html5QrcodeScanner.clear();
        }
    } catch (err) {
        console.error("Error al detener el scanner:", err);
    }
};

// --- Manejo del Ingreso Manual ---
const submitManualCode = () => {
    isSubmittingManual.value = true;
    // Puedes agregar validación de formato aquí si quieres
    validatePass(manualCode.value.toUpperCase());
};

// --- Guardar Observaciones ---
const submitRemarks = async () => {
    if (!accessLogId.value) return;
    try {
        // Endpoint para actualizar solo las observaciones del registro ya creado
        await axios.patch(`/security/access-logs/${accessLogId.value}`, {
            remarks: remarks.value
        });
        // Opcional: mostrar un snackbar de éxito
        closeDialogAndRestart(); // Cerrar después de guardar
    } catch (error) {
        console.error("Error guardando observaciones:", error);
        // Opcional: mostrar un snackbar de error
    }
};

// --- Ciclo de Vida y UI ---
onMounted(startScanner);
onBeforeUnmount(stopScanner);

const closeDialogAndRestart = () => {
    showResultDialog.value = false;
    manualCode.value = '';
    // Esperar un poco para que el diálogo se cierre antes de reiniciar la cámara
    setTimeout(startScanner, 300);
};

// --- Propiedades Computadas para el Diálogo ---
const isSuccess = computed(() => !!resultData.value);

const resultHeaderClass = computed(() => isSuccess.value ? 'bg-green-darken-2' : 'bg-red-darken-2');
const resultTitle = computed(() => {
    if (loadingResult.value) return 'Verificando...';
    return isSuccess.value ? 'Pase Válido' : 'Acceso Denegado';
});
const resultIcon = computed(() => isSuccess.value ? 'mdi-check-circle' : 'mdi-alert-octagon');

</script>

<style scoped>
#qr-reader {
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}
</style>

<template>
    <v-container>
        <v-row justify="center">
            <v-col cols="12" md="8" lg="6">
                <v-card class="pa-4">
                    <v-card-title class="text-center text-h5">Validar Pase de Visita</v-card-title>
                    <v-card-text>
                        <p class="text-center mb-4">Apunte la cámara al código QR del visitante.</p>
                        <div id="qr-reader"></div>
                        <v-alert v-if="scanError" type="error" dense outlined class="mt-4">
                            {{ scanError }}
                        </v-alert>

                        <v-divider class="my-6">
                            <span class="px-2">O INGRESE EL CÓDIGO</span>
                        </v-divider>

                        <v-text-field
                            v-model="manualCode"
                            label="Código de 12 dígitos (ej. AB12-CD34-EF56)"
                            variant="outlined"
                            placeholder="XXXX-XXXX-XXXX"
                            @keyup.enter="submitManualCode"
                            :disabled="isSubmittingManual"
                            clearable
                        ></v-text-field>
                        <v-btn
                            :loading="isSubmittingManual"
                            @click="submitManualCode"
                            color="primary"
                            block
                            size="large"
                        >
                            Verificar Código
                        </v-btn>
                    </v-card-text>
                </v-card>

                <!-- DIÁLOGO DE RESULTADO -->
                <v-dialog v-model="showResultDialog" max-width="500px" persistent>
                    <v-card>
                        <v-card-title class="d-flex align-center" :class="resultHeaderClass">
                            <v-icon :icon="resultIcon" start></v-icon>
                            <span>{{ resultTitle }}</span>
                        </v-card-title>

                        <v-card-text class="pt-4">
                            <!-- Estado de Carga -->
                            <div v-if="loadingResult" class="text-center pa-8">
                                <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                                <p class="mt-4">Consultando información...</p>
                            </div>

                            <!-- Resultado Exitoso -->
                            <div v-else-if="isSuccess && resultData">
                                <v-list lines="one">
                                    <v-list-item title="Propiedad" :subtitle="resultData.property.address"></v-list-item>
                                    <v-list-item title="Generado por" :subtitle="resultData.owner.name"></v-list-item>
                                    <v-list-item title="Teléfono" :subtitle="resultData.owner.phone"></v-list-item>
                                    <v-list-item title="Título del Pase" :subtitle="resultData.pass.title"></v-list-item>
                                    <v-list-item title="Detalle" :subtitle="resultData.pass.details"></v-list-item>
                                    <v-list-item title="Vigencia" :subtitle="`${formatDateForDisplay(resultData.pass.start_date)} - ${formatDateForDisplay(resultData.pass.end_date)}`"></v-list-item>
                                </v-list>

                                <div v-if="resultData.pass.members && resultData.pass.members.length > 0">
                                    <v-divider class="my-2"></v-divider>
                                    <p class="font-weight-bold">Integrantes:</p>
                                    <v-chip
                                        v-for="member in resultData.pass.members"
                                        :key="member.dni"
                                        class="ma-1"
                                        color="blue-grey"
                                        variant="tonal"
                                    >
                                        {{ member.name }} {{ member.last_name }}
                                    </v-chip>
                                </div>

                                <v-divider class="my-4"></v-divider>
                                <v-textarea
                                    v-model="remarks"
                                    label="Observaciones (opcional)"
                                    rows="2"
                                    variant="outlined"
                                    placeholder="Anotar cualquier incidencia o detalle relevante."
                                ></v-textarea>
                            </div>

                            <!-- Resultado con Error -->
                            <div v-else-if="resultError">
                                <v-alert type="error" variant="tonal" prominent class="ma-4">
                                    <h4 class="mb-2">No se pudo validar el pase</h4>
                                    <p>{{ resultError }}</p>
                                </v-alert>
                            </div>
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn text @click="closeDialogAndRestart">
                               Cerrar
                            </v-btn>
                            <v-btn v-if="isSuccess" color="primary" variant="flat" @click="submitRemarks">
                                Grabar Observaciones
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </v-col>
        </v-row>
    </v-container>
</template>
