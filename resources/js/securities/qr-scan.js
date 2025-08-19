import { createApp } from 'vue';
import vuetify from '../app';
import ScanSecurity from "@/components/securities/ScanSecurity.vue";
import './../../sass/main.scss';

const app = createApp({
    components: { ScanSecurity },
});

app.use(vuetify)
app.mount('#qr-scan-container');
