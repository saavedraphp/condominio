<script setup>
import {onMounted, ref} from "vue";
import House from "@/components/user/House.vue";
import axios from "axios";

const props = defineProps({
    userId: String
});

const houses = ref([]);
const loading = ref(true)
const error = ref(null)
const TABS_KEYS = {
    'HOUSES': 'houses',
    'VEHICLES': 'vehicles',
};

const headers = ref([
    {title: 'Unidad de Propiedad', key: 'property_unit', align: 'start', sortable: true},
    {title: 'Direccion', key: 'address', sortable: true},
    {title: 'Area de Construcción', key: 'construction_area', sortable: true},
    {title: '% Participación', key: 'participation_percentage', sortable: true},
    {title: 'Acciones', key: 'actions', sortable: false, align: 'end'},
]);

const activeKey = ref(TABS_KEYS.HOUSES);

onMounted(() => {
    getHousesByUserId();
})

async function getHousesByUserId() {
    loading.value = true;

    try {
        const response = await axios.get(`/admin/user/houses/${props.userId}`);
        houses.value = response.data;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <v-container>
        <p v-if="loading">Cargango datos</p>
        <p v-else-if="error"> {{ error }}</p>
        <v-row v-else>
            <!-- Panel Derecho (Formulario) -->
            <v-col cols="12" md="8">
                <v-card>
                    <v-tabs v-model="activeKey">
                        <v-tab :value="TABS_KEYS.HOUSES">Casa e Integrantes</v-tab>
                        <v-tab :value="TABS_KEYS.VEHICLES">Vehículos</v-tab>
                    </v-tabs>
                    <v-card-text>
                        <v-window v-model="activeKey">
                            <v-window-item :value="TABS_KEYS.HOUSES">
                                <v-data-table v-show="houses.length"
                                              :headers="headers"
                                              :items="houses"
                                              class="elevation-1"
                                              dense
                                >
                                    <!-- Columna de Acciones Personalizada -->
                                    <template v-slot:item.actions="{ item }">
                                        <v-tooltip text="Editar">
                                            <template v-slot:activator="{ props }">
                                                <v-btn
                                                    v-bind="props"
                                                    icon="mdi-pencil"
                                                    variant="text"
                                                    color="primary"
                                                    size="small"
                                                    class="me-2"
                                                    @click="openModalEdit(item)"
                                                ></v-btn>
                                            </template>
                                        </v-tooltip>
                                        <v-tooltip text="Configurar">
                                            <template v-slot:activator="{ props }">
                                                <v-btn
                                                    v-bind="props"
                                                    icon="mdi-cog"
                                                    variant="text"
                                                    color="primary"
                                                    size="small"
                                                    class="me-2"
                                                    @click="openSettings(item)"
                                                ></v-btn>
                                            </template>
                                        </v-tooltip>
                                        <v-tooltip text="Eliminar">
                                            <template v-slot:activator="{ props }">
                                                <v-btn
                                                    v-bind="props"
                                                    icon="mdi-delete"
                                                    variant="text"
                                                    color="error"
                                                    size="small"
                                                    @click="openDeleteModal(item)"
                                                ></v-btn>
                                            </template>
                                        </v-tooltip>
                                    </template>
                                    <template v-slot:item.email_verified_at="{ value }">
                                        <v-chip :color="value ? 'success' : 'grey'" size="small">
                                            {{ value ? 'Verificado' : 'Pendiente' }}
                                        </v-chip>
                                    </template>
                                    <template v-slot:item.status="{ value }">
                                        <v-chip :color="value  === 'active' ? 'success' : 'grey'" size="small">
                                            {{ value === 'active' ? 'Activo' : 'Inactivo' }}
                                        </v-chip>
                                    </template>

                                </v-data-table>
                                {{ props.userId }}
                            </v-window-item>
                        </v-window>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>

</template>

<style scoped>

</style>
