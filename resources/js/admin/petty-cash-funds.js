import { createApp } from 'vue';
import vuetify from '../app';
import PettyCashFundsList from "@/components/admin/PettyCashFundsList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { PettyCashFundsList },
});

app.use(vuetify)
app.mount('#petty-cash-funds-container');
