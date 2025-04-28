<script setup>
const emit = defineEmits(['close-modal']);
const props = defineProps({
    element: {
        type: Object,
        default: null
    }
});

const close = () => {
    emit('close-modal');
}

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', options);
};
</script>
<template>
    <v-card>
        <!-- 1. Título del Modal -->
        <v-card-title class="headline grey lighten-2" primary-title>
            Detalles del Registro de Consumo
        </v-card-title>

        <!-- 2. Contenido del Modal -->
        <v-card-text v-if="element"> <!-- Asegura que selectedItem no sea null -->
            <v-container>
                <v-row dense>
                    <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">Fecha</div>
                        <div class="text-body-1">{{ formatDate(element.payment_date) }}</div>
                    </v-col>
                    <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">Consumo (kWh)</div>
                        <div class="text-body-1">{{ element.quantity ?? 'N/A' }} kWh</div>
                    </v-col>
                    <v-col cols="12" sm="6">
                        <div class="text-caption text-grey">¿Hubo Remplazo?</div>
                        <div>
                            <v-chip
                                :color="element.replace ? 'success' : 'grey'"
                                size="small"
                                label
                            >
                                {{ element.replace ? 'Sí' : 'No' }}
                            </v-chip>
                        </div>
                    </v-col>
                    <v-col cols="12">
                        <div class="text-caption text-grey mt-3">Observaciones</div>
                        <p
                            class="text-body-2 pa-2 bg-grey-lighten-4 rounded"
                            style="white-space: pre-wrap; min-height: 50px;"
                        >
                            {{ element.observations || 'Sin observaciones.' }}
                        </p>
                    </v-col>
                    <v-col cols="12" v-if="element.file_path_url">
                        <div class="text-caption text-grey mt-3">Imagen Adjunta</div>
                        <v-img
                            :src="element.file_path_url"
                            max-height="350"
                            contain
                            class="mt-2 border rounded"
                            alt="Imagen del registro"
                        ></v-img>
                    </v-col>
                    <v-col cols="12" v-else>
                        <div class="text-caption text-grey mt-3">Imagen Adjunta</div>
                        <p class="text-body-2 text-grey">No hay imagen disponible.</p>
                    </v-col>
                </v-row>
            </v-container>
        </v-card-text>
        <v-card-text v-else>
            <p>No se han cargado los detalles.</p>
        </v-card-text>


        <!-- 3. Acciones (Pie del Modal) -->
        <v-divider></v-divider>
        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn
                color="blue darken-1"
                variant="text"
                @click="close"
            >
                Cerrar
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped>

</style>
