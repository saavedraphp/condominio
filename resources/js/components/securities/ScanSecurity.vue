<script setup>
import { ref, shallowRef, defineAsyncComponent } from 'vue';
import QrScan from '@/components/QrScan.vue';
import axios from 'axios';

// --- Componentes de Resultado (Cargados de forma asíncrona) ---
// Esto mejora el rendimiento, ya que solo se carga el componente que se necesita.
/*
const VisitorPassResult = defineAsyncComponent(() => import('@/components/results/VisitorPassResult.vue'));
*/

const ActivityLogResult = defineAsyncComponent(() => import('@/components/ActivityLogResult.vue'));

// Añade más componentes de resultado aquí...

// --- Lógica del componente ---
const manualCode = ref('');
const resultComponent = shallowRef(null); // Usamos shallowRef para componentes
const resultData = ref(null);

// El "cerebro" que decide qué hacer
const handleScan = async (qrContent) => {
    console.log('Contenido del QR escaneado:', qrContent);

    try {
        const data = JSON.parse(qrContent);
        console.log('Datos parseados del QR:', data);
        // Hacemos una llamada a un único endpoint en el backend que maneje la lógica
        const response = await axios.post('/security/qr-handler', data);

        // Despachador en el Frontend
        switch (data.type) {
            case 'VISIT_PASS':
                resultData.value = response.data; // Los datos que vienen del backend
                resultComponent.value = VisitorPassResult;
                break;
            case 'PATROL_CHECKPOINT':
                resultData.value = response.data.data;
                resultComponent.value = ActivityLogResult;
                break;
            default:
                console.error('Tipo de QR desconocido:', data.type);
            // Aquí podrías mostrar un componente de error genérico
            // resultComponent.value = ErrorResult;
            // resultData.value = { message: 'El código QR no es válido.' };
        }
    } catch (error) {
        console.error('Error al procesar el QR:', error);
        // Manejar el caso de que el QR no sea un JSON válido o la API falle
    }
};

const handleManualInput = () => {
    if (!manualCode.value) return;

    // Simulamos el mismo formato JSON que generaría el QR
    const qrContent = JSON.stringify({
        type: 'VISIT_PASS',
        payload: {
            code: manualCode.value
        }
    });
    handleScan(qrContent);
};

const resetScanner = () => {
    resultComponent.value = null;
    resultData.value = null;
    manualCode.value = '';
};
</script>
<template>
    <v-container>
                <!-- Componente reutilizable para escanear -->
                <QrScan v-if="!resultComponent" @scan-success="handleScan" />

                <!-- Aquí se mostrará el resultado dinámicamente -->
                <div v-else>
                    <!-- Usamos el componente dinámico de Vue -->
                    <component :is="resultComponent" :data="resultData" @reset="resetScanner" />
                </div>

                <!-- Opción para ingreso manual -->
                <div v-if="false" class="mt-4">
                    <p class="text-center">O INGRESE EL CÓDIGO MANUALMENTE</p>
                    <v-text-field
                        v-model="manualCode"
                        label="Código de 12 dígitos (ej. AB12-CD34-EF56)"
                        outlined
                    ></v-text-field>
                    <v-btn block color="primary" @click="handleManualInput">Verificar Código</v-btn>
                </div>
    </v-container>
</template>
