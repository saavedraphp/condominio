<script setup>
import {onMounted, ref} from "vue";
import axios from "axios";
import MemberList from "@/components/admin/MemberList.vue";

const props = defineProps({
    modelValue: Boolean,
    user: Object,
    house: Object
});

const loading = ref(true)
const TABS_KEYS = {
    'MEMBERS': 'members'
};

const activeKey = ref(TABS_KEYS.HOUSES);

const goBack = () => {
    window.history.back();
};

onMounted(() => {

});
</script>

<template>
    <v-container>
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
                Usuario : {{ props.user.name }} / {{ props.house.address }}
            </v-card-title>
            <v-divider></v-divider>
            <v-tabs v-model="activeKey">
                <v-tab :value="TABS_KEYS.HOUSES">Integrantes</v-tab>
            </v-tabs>
            <v-card-text>
                <v-window v-model="activeKey">
                    <v-window-item :value="TABS_KEYS.HOUSES">
                        <MemberList
                            :user="props.user"
                            :house="props.house"
                        >

                        </MemberList>

                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>

    </v-container>
</template>

<style scoped>

</style>
