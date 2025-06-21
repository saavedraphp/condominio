import { createApp } from 'vue';
import vuetify from '../app';
import HouseMonthlyChargeList from "@/components/admin/HouseMonthlyChargeList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { HouseMonthlyChargeList },
});

app.use(vuetify)
app.mount('#house-charge-container');
