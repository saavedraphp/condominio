<script setup>
import {onMounted, ref} from "vue";
import MemberList from "@/components/admin/MemberList.vue";
import VehicleList from "@/components/admin/VehicleList.vue";

const props = defineProps({
    modelValue: Boolean,
    user: Object,
    house: Object,
    isAdmin: {
        type: Boolean,
        default: false
    }
});

const loading = ref(true)
const TABS_KEYS = {
    'MEMBERS': 'members',
    'VEHICLES': 'vehicles'
};

const activeKey = ref(TABS_KEYS.MEMBERS);

const goBack = () => {
    window.history.back();
};

onMounted(() => {

});
</script>

<template>
    <v-card>
        <v-toolbar density="compact" flat color="transparent">
            <v-btn
                icon="mdi-arrow-left"
                @click="goBack"
                aria-label="Volver"
            ></v-btn>
            Lista de Casas
        </v-toolbar>
        <v-card-title class="text-start text-h6 justify-center py-4">
            <div class="px-4 py-3">
                <div class="text-h5 font-weight-bold d-flex align-center">
                    <v-icon start color="grey-darken-1">mdi-home-city-outline</v-icon>
                    {{ props.house.address }}
                </div>
                <div class="text-subtitle-1 text-medium-emphasis ml-1">
                    Usuario: {{ props.user.name }}
                </div>
            </div>
        </v-card-title>
        <v-divider></v-divider>
        <v-tabs v-model="activeKey">
            <v-tab :value="TABS_KEYS.MEMBERS">Integrantes</v-tab>
            <v-tab :value="TABS_KEYS.VEHICLES">Vehículos</v-tab>
        </v-tabs>
        <v-card-text>
            <v-window v-model="activeKey">
                <v-window-item :value="TABS_KEYS.MEMBERS">
                    <MemberList
                        :user="props.user"
                        :house="props.house"
                        :is-admin="props.isAdmin"
                    >
                    </MemberList>
                </v-window-item>
                <v-window-item :value="TABS_KEYS.VEHICLES">
                    <VehicleList
                        :user="props.user"
                        :house="props.house"
                        :is-admin="props.isAdmin"
                    ></VehicleList>
                </v-window-item>
            </v-window>
        </v-card-text>
    </v-card>
</template>

<style scoped>

</style>
