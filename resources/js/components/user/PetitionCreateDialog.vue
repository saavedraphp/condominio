<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

// --- Props y Emits ---
const props = defineProps({
    modelValue: Boolean,
    apiBaseUrl: {
        type: String,
        required: true
    },
});
const emit = defineEmits(['update:modelValue', 'petition-created']);

// --- Estado ---
const dialog = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value);
    }
});

const formRef = ref(null);
const loading = ref(false);
const errorMessage = ref('');
const formData = ref({
    type: null,
    subject: '',
    description: '',
});

const petitionTypes = ['Reclamo', 'Sugerencia', 'Consulta', 'Otro'];

// --- Reglas de Validación ---
const rules = {
    required: value => !!value || 'Este campo es requerido.',
    maxLength: (max) => (value) => (value && value.length <= max) || `Máximo ${max} caracteres.`,
};

// --- Métodos ---
const resetForm = () => {
    formData.value = { type: null, subject: '', description: '' };
    errorMessage.value = '';
    if (formRef.value) {
        formRef.value.resetValidation();
    }
};

const closeDialog = () => {
        resetForm();
        dialog.value = false;
};

const submit = async () => {
    errorMessage.value = '';
    const { valid } = await formRef.value.validate();

    if (valid) {
        loading.value = true;
        try {
            const response = await axios.post(`${props.apiBaseUrl}`, formData.value);
            emit('petition-created', response.data);
            closeDialog();
        } catch (error) {
            console.error("Error creating petition:", error);
            if (error.response && error.response.data && error.response.data.message) {
                errorMessage.value = error.response.data.message;
            } else if (error.response && error.response.data && error.response.data.errors) {
                // Mostrar errores de validación si Laravel los devuelve así
                const validationErrors = Object.values(error.response.data.errors).flat();
                errorMessage.value = validationErrors.join(' ');
            }
            else {
                errorMessage.value = 'Ocurrió un error inesperado al crear la petición.';
            }
        } finally {
            loading.value = false;
        }
    }
};

// --- Watchers ---
// Resetear el formulario cuando el diálogo se abre
watch(dialog, (newValue) => {
    if (newValue) {
        resetForm();
    }
});

</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card :loading="loading">
            <v-card-title>
                <span class="text-h5">Crear Petición</span>
            </v-card-title>
            <v-card-text>
                <v-form ref="formRef" @submit.prevent="submit">
                    <v-select
                        v-model="formData.type"
                        :items="petitionTypes"
                        label="Tipo de Petición"
                        :rules="[rules.required]"
                        required
                        variant="outlined"
                        class="mb-3"
                    ></v-select>
                    <v-text-field
                        v-model="formData.subject"
                        label="Asunto"
                        :rules="[rules.required, rules.maxLength(50)]"
                        required
                        counter="50"
                        variant="outlined"
                        class="mb-3"
                    ></v-text-field>
                    <v-textarea
                        v-model="formData.description"
                        label="Detalle de la Petición"
                        :rules="[rules.required, rules.maxLength(200)]"
                        required
                        counter="200"
                        rows="5"
                        auto-grow
                        variant="outlined"
                    ></v-textarea>
                    <v-alert v-if="errorMessage" type="error" dense class="mt-4">
                        {{ errorMessage }}
                    </v-alert>
                </v-form>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="blue-darken-1" variant="text" @click="closeDialog" :disabled="loading">
                    Cancelar
                </v-btn>
                <v-btn color="blue-darken-1" variant="elevated" @click="submit" :loading="loading">
                    Enviar Petición
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

