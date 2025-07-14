import { createApp } from 'vue';
import vuetify from '../app';
import SettingsView from '../components/admin/SettingsView.vue';
import './../../sass/main.scss';
const app = createApp({
    components: { SettingsView },
    methods: {
    }
});

app.use(vuetify)
app.mount('#settings-container');
