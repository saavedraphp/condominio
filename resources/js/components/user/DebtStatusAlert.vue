<script setup>
import {computed} from 'vue';

const props = defineProps({
    totalDebt: {
        type: Number,
        required: true,
        default: 0,
    },
    hasPaymentArrangement: {
        type: Boolean,
        default: false,
    },
    showPayButton: {
        type: Boolean,
        default: true, // Hacemos que el botón se muestre por defecto
    },
    currency: {
        type: String,
        default: 'PEN', // Soles Peruanos
    },
    locale: {
        type: String,
        default: 'es-PE', // Formato de Perú
    }
});

// 2. Definimos los eventos que el componente puede "emitir" hacia el padre.
const emit = defineEmits(['pay-now']);

// 3. Usamos una propiedad computada para formatear la moneda.
// Esto es más limpio y eficiente que una función en el template.
const formattedDebt = computed(() => {
    return new Intl.NumberFormat(props.locale, {
        style: 'currency',
        currency: props.currency,
    }).format(props.totalDebt);
});

// 4. Función que se ejecuta al hacer clic en el botón y emite el evento.
function onPayNowClick() {
    emit('pay-now', {amount: props.totalDebt}); // Opcional: puedes pasar datos con el evento
}
</script>

<template>
    <div>
        <!-- Alerta cuando hay deuda -->
        <v-alert
            v-if="totalDebt > 0"
            type="warning"
            variant="tonal"
            border="start"
            prominent
            icon="mdi-alert-circle-outline"
            class="mb-4"
        >
            <template v-slot:title v-if="hasPaymentArrangement">
                Con arreglo de Pago
            </template>

            <div class="text-subtitle-1 font-weight-medium">Deuda Acumulada</div>
            <div class="text-h5 font-weight-bold text-warning">
                {{ formattedDebt }}
            </div>

            <template v-slot:append>
                <v-btn
                    v-if="showPayButton"
                    color="warning"
                    variant="elevated"
                    size="small"
                    @click="onPayNowClick"
                >
                    Pagar Ahora
                </v-btn>
            </template>
        </v-alert>

        <!-- Mensaje si no hay deuda -->
        <v-alert
            v-else
            type="success"
            variant="tonal"
            border="start"
            icon="mdi-check-circle-outline"
            density="compact"
            class="mb-4"
        >
            Estás al día con tus pagos. ¡Felicidades!
        </v-alert>
    </div>
</template>


<style scoped>
/* Puedes añadir estilos específicos para este componente aquí si es necesario */
</style>
