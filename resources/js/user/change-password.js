import {createApp} from 'vue';
import vuetify from '../app';
import ChangePasswordForm from "@/components/ChangePasswordForm.vue";

import './../../sass/main.scss';

const app = createApp({
    components: {ChangePasswordForm},
});

app.use(vuetify)
app.mount('#change-password-container');
