import { createApp } from 'vue';
import vuetify from '../app';
import BudgetSummaryReport from "@/components/user/BudgetSummaryReport.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { BudgetSummaryReport },
});

app.use(vuetify)
app.mount('#budget-summary-container');
