import { createApp } from 'vue';
import vuetify from '../app';
import BalanceAssociateReportList from '../components/admin/BalanceAssociateReportList.vue';
import './../../sass/main.scss';
const app = createApp({
    components: { BalanceAssociateReportList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#balance-by-associate-report-container');
