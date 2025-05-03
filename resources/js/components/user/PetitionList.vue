<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import TenantPetitionCreateDialog from '@/components/user/PetitionCreateDialog.vue';
import TenantPetitionViewDialog from '@/components/user/TenantPetitionViewDialog.vue';
import { debounce } from 'lodash-es'; // Para evitar búsquedas en cada tecla

const props = defineProps({
    apiBaseUrl: {
        type: String,
        required: true
    },
    isAdmin: {
        type: Boolean,
        default: false
    }
});
// --- Estado ---
const petitions = ref([]);
const loading = ref(false);
const search = ref('');
const showModal = ref(false);
const showViewDialog = ref(false);
const selectedPetitionId = ref(null);

const headers = [
    { title: 'Asunto', key: 'subject', sortable: true },
    { title: 'Tipo', key: 'type', sortable: true },
    { title: 'Estado', key: 'status', sortable: true },
    { title: 'Última Actualización', key: 'updated_at', sortable: true },
];

// --- Métodos ---
const getPetitions = async () => {
    loading.value = true;
    try {
        // Asegúrate que la URL base de axios esté configurada o usa la URL completa
        const response = await axios.get(`${props.apiBaseUrl}`, {
            params: { search: search.value } // Pasa el término de búsqueda si aplica
        });
        // Si usas paginación en el backend, la data estará en response.data.data
        petitions.value = response.data.data || response.data;
    } catch (error) {
        console.error("Error fetching petitions:", error);
        // Manejar error (mostrar snackbar, etc.)
    } finally {
        loading.value = false;
    }
};

const fetchPetitionsDebounced = debounce(getPetitions, 300);


const getStatusColor = (status) => {
    switch (status) {
        case 'Abierto': return 'blue';
        case 'En Progreso': return 'orange';
        case 'Cerrado': return 'grey';
        default: return 'grey';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const onPetitionCreated = () => {
    showModal.value = false;
    getPetitions(); // Recargar la lista
};

const handleRowClick = (event, { item }) => {
     console.log("Clicked row item:", item); // item.raw contiene el objeto original
    if (item && item.id) {
        selectedPetitionId.value = item.id;
        showViewDialog.value = true;
    }
};


// --- Ciclo de vida ---
onMounted(() => {
    getPetitions();
});
</script>
<template>
    <v-container fluid>
         <v-card :loading="loading">
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi mdi-message-reply-text-outline"></v-icon>
                 
                Mis Peticiones
                <v-spacer></v-spacer>
                <v-btn
                    v-if="!isAdmin"
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="showModal = true"
                >
                    Agregar Petición
                </v-btn>
            </v-card-title>
            <v-card-title v-if="false">
                <v-text-field
                    v-model="search"
                    append-inner-icon="mdi-magnify"
                    label="Buscar..."
                    single-line
                    hide-details
                    density="compact"
                    @input="fetchPetitionsDebounced"
                ></v-text-field>
            </v-card-title>
             <v-divider></v-divider>
            <v-data-table
                :headers="headers"
                :items="petitions"
                :loading="loading"
                :search="search"
                item-value="id"
                class="elevation-1"
                @click:row="handleRowClick"
                hover
            >
                <template v-slot:item.status="{ item }">
                    <v-chip :color="getStatusColor(item.status)" dark small>
                        {{ item.status }}
                    </v-chip>
                </template>
                <template v-slot:item.updated_at="{ item }">
                    {{ formatDate(item.updated_at) }}
                </template>
                <template v-slot:loading>
                    <v-skeleton-loader type="table-row@5"></v-skeleton-loader>
                </template>
                <template v-slot:no-data v-if="false">
                    <v-alert type="info" class="ma-4">No se encontraron peticiones.</v-alert>
                </template>
            </v-data-table>
        </v-card>

        <TenantPetitionCreateDialog
            v-model="showModal"
            @petition-created="onPetitionCreated"
            :api-base-url="props.apiBaseUrl"
        />

        <TenantPetitionViewDialog
            v-model="showViewDialog"
            :petition-id="selectedPetitionId"
            :api-base-url="props.apiBaseUrl"
            @close="showViewDialog = false"
            @updated="getPetitions"
        />

    </v-container>
</template>
<style scoped>
.v-data-table :deep(tbody tr:hover) {
    cursor: pointer;
}
</style>
