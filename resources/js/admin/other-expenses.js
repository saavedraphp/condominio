import { createApp } from 'vue';
import vuetify from '../app';
import OtherExpensesList from "@/components/admin/OtherExpensesList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { OtherExpensesList },
});

app.use(vuetify)
app.mount('#other-expenses-container');
