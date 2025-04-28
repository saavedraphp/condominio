import { createApp } from 'vue';
import vuetify from '../app';
import HouseList from '../components/user/HouseList.vue';

const app = createApp({
    components: { HousesList: HouseList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#houses-container');
