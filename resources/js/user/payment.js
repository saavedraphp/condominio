import { createApp } from 'vue';
import vuetify from '../app';
import PaymentList from "@/components/user/PaymentList.vue";

import './../../sass/main.scss';
const app = createApp({
    components: { PaymentList },
});

app.use(vuetify)
app.mount('#payment-list-container');
