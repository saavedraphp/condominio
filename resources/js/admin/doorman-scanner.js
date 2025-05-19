import { createApp } from 'vue';
import vuetify from '../app';
import DoormanScanner from "@/components/admin/DoormanScanner.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { DoormanScanner },
});

app.use(vuetify)
app.mount('#doorman-scanner-container');
