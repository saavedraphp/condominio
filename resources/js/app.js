import './bootstrap';
import { createVuetify } from 'vuetify';
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css'; // Opcional: iconos
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

import * as yup from 'yup';
yup.setLocale({
    // Usar 'mixed' para reglas generales
    mixed: {
        default: 'Campo inválido', // Mensaje por defecto si no hay otro más específico
        required: ('Este campo es obligatorio'), // Usa 'path' para obtener el nombre del campo
        // O una versión más simple si no necesitas el nombre del campo:
        // required: 'Este campo es obligatorio',
    },
    // Usar 'string' para reglas específicas de strings
    string: {
        email: 'Debe ser un correo electrónico válido',
        min: ({ min, path }) => `${path} debe tener al menos ${min} caracteres`,
        // min: ({ min }) => `Mínimo ${min} caracteres`, // Versión simple
    },
    // Puedes añadir otros tipos como 'number', 'date', etc.
    number: {
        min: ({ min }) => `Debe ser mayor o igual a ${min}`,
    }
});

// Configuración de Vuetify
const vuetify = createVuetify({
    theme: {
        defaultTheme: 'light',
    },
    components,
    directives
});

export default vuetify;
