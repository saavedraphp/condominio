import { createApp } from 'vue';
import vuetify from '../app';
import PetitionList from "@/components/user/PetitionList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { PetitionList },
});

app.use(vuetify)
app.mount('#petition-list-container');
