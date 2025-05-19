import {createApp} from 'vue';
import vuetify from '../app';
import VerificationPage from "@/components/user/VerificationPage.vue";

import './../../sass/main.scss';

const app = createApp({
    components: {VerificationPage},
});

app.use(vuetify)
app.mount('#qr-verification-container');
