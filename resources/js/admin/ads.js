import { createApp } from 'vue';
import vuetify from '../app';
import AdsList from '../components/admin/AdsList.vue';

import './../../sass/main.scss';

const app = createApp({
    components: { AdsList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#ads-container');
