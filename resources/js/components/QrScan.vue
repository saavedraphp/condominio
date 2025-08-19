<!-- src/components/QrScanner.vue -->
<template>
    <div class="qr-scanner-container">
        <div v-if="!scanResult">
            <p>{{ message }}</p>
            <div :id="qrReaderId" width="100%"></div>
        </div>
        <div v-if="scanError">
            <p class="error-text">Error: {{ scanError }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Html5QrcodeScanner } from 'html5-qrcode';

// Props para hacerlo más personalizable
const props = defineProps({
    message: {
        type: String,
        default: 'Apunta la cámara al código QR.',
    },
    qrboxSize: {
        type: Number,
        default: 250,
    }
});

// Emits para comunicar con el componente padre
const emit = defineEmits(['scan-success', 'scan-error']);

const qrReaderId = 'qr-reader';
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

onMounted(() => {
    html5QrcodeScanner = new Html5QrcodeScanner(
        qrReaderId,
        {
            fps: 10,
            qrbox: { width: props.qrboxSize, height: props.qrboxSize },
            // Solo soporta códigos QR para mejorar el rendimiento
            supportedScanTypes: [Html5QrcodeSupportedFormats.QR_CODE]
        },
        /* verbose= */ false
    );
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
});

onBeforeUnmount(() => {
    if (html5QrcodeScanner) {
        // Es muy importante limpiar el scanner para liberar la cámara
        html5QrcodeScanner.clear().catch(error => {
            console.error("Fallo al limpiar el scanner.", error);
        });
    }
});
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
