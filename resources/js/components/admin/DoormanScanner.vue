<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Html5QrcodeScanner, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import axios from 'axios'; // Asegúrate que axios esté configurado

const scanError = ref(null);
const showResultDialog = ref(false);
const loadingResult = ref(false);
const resultData = ref(null);
const resultError = ref(null);
let html5QrcodeScanner = null;


const resultHeaderClass = computed(() => {
    if (!resultData.value || !resultData.value.userInfo) return 'grey lighten-2';
    return resultData.value.userInfo.has_debt ? 'red darken-2 white--text' : 'green darken-2 white--text';
});

const resultIcon = computed(() => {
    if (!resultData.value || !resultData.value.userInfo) return 'mdi-information';
    return resultData.value.userInfo.has_debt ? 'mdi-alert-octagon' : 'mdi-check-circle';
});

const resultIconColor = computed(() => {
    return 'white'; // Si el fondo del header ya tiene color
});

const closeResultDialogAndRestartScanner = () => {
    showResultDialog.value = false;
    resultData.value = null;
    resultError.value = null;
    // Volver a iniciar el scanner para la próxima lectura (asegúrate que la función startScanner exista y funcione)
    if (html5QrcodeScanner && typeof startScanner === 'function') {
        startScanner();
    }
};

// --- Función llamada cuando el QR es leído ---
const onScanSuccess = async (decodedText, decodedResult) => {
    console.log(`Código escaneado = ${decodedText}`, decodedResult);

    // Detener el scanner una vez que se lee un código
    if (html5QrcodeScanner) {
        try {
            await html5QrcodeScanner.clear(); // Limpia el scanner (detiene la cámara)
            console.log("Scanner detenido.");
        } catch (err) {
            console.error("Error al detener el scanner:", err);
        }
    }


    // Mostrar el diálogo de resultado y cargar
    resultData.value = null;
    resultError.value = null;
    loadingResult.value = true;
    showResultDialog.value = true;

    // Llamar a la API del backend para verificar el ID
    try {
        // decodedText debería ser el ID del usuario
        const userId = decodedText;
        const response = await axios.get(`/admin/doorman/check-access/${userId}`);
        resultData.value = response.data;
        resultError.value = null;
    } catch (err) {
        console.error("Error verificando acceso:", err);
        resultData.value = null;
        if (err.response) {
            resultError.value = `Error ${err.response.status}: ${err.response.data.message || 'No se pudo verificar.'}`;
        } else {
            resultError.value = "Error de red o del servidor.";
        }
    } finally {
        loadingResult.value = false;
    }
};

// --- Función para manejar errores del scanner ---
const onScanFailure = (error) => {
    // Ignorar errores comunes como "QR code not found" si no quieres mostrarlos
    // console.warn(`Error de escaneo = ${error}`);
    // scanError.value = error; // Opcional: mostrar error en la UI
};

// --- Iniciar el scanner cuando el componente se monta ---
onMounted(() => {
    startScanner();
});

const startScanner = () => {
    scanError.value = null; // Limpiar errores previos
    try {
        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", // ID del elemento HTML donde se mostrará
            {
                fps: 10, // Frames por segundo para escanear
                qrbox: { width: 250, height: 250 }, // Tamaño del cuadro de escaneo (opcional)
                supportedScanTypes: [], // Usa [Html5QrcodeScanType.SCAN_TYPE_CAMERA] si quieres forzar solo cámara
                formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ] // Solo busca QR
            },
            false // verbose = false
        );
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        console.log("Scanner iniciado.");
    } catch (err) {
        console.error("No se pudo iniciar el scanner:", err);
        scanError.value = "No se pudo iniciar el scanner. Revisa los permisos de la cámara.";
    }
}

// --- Limpiar el scanner al desmontar el componente ---
onBeforeUnmount(async () => {
    if (html5QrcodeScanner) {
        try {
            // Asegurarse que el scanner está activo antes de intentar limpiarlo
            // La librería puede lanzar error si intentas limpiar algo ya detenido o no iniciado
            const state = html5QrcodeScanner.getState();
            if (state && state !== /*Html5QrcodeScannerState.NOT_STARTED*/ 1 && state !== /*Html5QrcodeScannerState.STOPPED*/ 3) {
                await html5QrcodeScanner.clear();
                console.log("Scanner limpiado al desmontar.");
            }
        } catch (err) {
            console.error("Error limpiando el scanner al desmontar:", err);
        }
    }
});

// --- Cerrar el diálogo y reiniciar el scanner ---
const closeResultDialog = () => {
    showResultDialog.value = false;
    resultData.value = null;
    resultError.value = null;
    // Volver a iniciar el scanner para la próxima lectura
    startScanner();
};

</script>

<style scoped>
#qr-reader {
    border: 1px solid #eee;
    border-radius: 8px;
}
/* Estilos adicionales si son necesarios */
</style>
<template>
    <v-container>
        <v-row justify="center">
            <v-col cols="12" md="8" lg="6">
                <v-card class="pa-4">
                    <v-card-title class="text-center">Escanear Código QR</v-card-title>
                    <v-card-text>
                        <!-- Contenedor para el visor de la cámara -->
                        <div id="qr-reader" style="width: 100%; max-width: 500px; margin: auto;"></div>

                        <div v-if="scanError" class="mt-4">
                            <v-alert type="error" dense outlined>
                                Error al escanear: {{ scanError }}
                            </v-alert>
                        </div>

                    </v-card-text>
                </v-card>
                <!-- Dialogo para mostrar el resultado -->
                <v-dialog v-model="showResultDialog" max-width="500px" persistent>
                    <v-card>
                        <v-card-title class="text-h5" :class="resultHeaderClass">
                            <v-icon left :color="resultIconColor" class="mr-2">{{ resultIcon }}</v-icon>
                            Información del Residente
                        </v-card-title>

                        <v-card-text class="pt-4">
                            <div v-if="loadingResult" class="text-center pa-5">
                                <v-progress-circular indeterminate color="primary" size="50"></v-progress-circular>
                                <p class="mt-3">Verificando acceso...</p>
                            </div>
                            <div v-else-if="resultData && resultData.userInfo">
                                <v-list-item>
                                    <v-list-item-content>
                                        <v-list-item-title class="text-h6">{{
                                                resultData.userInfo.name
                                            }}
                                        </v-list-item-title>
                                        <v-list-item-subtitle>Apartamento: {{
                                                resultData.userInfo.apartment || 'N/A'
                                            }}
                                        </v-list-item-subtitle>
                                    </v-list-item-content>
                                </v-list-item>

                                <v-divider class="my-3"></v-divider>

                                <v-alert
                                    dense
                                    outlined
                                    :type="resultData.userInfo.has_debt ? 'error' : 'success'"
                                    class="mt-3"
                                >
                                    {{ resultData.userInfo.debt_message }}
                                </v-alert>

                                <!-- Si quieres mostrar el monto específico si hay deuda -->
                                <p v-if="resultData.userInfo.has_debt" class="text-subtitle-1 mt-2">
                                    Monto adeudado: <strong>${{ resultData.userInfo.debt_amount.toFixed(2) }}</strong>
                                </p>

                            </div>
                            <div v-else-if="resultError">
                                <v-alert type="warning" dense outlined prominent>
                                    Error al verificar: {{ resultError }}
                                </v-alert>
                            </div>
                        </v-card-text>

                        <v-divider></v-divider>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="primary" text @click="closeResultDialogAndRestartScanner">
                                Escanear Otro
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </v-col>
        </v-row>
    </v-container>
</template>
