import { createApp } from 'vue';
import vuetify from '../app';
import QrCodesList from "@/components/admin/QrCodesList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { QrCodesList },
});

app.use(vuetify)
app.mount('#qr-codes-container');
