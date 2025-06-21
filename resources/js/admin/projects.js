import { createApp } from 'vue';
import vuetify from '../app';
import ProjectList from "@/components/admin/ProjectList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { ProjectList },
});

app.use(vuetify)
app.mount('#projects-container');
