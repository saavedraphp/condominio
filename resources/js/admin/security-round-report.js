import { createApp } from 'vue';
import vuetify from '../app';
import SecurityRoundReportList from "@/components/admin/SecurityRoundReportList.vue";
import './../../sass/main.scss';
const app = createApp({
    components: { SecurityRoundReportList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#security-round-report-container');
