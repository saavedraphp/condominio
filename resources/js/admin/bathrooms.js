import { createApp } from 'vue';
import vuetify from '../app';
import Bathrooms from '../components/admin/Bathrooms.vue';

createApp(Bathrooms)
    .use(vuetify)
    .mount('#bathrooms-container');
