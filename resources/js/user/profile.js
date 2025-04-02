import { createApp } from 'vue';
import vuetify from '../app';
import Profile from '../components/user/Profile.vue';

/*
createApp(Profile)
    .use(vuetify)
    .mount('#profile-container');
*/
const app = createApp({
    components: { Profile },
    methods: {
        handleAddUser(data) {
            console.log("Usuario agregado:", data);
        }
    }
});

app.use(vuetify).mount('#profile-container');
