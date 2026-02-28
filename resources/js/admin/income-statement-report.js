import { createApp } from 'vue';
import vuetify from '../app';
import IncomeStatementList from "@/components/admin/IncomeStatementList.vue";
import './../../sass/main.scss';
const app = createApp({
    components: { IncomeStatementList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#income-statement-container');
