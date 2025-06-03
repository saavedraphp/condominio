import { createApp } from 'vue';
import vuetify from '../app';
import PettyCashTransactionsList from "@/components/admin/PettyCashTransactionsList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { PettyCashTransactionsList },
});

app.use(vuetify)
app.mount('#petty-cash-funds-transactions-container');
