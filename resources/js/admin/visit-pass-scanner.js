import { createApp } from 'vue';
import vuetify from '../app';
import VisitPassScanner from "@/components/admin/VisitPassScanner.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { VisitPassScanner },
});

app.use(vuetify)
app.mount('#doorman-scanner-container');
