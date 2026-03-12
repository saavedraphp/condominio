<script setup>
import {ref, onMounted, computed, watch} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {formattedMoney} from "../../utils/functions.js";
import dayjs from "dayjs";
import {useField} from "vee-validate";

const mySnackbar = ref(null);
const loading = ref(true);
const selectedYear = ref(null);
const selectedMonth = ref(null);


const previewReport = () => {
    const url = `/admin/reports/assets/preview`;
    window.open(url, '_blank');
};
</script>
<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-home"></v-icon>
                 
                Reporte de Balance
                <v-spacer></v-spacer>
            </v-card-title>

            <v-card-text>
                <!-- Fila de Filtros -->
                <v-row align="center" class="mt-5">

                    <v-col cols="12" sm="6" class="d-flex justify-end align-center flex-wrap ga-2">
                        <v-btn @click="previewReport" color="secondary" prepend-icon="mdi-printer">
                            Previsualizar
                        </v-btn>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
<style scoped>
/* Puedes añadir estilos específicos aquí si los necesitas */
.v-card-title {
    background-color: #f5f5f5; /* Un fondo ligero para el título como en tu imagen */
    border-bottom: 1px solid #e0e0e0;
}
</style>
