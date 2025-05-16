<script setup>
import VehicleListAdmin from  "@/components/admin/VehicleList.vue";

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    isAdmin: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-car-back"></v-icon>
                 
                Lista de Vehículos
                <v-spacer></v-spacer>

            </v-card-title>
            <v-divider></v-divider>

                <VehicleListAdmin
                    :user="props.user"
                    :is-admin="props.isAdmin"
                >
                </VehicleListAdmin>

        </v-card>
    </v-container>
</template>

<style scoped>

</style>
