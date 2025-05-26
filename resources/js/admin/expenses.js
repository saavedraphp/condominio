import { createApp } from 'vue';
import vuetify from '../app';
import ExpensesList from "@/components/admin/ExpensesList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { ExpensesList },
});

app.use(vuetify)
app.mount('#expenses-container');
