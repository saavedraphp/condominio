import { createApp } from 'vue';
import vuetify from '../app';
import VisitPassList from "@/components/user/VisitPassList.vue";

import './../../sass/main.scss';
const app = createApp({
    components: { VisitPassList },
});

app.use(vuetify)
app.mount('#visit-passes-container');
