import { createApp } from 'vue';
import vuetify from '../app';
import UserSettings from '../components/admin/UserSettings.vue';

const app = createApp({
    components: { UserSettings },
    methods: {
    }
});

app.use(vuetify)
app.mount('#user-settings-container');
