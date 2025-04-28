import { createApp } from 'vue';
import vuetify from '../app';
import ElectricityConsumptionList from "@/components/user/ElectricityConsumptionList.vue";

import './../../sass/main.scss';
const app = createApp({
    components: { ElectricityConsumptionList },
});

app.use(vuetify)
app.mount('#payment-service-container');
