import { createApp } from 'vue';
import vuetify from '../app';
import ExpenseReportList from "@/components/admin/ExpenseReportList.vue";
import './../../sass/main.scss';
const app = createApp({
    components: { ExpenseReportList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#expenses-report-container');
