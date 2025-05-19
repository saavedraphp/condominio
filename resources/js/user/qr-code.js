import { createApp } from 'vue';
import vuetify from '../app';
import UserQrCode from "../components/user/UserQrCode.vue";

import './../../sass/main.scss';

const app = createApp({
    components: {UserQrCode },
});

app.use(vuetify)
app.mount('#qr-user-container');
