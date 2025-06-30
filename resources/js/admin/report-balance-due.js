import { createApp } from 'vue';
import vuetify from '../app';
import ReportBalanceDueList from '../components/admin/ReportBalanceDueList.vue';
import './../../sass/main.scss';
const app = createApp({
    components: { ReportBalanceDueList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#report-balance-due-container');
