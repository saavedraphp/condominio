<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Snackbar from "@/components/Snackbar.vue"; // Asegúrate de tener axios instalado

const props = defineProps({
    routes : {
        type: Object,
        required: true,
    }
})
// Estado del formulario
const formData = ref({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const loading = ref(false);
const errors = ref({}); // Para almacenar errores de validación del backend

const mySnackbar = ref(null);

const submit = async () => {
    loading.value = true;
    errors.value = {}; // Limpiar errores previos

    try {
        const response = await axios.post(`${props.routes.base}`, formData.value);

        if (response.data.success) {
            mySnackbar.value.show('¡Contraseña cambiada con éxito!','success');

            formData.value = {
                current_password: '',
                new_password: '',
                new_password_confirmation: '',
            };
        }
    } catch (error) {
        // Manejo de errores de validación (código 422)
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
            mySnackbar.value.show('Por favor, corrige los errores en el formulario.','error');
        } else {
            mySnackbar.value.show('Ocurrió un error inesperado. Inténtalo de nuevo.','error');
            console.error('Error al cambiar contraseña:', error);
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <v-container>
    <v-card>
        <v-card-title>Cambiar Contraseña</v-card-title>
        <v-spacer></v-spacer>
        <v-card-text>
            <v-form @submit.prevent="submit">
                <!-- Campo para la contraseña actual -->
                <v-text-field
                    v-model="formData.current_password"
                    label="Contraseña Actual"
                    type="password"
                    :error-messages="errors.current_password"
                    variant="outlined"
                    required
                    class="mb-4"
                ></v-text-field>

                <!-- Campo para la nueva contraseña -->
                <v-text-field
                    v-model="formData.new_password"
                    label="Nueva Contraseña"
                    type="password"
                    :error-messages="errors.new_password"
                    variant="outlined"
                    required
                    class="mb-4"
                ></v-text-field>

                <!-- Campo para confirmar la nueva contraseña -->
                <v-text-field
                    v-model="formData.new_password_confirmation"
                    label="Confirmar Nueva Contraseña"
                    type="password"
                    variant="outlined"
                    required
                ></v-text-field>

                <v-btn type="submit" color="primary" :loading="loading">
                    Cambiar Contraseña
                </v-btn>
            </v-form>
        </v-card-text>
    </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>

