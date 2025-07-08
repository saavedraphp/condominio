<script setup>
import { onMounted, ref, watch } from 'vue';
import axios from "axios";

// --- PROPS ---
const props = defineProps({
    modelValue: {
        type: Object,
        default: null
    },
    initialHouseId: {
        type: [Number, String],
        default: null
    }
});

// --- EMITS ---
const emit = defineEmits(['update:modelValue']);

// --- ESTADO INTERNO ---
const isLoading = ref(false);
const itemsList = ref([]);
const searchQuery = ref('');
const selectedHouse = ref(null);
const apiError = ref(null);

let debounceTimer = null;

// --- API HELPER (Aquí está la pieza que faltaba) ---
const houseApi = {
    /**
     * Busca casas en la API.
     * @param {string} query - El término de búsqueda.
     * @param {string|number|null} initialId - Un ID específico para cargar inicialmente.
     * @returns {Promise<Array>} - Una promesa que resuelve a un array de casas.
     */
    async search(query = '', initialId = null) {
        try {
            const response = await axios.get('/admin/houses', {
                params: {
                    search: query,
                    initialHouseId: initialId // Pasamos ambos parámetros a la API
                }
            });
            // La API puede devolver los datos en 'response.data.data' (paginado) o 'response.data'
            return response.data.data || response.data || [];
        } catch (error) {
            console.error("Error obteniendo las casas:", error);
            // Asignamos el error para poder mostrarlo en la UI si quisiéramos
            apiError.value = error.response?.data?.message || error.message || 'No se pudo conectar a la API.';
            // Devolvemos un array vacío en caso de error para no romper el componente
            return [];
        }
    }
};

// --- MÉTODOS ---

// Método unificado para cargar las casas (tanto inicial como en búsqueda)
const fetchHouses = async (query = '', initialId = null) => {
    isLoading.value = true;
    apiError.value = null; // Limpiar errores previos

    // Llamamos a nuestro nuevo método de la API
    const results = await houseApi.search(query, initialId);

    // Si es una búsqueda inicial por ID y no hay resultados, puede que el ID sea inválido
    // o que la API no devuelva una lista junto con el item inicial.
    // Si la búsqueda es por texto, simplemente actualizamos la lista.
    itemsList.value = results;

    isLoading.value = false; // Esto ahora SIEMPRE se ejecuta
};

// Se llama cuando el usuario escribe en el campo de búsqueda
const onSearchInput = (query) => {
    searchQuery.value = query;
    if (debounceTimer) clearTimeout(debounceTimer);

    // Si el campo está vacío o si el texto es el de la casa ya seleccionada, no hacemos nada.
    if (!query || (selectedHouse.value && query === selectedHouse.value.property_unit)) {
        return;
    }

    debounceTimer = setTimeout(() => {
        fetchHouses(query); // Usamos nuestra función unificada
    }, 300);
};

// --- WATCHERS ---

// 1. Observa el `selectedHouse` interno y actualiza el v-model del padre.
watch(selectedHouse, (newSelection) => {
    if (newSelection?.id !== props.modelValue?.id) {
        emit('update:modelValue', newSelection);
    }
    // Cuando se selecciona una casa, actualizamos el `searchQuery` para que el texto del input coincida.
    // Esto es útil si el usuario borra y vuelve a seleccionar.
/*    if(newSelection) {
        searchQuery.value = newSelection.property_unit;
    }*/
});

// 2. Observa el `initialHouseId` y la lista de casas para pre-seleccionar el valor inicial.
watch(
    [() => props.initialHouseId, itemsList],
    ([id, list]) => {
        if (id && list.length > 0) {
            const houseToSelect = list.find(item => item.id == id); // Usar '==' para comparar string y number
            if (houseToSelect) {
                selectedHouse.value = houseToSelect;
            }
        }
    },
    { immediate: true } // Ejecutar inmediatamente al cargar
);

// --- LIFECYCLE HOOKS ---
onMounted(() => {
    // En la carga inicial, llamamos a fetchHouses pasando el ID inicial
    // para que la API nos devuelva esa casa específica y/o una lista inicial.
    fetchHouses('', props.initialHouseId);
});
</script>

<template>
    <v-autocomplete
        v-model="selectedHouse"
        :items="itemsList"
        :loading="isLoading"
        :search="searchQuery"
        @update:search="onSearchInput"
        item-title="address"
        item-value="id"
        label="Buscar y seleccionar casa..."
        placeholder="Escribe el nombre o dirección..."
        variant="outlined"
        return-object
        clearable
        no-data-text="No se encontraron casas"
        :error-messages="apiError ? [apiError] : []"
    >
        <!-- Slots para personalizar la apariencia (igual que antes) -->
        <template v-slot:item="{ props, item }">
            <v-list-item
                v-bind="props"
                :title="item.raw.property_unit"
                :subtitle="item.raw.address"
            ></v-list-item>
        </template>
        <template v-slot:selection="{ item }">
            <span>{{ item.raw.property_unit }} - {{ item.raw.address }}</span>
        </template>
    </v-autocomplete>
</template>
