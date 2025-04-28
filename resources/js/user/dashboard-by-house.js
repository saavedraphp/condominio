import { createApp } from 'vue';
import vuetify from '../app';
import DashboardByHouse from '../components/user/DashoardByHome.vue';

const app = createApp({
    components: { DashboardByHouse },
    methods: {

    }
});

app.use(vuetify).mount('#dashboard-by-house-container');
