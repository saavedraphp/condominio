<script setup>
import {computed, ref, watch} from 'vue';
import axios from 'axios';
// Props
const props = defineProps({
    element: {
        type: Object,
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
const data = ref(null);

const fetchData = async (id) => {
    loading.value = true;
    data.value = null;
    try {
        // La URL debe ser la correcta según tu configuración de Ziggy o rutas
        const response = await axios.get(`/admin/qr-codes/${id}/qr`);
        data.value = response.data;
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
watch(() => props.element, (newId) => {
    if (newId.id) {
        fetchData(newId.id);
    }
}, {immediate: true});

const downloadPDF = () => {
    if (props.element.id) {
        // Abre la URL de descarga en una nueva pestaña
        window.open(`/admin/qr-codes/${props.element.id}/download-pdf`, '_blank');
    }
};

const close = () => {
    dialog.value = false;
    data.value = null; // Limpiar los datos del pase al cerrar
};
</script>
<template>
    <v-dialog v-model="dialog" max-width="500px" persistent>
        <v-card>
            <v-card-title class="text-h5 text-center primary--text">
                QR
            </v-card-title>

            <v-card-text v-if="loading" class="text-center py-10">
                <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </v-card-text>

            <v-card-text v-else-if="data">
                <v-list-item>
                    <v-list-item-title class="font-weight-bold">Título</v-list-item-title>
                    <v-list-item-subtitle>{{ data.title }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                    <v-list-item-title class="font-weight-bold">Descripción</v-list-item-title>
                    <v-list-item-subtitle>{{ data.description }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                    <v-list-item-title class="font-weight-bold">Código</v-list-item-title>
                    <v-list-item-subtitle>{{ data.code }}</v-list-item-subtitle>
                </v-list-item>
<!--                <div class="text-center my-4">
                    &lt;!&ndash; Usaremos una librería de QR para Vue para generarlo en el cliente &ndash;&gt;
                    <qrcode-vue :value="passData.access_code" :size="250" level="H" />
                </div>-->
                <!-- QR Code and Name Display -->
                <div v-if="data.qr_code" class="text-center my-4">

                        <v-img
                            :src="data.qr_code"
                            alt="User QR Code"
                            contain
                            max-height="200"
                            max-width="200"
                            class="qr-code-image mx-auto"
                        ></v-img>

                    <div class="text-h5 mt-5 font-weight-medium" v-if="false">
                        {{ data.code }}
                    </div>
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="grey"  variant="flat"  @click="close">Cerrar</v-btn>
                <v-btn color="primary"  variant="flat" @click="downloadPDF">Descargar PDF</v-btn>
                <v-btn
                    v-if="false"
                    color="primary"
                    class="mt-4"
                    :href="`/admin/qr-codes/${element.id}/download-image`"
                    download
                >
                    <v-icon left>mdi-download</v-icon>
                    Descargar QR
                </v-btn>
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
