import { createApp } from 'vue';
import vuetify from '../app';
import UserList from '../components/admin/UserList.vue';
import './../../sass/main.scss';
const app = createApp({
    components: { UserList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#users-container');
