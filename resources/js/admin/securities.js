import { createApp } from 'vue';
import vuetify from '../app';
import SecurityList from "@/components/admin/SecurityList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { SecurityList },
});

app.use(vuetify)
app.mount('#securities-container');
