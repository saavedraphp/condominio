import { createApp } from 'vue';
import vuetify from '../app';
import PaymentReportList from "@/components/admin/PaymentReportList.vue";
import './../../sass/main.scss';
const app = createApp({
    components: { PaymentReportList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#payment-report-container');
