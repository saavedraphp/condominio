import './bootstrap';
import { createVuetify } from 'vuetify';
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css'; // Opcional: iconos
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

// Configuración de Vuetify
const vuetify = createVuetify({
    theme: {
        defaultTheme: 'light',
    },
    components,
    directives
});

export default vuetify;
