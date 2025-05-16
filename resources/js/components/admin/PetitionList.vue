<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import axios from 'axios';
import AdminPetitionViewDialog from '@/components/admin/PetitionViewDialog.vue'; // Dialog de admin
import { debounce } from 'lodash-es';
import {formatDateTime} from "@/utils/functions.js";

const props = defineProps({
    apiBaseUrl: {
        type: String,
        required: true
    }
});

// --- Estado ---
const petitions = ref([]);
const loading = ref(false);
const showViewDialog = ref(false);
const selectedPetitionId = ref(null);


const filters = reactive({
    search: '',
    status: null,
    type: null,
});

const pagination = reactive({
    page: 1,
    itemsPerPage: 10, // Valor inicial
    sortBy: [{ key: 'created_at', order: 'desc' }], // Orden inicial por fecha creación descendente
    totalItems: 0,
});

const headers = [
    { title: 'Remitente', key: 'webUser.name', sortable: false }, // O usa un campo específico si lo cargas
    { title: 'Asunto', key: 'subject', sortable: true },
    { title: 'Tipo', key: 'type', sortable: true },
    { title: 'Estado', key: 'status', sortable: true },
    { title: 'Última Actualización', key: 'updated_at', sortable: true },
    { title: 'Fecha Creación', key: 'created_at', sortable: true },
];

const statusOptions = ['Abierto', 'En Progreso', 'Cerrado'];
const typeOptions = ['Reclamo', 'Sugerencia', 'Consulta', 'Otro']; // Coincidir con backend

// --- Métodos ---
const fetchPetitions = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.itemsPerPage,
            sortBy: pagination.sortBy[0]?.key,
            sortDesc: pagination.sortBy[0]?.order === 'desc',
            ...filters // Añade todos los filtros activos
        };
        // Limpiar parámetros nulos o vacíos
        Object.keys(params).forEach(key => (params[key] == null || params[key] === '') && delete params[key]);

        const response = await axios.get(`${props.apiBaseUrl}`, { params });
        petitions.value = response.data.data;
        pagination.totalItems = response.data.total;
    } catch (error) {
        console.error("Error fetching petitions:", error);
        // Manejar error
    } finally {
        loading.value = false;
    }
};

const fetchPetitionsDebounced = debounce(fetchPetitions, 500); // Debounce para search

// Manejador para v-data-table-server
const handleOptionsUpdate = (options) => {
    // console.log("Table options updated:", options);
    pagination.page = options.page;
    pagination.itemsPerPage = options.itemsPerPage;
    pagination.sortBy = options.sortBy; // sortBy es un array [{ key, order }]
    fetchPetitions(); // Carga datos con la nueva paginación/orden
};

const getStatusColor = (status) => {
    // Misma lógica que en el componente de inquilino
    switch (status) {
        case 'Abierto': return 'blue';
        case 'En Progreso': return 'orange';
        case 'Cerrado': return 'grey';
        default: return 'grey';
    }
};

const handleRowClick = (event, { item }) => {
    if (item && item.id) {
        selectedPetitionId.value = item.id;
        showViewDialog.value = true;
    }
};

// --- Watchers ---
// Observar cambios en filtros (excepto search que usa debounce)
watch(() => [filters.status, filters.type], () => {
    pagination.page = 1; // Resetear a página 1 al cambiar filtros
    fetchPetitions();
});

// --- Ciclo de vida ---
// La carga inicial se disparará por handleOptionsUpdate al montar la tabla
// onMounted(fetchPetitions); // Ya no es necesario si la tabla dispara el evento al inicio

</script>
<template>
    <v-container>
        <v-row justify="space-between" align="center" class="mb-4">
            <v-col>
                <h2 class="text-h5">Gestionar Peticiones</h2>
            </v-col>
        </v-row>

        <v-card :loading="loading">
            <v-card-title class="d-flex flex-wrap ga-3" v-if="false">
                <v-text-field
                    v-model="filters.search"
                    append-inner-icon="mdi-magnify"
                    label="Buscar (Asunto, Desc, Inquilino...)"
                    single-line
                    hide-details
                    density="compact"
                    class="flex-grow-1"
                    style="min-width: 250px;"
                    @input="fetchPetitionsDebounced"
                ></v-text-field>
                <v-select
                    v-model="filters.status"
                    :items="statusOptions"
                    label="Estado"
                    clearable
                    hide-details
                    density="compact"
                    style="max-width: 180px;"
                    @update:modelValue="fetchPetitions"
                ></v-select>
                <v-select
                    v-model="filters.type"
                    :items="typeOptions"
                    label="Tipo"
                    clearable
                    hide-details
                    density="compact"
                    style="max-width: 180px;"
                    @update:modelValue="fetchPetitions"
                ></v-select>
            </v-card-title>

            <v-data-table-server
                v-model:items-per-page="pagination.itemsPerPage"
                :headers="headers"
                :items="petitions"
                :items-length="pagination.totalItems"
                :loading="loading"
                :search="filters.search"
                item-value="id"
                class="elevation-1"
                @update:options="handleOptionsUpdate"
                @click:row="handleRowClick"
                hover
            >
                <template v-slot:item.webUser.name="{ item }">
                    {{ item.web_user?.name || 'N/A' }}
                </template>
                <template v-slot:item.status="{ item }">
                    <v-chip :color="getStatusColor(item.status)" dark small>
                        {{ item.status }}
                    </v-chip>
                </template>
                <template v-slot:item.updated_at="{ item }">
                    {{ formatDateTime(item.updated_at) }}
                </template>
                <template v-slot:item.created_at="{ item }">
                    {{ formatDateTime(item.created_at) }}
                </template>
                <template v-slot:loading>
                    <v-skeleton-loader type="table-row@5"></v-skeleton-loader>
                </template>
                <template v-slot:no-data v-if="false">
                    <v-alert type="info" class="ma-4">No se encontraron peticiones con los filtros actuales.</v-alert>
                </template>
            </v-data-table-server>
        </v-card>

        <AdminPetitionViewDialog
            v-model="showViewDialog"
            :petition-id="selectedPetitionId"
            :api-base-url="props.apiBaseUrl"
            @close="showViewDialog = false"
            @updated="fetchPetitions"
        />

    </v-container>
</template>



<style scoped>
.v-data-table :deep(tbody tr:hover) {
    cursor: pointer;
}
</style>
