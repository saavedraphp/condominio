import { createApp } from 'vue';
import vuetify from '../app';
import VisitPassReportList from "@/components/admin/VisitPassReportList.vue";
import './../../sass/main.scss';
const app = createApp({
    components: { VisitPassReportList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#visit-pass-report-container');
