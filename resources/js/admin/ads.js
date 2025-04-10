import { createApp } from 'vue';
import vuetify from '../app';
import AdsList from '../components/admin/AdsList.vue';
import VueApexCharts from "vue3-apexcharts";


const app = createApp({
    components: { AdsList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#ads-container');
