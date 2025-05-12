import { createApp } from 'vue';
import vuetify from '../app';
import './../../sass/main.scss';
import HouseAttribute from '../components/admin/houseAttribute.vue';

const app = createApp({
    components: { HouseAttribute },
    methods: {
    }
});

app.use(vuetify)
app.mount('#house-attribute-container');
