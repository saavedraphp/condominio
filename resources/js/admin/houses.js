import { createApp } from 'vue';
import vuetify from '../app';
import HousesList from '../components/admin/HousesList.vue';

const app = createApp({
    components: { HousesList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#houses-container');
