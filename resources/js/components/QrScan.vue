<!-- src/components/QrScanner.vue -->
<template>
    <v-container>
        <v-row justify="center">
            <v-col cols="12" md="8" lg="6">
                <v-card class="pa-4">
                    <v-card-title class="text-center text-h5">{{ message }}</v-card-title>
                    <v-card-text>
                        <p class="text-center mb-4">Apunte la cámara al código QR del visitante.</p>
                        <div id="qr-reader"></div>
                        <v-alert v-if="scanError" type="error" dense outlined class="mt-4">
                            {{ scanError }}
                        </v-alert>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Html5QrcodeScanner, Html5QrcodeSupportedFormats } from 'html5-qrcode';

// Props para hacerlo más personalizable
const props = defineProps({
    message: {
        type: String,
        default: 'Marcación de Vigilancia',
    },
    qrboxSize: {
        type: Number,
        default: 250,
    }
});

// Emits para comunicar con el componente padre
const emit = defineEmits(['scan-success', 'scan-error']);

let html5QrcodeScanner = null;
const scanResult = ref(null);
const scanError = ref(null);

// Función que se ejecuta cuando el scanner tiene éxito
function onScanSuccess(decodedText, decodedResult) {
    // Para evitar múltiples escaneos, detenemos el scanner
    if (html5QrcodeScanner.isScanning) {
        html5QrcodeScanner.pause();
    }
    scanResult.value = decodedText;
    emit('scan-success', decodedText); // ¡La parte más importante!
}

// Función para el manejo de errores
function onScanFailure(error) {
    // No emitimos cada error de "QR no encontrado",
    // solo errores más serios si los hubiera.
    // console.warn(`Code scan error = ${error}`);
}

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

onMounted(startScanner);
const stopScanner = async () => {
    try {
        if (html5QrcodeScanner && html5QrcodeScanner.getState() !== 3 /* STOPPED */) {
            await html5QrcodeScanner.clear();
        }
    } catch (err) {
        console.error("Error al detener el scanner:", err);
    }
};

onBeforeUnmount(stopScanner);


</script>

<style scoped>
.qr-scanner-container {
    max-width: 500px;
    margin: auto;
}
.error-text {
    color: red;
}
</style>

<style scoped>
#qr-reader {
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}
</style>
