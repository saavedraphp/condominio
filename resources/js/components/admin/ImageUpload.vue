<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

// Props:
// - modelValue: la URL de la imagen actual (se usa con v-model)
// - label: la etiqueta para el campo
const props = defineProps({
    modelValue: String,
    label: String,
});

// Emits:
// - update:modelValue: para actualizar el v-model en el componente padre
const emit = defineEmits(['update:modelValue']);

const loading = ref(false);
const error = ref(null);
const localPreview = ref(null); // Para la vista previa instantánea

// La URL a mostrar: o la vista previa local o la que viene del v-model
const imageUrl = computed(() => {
    // Si el usuario acaba de seleccionar un archivo, muestra la vista previa
    if (localPreview.value) {
        return localPreview.value;
    }
    // Si hay una URL en el modelo (de la BD), la muestra
    if (props.modelValue) {
        // Asegurarse de que si es una URL relativa, se resuelve correctamente
        const baseUrl = import.meta.env.VITE_APP_URL || ''; // Configura esto en tu .env.VITE
        return props.modelValue.startsWith('http') ? props.modelValue : `${baseUrl}${props.modelValue}`;
    }
    // Si no hay nada, no muestra imagen
    return null;
});


const onFileChange = (event) => {
    const file = event.target.files[0];
    if (!file) {
        return;
    }

    // Crear una URL local para la vista previa instantánea
    localPreview.value = URL.createObjectURL(file);

    // Llamar a la función para subir el archivo
    uploadFile(file);
};

const uploadFile = async (file) => {
    loading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await axios.post('/admin/settings/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        // Si la subida es exitosa, emitimos la nueva URL al componente padre
        emit('update:modelValue', response.data.url);

    } catch (err) {
        error.value = err.response?.data?.message || 'Error al subir la imagen.';
        // Si falla, revertimos la vista previa y el valor
        localPreview.value = null;
        emit('update:modelValue', props.modelValue); // Vuelve al valor original
    } finally {
        loading.value = false;
    }
};
</script>
<template>
    <div>
        <label class="v-label mb-2">{{ label }}</label>
        <v-card variant="tonal" class="pa-2">
            <v-img
                :src="imageUrl"
                :aspect-ratio="16/9"
                cover
                class="mb-2 rounded"
                v-if="imageUrl"
            >
                <template v-slot:placeholder>
                    <div class="d-flex align-center justify-center fill-height">
                        <v-progress-circular color="grey-lighten-4" indeterminate></v-progress-circular>
                    </div>
                </template>
            </v-img>

            <v-file-input
                :label="imageUrl ? 'Cambiar imagen' : 'Seleccionar imagen'"
                variant="outlined"
                density="compact"
                accept="image/*"
                prepend-icon="mdi-camera"
                :loading="loading"
                @change="onFileChange"
                hide-details
            ></v-file-input>

            <v-alert v-if="error" type="error" density="compact" class="mt-2">{{ error }}</v-alert>
        </v-card>
    </div>
</template>
