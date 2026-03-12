import { createApp } from 'vue';
import vuetify from '../app';
import BalanceSheetList from "@/components/admin/BalanceSheetList.vue";
import './../../sass/main.scss';
const app = createApp({
    components: { BalanceSheetList },
    methods: {
    }
});

app.use(vuetify)
app.mount('#balance-sheet-container');
