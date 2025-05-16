import { createApp } from 'vue';
import vuetify from '../app';
import VehicleList from "@/components/user/VehicleList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: {VehicleList },
});

app.use(vuetify)
app.mount('#vehicles-container');
