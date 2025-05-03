import { createApp } from 'vue';
import vuetify from '../app';
import HousesList from '../components/admin/HousesList.vue';
import './../../sass/main.scss';
const app = createApp({
    components: { HousesList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#houses-container');
