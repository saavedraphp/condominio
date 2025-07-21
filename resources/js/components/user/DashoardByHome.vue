<script setup>
import Snackbar from "@/components/Snackbar.vue";
import axios from "axios";
import {onMounted, ref} from "vue";
import DebtStatusAlert from "@/components/user/DebtStatusAlert.vue";
import HouseAttribute from "@/components/admin/HouseAttribute.vue";

const props = defineProps({
    userId: {
        type: String,
        required: true
    },
    houseId: {
        type: String,
        required: true
    }
});


const loading = ref(true);
const totalLoading = ref(false);
const house = ref(null);
const mySnackbar = ref(null);
const totalDebt = ref(null);
const user = ref(null);

async function getUser() {
    loading.value = true;
    user.value = null;
    try {
        const response = await axios.get(`/user/show/${props.userId}`);

        if (response.status === 200) {
            user.value = response.data;
        }

    } catch (error) {
        mySnackbar.value.show(error.response.data.message, 'error');
    } finally {
        loading.value = false;
    }
}
async function getHouse() {
    loading.value = true;
    house.value = null
    try {
        const response = await axios.get(`/user/houses/show/${props.houseId}`);

        if (response.status === 200) {
            house.value = response.data;
            totalDebt.value = response.data.balance.amount_due || 0;
        }

    } catch (error) {
        if (error.response.status === 403) {
            mySnackbar.value.show(error.response.data.message, 'error');
            return;
        }
        mySnackbar.value.show('Lo sentimos, hubo un problema al obtener  las casas asignadas. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    totalLoading.value = true;
    try {
        await Promise.all([
             await getUser(),
            await getHouse(),
            totalLoading.value = false,
        ]);
    }catch (e) {
        console.error("Hubo un error al cargar los datos iniciales:", error);
        mySnackbar.value.show('No se pudieron cargar todos los datos. Intenta de nuevo.', 'error');
    }

});
</script>

<template>
    <v-container fluid>
        <p v-if="totalLoading">Cargango datos</p>
        <v-row dense v-else>
            <!-- Card Bienvenida -->
            <v-col cols="12">
                <DebtStatusAlert v-if="totalDebt !== null"
                    :total-debt="totalDebt"
                    :has-payment-arrangement="user?.has_payment_arrangement"
                    :show-pay-button="false"
                />
            </v-col>
            <v-col cols="12">
                <HouseAttribute v-if="house?.house"
                    :user="user"
                    :house="house.house"
                />
            </v-col>
        </v-row>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>

<style scoped>

</style>
