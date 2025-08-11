<script setup>
import {computed, ref, watch} from 'vue';
import axios from 'axios';
//import QrcodeVue from 'qrcode.vue'; // Necesitas instalar: npm install qrcode.vue

// Props
const props = defineProps({
    passId: {
        type: Number,
        default: null,
    },
    modelValue: { // Para usar con v-model
        type: Boolean,
        default: false,
    },
});

// Emits
const emit = defineEmits(['update:modelValue']);

// Reactive state
const loading = ref(false);
const passData = ref(null);

const fetchPassData = async (id) => {
    console.log("Cargando datos del pase con ID:", id);
    loading.value = true;
    passData.value = null;
    try {
        // La URL debe ser la correcta según tu configuración de Ziggy o rutas
        const response = await axios.get(`/user/visit-passes/${id}/virtual-pass`);
        passData.value = response.data;
    } catch (error) {
        console.error("Error al cargar los datos del pase:", error);
        dialog.value = false; // Cierra el modal si hay un error
    } finally {
        loading.value = false;
    }
};

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});


// Observar cambios en el ID del pase para cargar los datos
watch(() => props.passId, (newId) => {
    if (newId) {
        fetchPassData(newId);
    }
}, {immediate: true});

const downloadPDF = () => {
    if (props.passId) {
        // Abre la URL de descarga en una nueva pestaña
        window.open(`/user/visit-passes/${props.passId}/download-pdf`, '_blank');
    }
};

const close = () => {
    dialog.value = false;
    passData.value = null; // Limpiar los datos del pase al cerrar
};
</script>
<template>
    <v-dialog v-model="dialog" max-width="500px" persistent>
        <v-card>
            <v-card-title class="text-h5 text-center primary--text">
                Pase de Visita Virtual
            </v-card-title>

            <v-card-text v-if="loading" class="text-center py-10">
                <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </v-card-text>

            <v-card-text v-else-if="passData">
                <v-list-item>
                    <v-list-item-title class="font-weight-bold">Título</v-list-item-title>
                    <v-list-item-subtitle>{{ passData.title }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                    <v-list-item-title class="font-weight-bold">Generado por</v-list-item-title>
                    <v-list-item-subtitle>{{ passData.creator_name }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                    <v-list-item-title class="font-weight-bold">Dirección</v-list-item-title>
                    <v-list-item-subtitle>{{ passData.house_address }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                    <v-list-item-title class="font-weight-bold">Vigencia</v-list-item-title>
                    <v-list-item-subtitle>{{ passData.starts_at }} - {{ passData.expires_at }}</v-list-item-subtitle>
                </v-list-item>
<!--                <div class="text-center my-4">
                    &lt;!&ndash; Usaremos una librería de QR para Vue para generarlo en el cliente &ndash;&gt;
                    <qrcode-vue :value="passData.access_code" :size="250" level="H" />
                </div>-->
                <!-- QR Code and Name Display -->
                <div v-if="passData.qr_code"  class="text-center my-4">

                        <v-img
                            :src="passData.qr_code"
                            alt="User QR Code"
                            contain
                            max-height="200"
                            max-width="200"
                            class="qr-code-image mx-auto"
                        ></v-img>

                    <div class="text-h5 mt-5 font-weight-medium"> <!-- Larger text for name -->
                        {{ passData.access_code }}
                    </div>
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="grey"  variant="flat"  @click="close">Cerrar</v-btn>
                <v-btn color="primary"  variant="flat" @click="downloadPDF">Descargar PDF</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
<style scoped>
.qr-code-image {
    display: block; /* Ensure image behaves like a block element within the card */
    margin: auto; /* Helps centering if card padding isn't enough */
}

.fill-height {
    height: 100%;
}
</style>
