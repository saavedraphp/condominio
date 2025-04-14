import { createApp } from 'vue';
import vuetify from '../app';
import Dashboard from '../components/user/Dashboard.vue';

const app = createApp({
    components: { Dashboard },
    methods: {

    }
});

app.use(vuetify).mount('#dashboard-container');
