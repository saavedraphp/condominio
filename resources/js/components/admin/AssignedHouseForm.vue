<script setup>
import {ref, watch, onMounted, reactive} from 'vue';
import axios from 'axios';
import Snackbar from "@/components/Snackbar.vue";
import {number} from "yup";

const emit = defineEmits(['added-assigned', 'edit-assigned', 'close-modal']);

const props = defineProps({
    userId: String | number
});
// --- Estado Reactivo ---

// Lista completa de casas desde la API
const allHouses = ref([]);
// Indicador de carga para la lista de casas
const isLoadingHouses = ref(false);
// Para guardar el objeto casa seleccionado en el autocomplete
const selectedHouse = ref(null); // Importante: usar return-object en v-autocomplete
// Para almacenar errores de la API
const apiError = ref(null);
// Para el texto de búsqueda actual (opcional, pero útil para debugging o lógica avanzada)
const currentSearch = ref('');

const isRecording = ref(true)
const mySnackbar = ref(null);

// Datos del formulario que se llenarán y se enviarán
const formDetails = reactive({
    address: '',
    construction_area: '',
    participation_percentage: '',
    property_unit: '',
    // Roles
    is_owner: false,
    is_resident: false,
    is_manager: false,
    start_date: null,
    end_date: null,
});

// --- Lógica ---

// Función para obtener las casas de la API
const fetchHouses = async () => {
    isLoadingHouses.value = true;
    apiError.value = null;
    allHouses.value = []; // Limpiar antes de cargar
    selectedHouse.value = null; // Deseleccionar al recargar
    try {
        const response = await axios.get(`/admin/user/${props.userId}/house-assignments/getUnassigned`);
        allHouses.value = response.data.data || response.data || [];
    } catch (error) {
        console.error("Error obteniendo  las casas:", error);
        apiError.value = error.response?.data?.message || error.message || 'No se pudo conectar a la API.';
        allHouses.value = [];
    } finally {
        isLoadingHouses.value = false;
    }
};

// Observador: Se ejecuta cuando 'selectedHouse' cambia (cuando el usuario selecciona/deselecciona una casa)
watch(selectedHouse, (newSelection) => {
    if (newSelection && typeof newSelection === 'object') {
        // Se seleccionó una casa, llenar los campos del formulario
        // Asegúrate de que los nombres de propiedad coincidan con tu API (newSelection.address, etc.)
        formDetails.address = newSelection.address || '';
        formDetails.construction_area = newSelection.construction_area || '';
        formDetails.participation_percentage = newSelection.participation_percentage || '';
        formDetails.property_unit = newSelection.property_unit || '';

        formDetails.is_owner = false;
        formDetails.is_resident = false;
        formDetails.is_manager = false;

    } else {
        formDetails.address = '';
        formDetails.construction_area = '';
        formDetails.participation_percentage = '';
        formDetails.property_unit = '';
        formDetails.is_owner = false;
        formDetails.is_resident = false;
        formDetails.is_manager = false;
    }
});

// Función para manejar el input de búsqueda (opcional)
const onSearchInput = (value) => {
    // v-autocomplete filtra localmente por defecto.
    // Solo necesitarías lógica aquí si quisieras filtrar llamando a la API
    // en cada tecleo (más complejo, requiere debouncing).
    console.log('Buscando:', value);
    currentSearch.value = value;
};

const submitAssignment = async () => {

    if (!selectedHouse.value) {
        alert('Por favor, selecciona una casa primero.');
        return;
    }

    if (!formDetails.is_owner && !formDetails.is_resident && !formDetails.is_manager) {
        alert('Por favor, selecciona un rol.');
        return;
    }

    try {
        const payload = {
            house_id: selectedHouse.value.id,
            is_owner: formDetails.is_owner,
            is_resident: formDetails.is_resident,
            is_manager: formDetails.is_manager,
            start_date: formDetails.start_date,
            end_date: formDetails.end_date,
            web_user_id: props.userId
        };

        isRecording.value = true;

        const response = await axios.post(`/admin/user/${props.userId}/house-assignments`, payload);
        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            emit('added-assigned',response.data.message);
            close();
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        isRecording.value = false;
    }
};

// --- Ciclo de Vida ---

// Cargar las casas cuando el componente se monta
onMounted(() => {
    fetchHouses();
});

const close = () => {
    emit('close-modal');
}
</script>
<template>
    <v-container>
        <v-card>
            <v-card-text>
                <v-form @submit.prevent="submitAssignment">
                    <v-row>
                        <v-col cols="12">
                            <h2>Asignar Casa</h2>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-autocomplete
                                v-model="selectedHouse"
                                :items="allHouses"
                                :loading="isLoadingHouses"
                                :disabled="isLoadingHouses"
                                item-title="address"
                                item-value="id"
                                label="Buscar y seleccionar casa..."
                                placeholder="Escribe el nombre o dirección..."
                                variant="outlined"
                                return-object
                                clearable
                                no-data-text="No se encontraron casas"
                                @update:search="onSearchInput"
                            >
                                <!-- Opcional: Personalizar cómo se muestra cada item en la lista -->
                                <template v-slot:item="{ props, item }">
                                    <v-list-item
                                        v-bind="props"
                                        :title="item.raw.name"
                                        :subtitle="item.raw.address"
                                    ></v-list-item>
                                </template>

                                <!-- Opcional: Mostrar algo más que el item-title cuando está seleccionado -->
                                <template v-slot:selection="{ item }">
                                    <span>{{ item.raw.name }} - {{ item.raw.address }}</span>
                                </template>

                            </v-autocomplete>
                        </v-col>
                        <v-col cols="12" md="6" align="end">
                            <v-btn color="blue-darken-1" variant="text" @click="close" v-if="!selectedHouse">Cancelar</v-btn>
                        </v-col>
                    </v-row>
                    <v-divider class="my-4" v-if="selectedHouse"></v-divider>

                    <!-- Campos que se autocompletan -->
                    <div v-if="selectedHouse">
                        <v-row>
                            <v-col cols="12">
                                <h3>Detalles de la Casa Seleccionada</h3>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="formDetails.address"
                                    label="Dirección"
                                    variant="outlined"
                                    readonly
                                    filled
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="formDetails.property_unit"
                                    label="Unidad de propiedad"
                                    variant="outlined"
                                    readonly
                                    filled
                                ></v-text-field>
                            </v-col>
                            <!-- Aquí irían los checkboxes/radios para is_owner, is_resident, etc. -->
                            <v-col cols="12">
                                <h4>Definir Roles para esta Casa</h4>
                                <v-checkbox v-model="formDetails.is_owner" label="Es Propietario"></v-checkbox>
                                <v-checkbox v-model="formDetails.is_resident" label="Es Residente"></v-checkbox>
                                <v-checkbox v-model="formDetails.is_manager" label="Es Gestor (Admin App)"></v-checkbox>
                            </v-col>
                        </v-row>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="blue-darken-1" variant="text" @click="close">Cancelar</v-btn>
                            <v-btn color="red" type="submit">Guardar</v-btn>
                        </v-card-actions>
                    </div>
                    <!-- Indicador de carga general o mensajes -->
                    <v-row v-if="isLoadingHouses">
                        <v-col cols="12" class="text-center">
                            <v-progress-circular indeterminate color="primary"></v-progress-circular>
                            <p>Cargando casas...</p>
                        </v-col>
                    </v-row>
                    <v-row v-if="apiError">
                        <v-col cols="12">
                            <v-alert type="error" variant="tonal">
                                Error al cargar las casas: {{ apiError }}
                            </v-alert>
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
<style scoped>
/* Estilos específicos si los necesitas */
.v-text-field[readonly] {
    background-color: #f5f5f5; /* Un gris claro para indicar que no es editable */
    cursor: default;
}

.v-text-field[readonly] input {
    color: #555; /* Texto un poco más oscuro */
}
</style>
