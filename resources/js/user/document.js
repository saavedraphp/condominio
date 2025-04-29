import { createApp } from 'vue';
import vuetify from '../app';
import DocumentList from "@/components/user/DocumentList.vue";

import './../../sass/main.scss';

const app = createApp({
    components: { DocumentList },
});

app.use(vuetify)
app.mount('#document-list-container');
