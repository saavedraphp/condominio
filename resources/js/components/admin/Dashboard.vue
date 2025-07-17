<script setup>
import {onMounted, ref} from 'vue';
import StatCard from '@/components/StatCard.vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue"; // Asegúrate que la ruta sea correcta

const stats = ref([
    { title: 'New Orders', value: 150, icon: 'mdi-shopping', color: 'teal', to: '/orders' },
    { title: 'Bounce Rate', value: 53, suffix: '%', icon: 'mdi-chart-bar', color: 'green', to: '/stats' },
    { title: 'User Registrations', value: 44, icon: 'mdi-account-plus', color: 'yellow-darken-2', to: '/users' },
    { title: 'Unique Visitors', value: 65, icon: 'mdi-chart-pie', color: 'red', to: '/visitors' },
]);

const mySnackbar = ref(null);
const cards = ref([]);
const loading = ref(false);
async function getStatisticCard() {
    loading.value = true;
    try {
        const response = await axios.get(`/admin/statistic-cards`);
        cards.value = response.data.data;
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener los datos de estadistica.', 'error');
    } finally {
        loading.value = false;
    }
}
onMounted(() => {
    getStatisticCard();
});
</script>
<template>
    <v-container fluid>
        <v-row>
            <StatCard
                v-for="card in cards"
                :key="card.title"
                :color="card.color"
                :icon="card.icon"
                :title="card.title"
                :value="card.value"
                :suffix="card.suffix"
                :to="card.to"
            />
        </v-row>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
