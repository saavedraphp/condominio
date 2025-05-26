import { createApp } from 'vue';
import vuetify from '../app';
import AnnualBudgetList from "@/components/admin/AnnualBudgetList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { AnnualBudgetList },
});

app.use(vuetify)
app.mount('#annual-budget-container');
