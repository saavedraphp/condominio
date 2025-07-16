<script setup>
import {formatDate, getUnitConsumption} from "../../utils/functions.js";
import {computed, onMounted, ref, watch, watchEffect} from "vue";
import {useField, useForm} from "vee-validate";
import * as yup from "yup";
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import dayjs from 'dayjs';


const emit = defineEmits(['payment-created', 'payment-updated', 'update:modelValue']);
const props = defineProps({
    modelValue: Boolean,
    element: {
        type: Object,
        default: {
            replace: false
        }
    },
    typeServiceUnit: {
        type: String,
        default: 'kWh'
    },
    typeServiceId: {
        type: Number,
        default: null
    },
    urlBase: {
        type: String,
        required: true
    },
    isAdmin: {
        type: Boolean,
        default: false
    }

});

const dialog = computed(({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
}));

const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar comsumo' : 'Adicionar comsumo');
const labelUnit = computed(() => `Lectura Actual ${getUnitConsumption(props.typeServiceUnit)}`);
const labelConsumption = computed(() => `Comsumo ${getUnitConsumption(props.typeServiceUnit)}`);

const schema = yup.object({
    house_id: yup.object().required('La casa es requerida.'),
    payment_date: yup.string().required().min(10, 'Este campo es requerido.'),
    quantity: yup
        .number()
        .transform((value, originalValue) => {
            return originalValue === '' ? undefined : value;
        })
        .required('La lectura es requerida.').min(0, 'La lectura debe ser un número positivo.'),
});

const {handleSubmit, resetForm, setValues, setFieldValue } = useForm({
    validationSchema: schema,
    initialValues: {
        payment_date: dayjs().format('YYYY-MM-DD'),
        quantity: '',
        consumption: 0,
        replace: false,
        observations: '',
        documentFile: null,
    }
});
// LSL DESESTRUCTURACIÓN DE CAMPOS
// --- Desestructuración de useField (Sin cambios) ---
const { value: house_id, errorMessage: house_idError } = useField('house_id');
const { value: payment_date, errorMessage: payment_dateError } = useField('payment_date');
const { value: quantity, errorMessage: quantityError } = useField('quantity');
const { value: consumption, errorMessage: consumptionError } = useField('consumption'); // Mantenemos `consumption` para el submit
const { value: replace } = useField('replace');
const { value: observations } = useField('observations');
const { value: documentFile, errorMessage: documentFileError } = useField('documentFile');

const file_path = useField('file_path');
const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const mySnackbar = ref(null);
const houses = ref([]);
const selectedHouse = ref(null); // Importante: usar return-object en v-autocomplete
const isLoadingHouses = ref(false);
const currentSearch = ref('');
const existingImageUrl = ref(null);
const quantityLast = ref('');
const labelLastQuantity = ref('Ultima Lectura');
const isLoadingLastConsumption = ref(false);

const isLoading = ref(false);

const submitForm = handleSubmit(async (values) => {

    isLoading.value = true;
    const formData = new FormData();
    formData.append('payment_date', values.payment_date);
    formData.append('quantity', values.quantity);
    formData.append('consumption', values.consumption);
    formData.append('replace', values.replace ? 1 : 0);
    formData.append('observations', values.observations);
    formData.append('service_id', props.typeServiceId);
    formData.append('house_id', values.house_id.id);


    let fileToUpload = null;
    const proofValue = values.documentFile;
    if (Array.isArray(proofValue) && proofValue.length > 0) {
        fileToUpload = proofValue[0];
    } else if (proofValue instanceof File) {
        fileToUpload = proofValue;
    }

// Validación del archivo
    // --- Validación Corregida y Simplificada ---
    // Un archivo es requerido si no hay uno nuevo Y no había uno existente.
    if (!fileToUpload && !existingImageUrl.value) {
        mySnackbar.value.show('Debe seleccionar una imagen.', 'error');
        isLoading.value = false;
        return;
    }

    if (fileToUpload instanceof File) {
        formData.append('file_path', fileToUpload, fileToUpload.name);
    }

    try {
        const isEditingMode = isEditing.value;
        let url = props.urlBase;
        const config = {
            headers: {
                'Accept': 'application/json',
            }
        };

        if (isEditingMode) {
            url = `${props.urlBase}/${props.element.id}`;
            formData.append('_method', 'PUT');
        }
        const response = await axios.post(url, formData, config);

        if (response.data.success) {
            if (isEditingMode) {
                emit('payment-updated', response.data.message);
            } else {
                emit('payment-created', response.data.message);
            }
            close();
        } else {
            mySnackbar.value.show(response.data.message || 'Ocurrió un error inesperado.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response.data.message, 'error');
    } finally {
        isLoading.value = false;
    }
});

async function getHouses() {
    isLoadingHouses.value = true;
    houses.value = [];
    selectedHouse.value = null;
    try {
        const response = await axios.get(`/admin/houses/`);
        houses.value = response.data;
    } catch (error) {
        mySnackbar.value.show(error.response.data.message, 'error');
        houses.value = [];
    } finally {
        isLoadingHouses.value = false;
    }
}

const onSearchInput = (value) => {
    currentSearch.value = value;
};

const close = () => {
    dialog.value = false;
    resetForm();
}

watch(() => props.element, (newValue) => {
    if (newValue) {
        setValues({
            id: newValue.id || null,
            payment_date: newValue.payment_date.substring(0, 10),
            quantity: newValue.quantity || 0,
            replace: newValue.replace,
            //consumption: newValue.consumption_calculated || 0,
            observations: newValue.observations || '',
            house_id: newValue.house || null,
            filepath: [],
        });
        existingImageUrl.value = newValue.file_path_url || null;
    } else {
        resetForm();
        quantityLast.value = 0;
        labelLastQuantity.value = 'Última Lectura';
        existingImageUrl.value = null;
    }

}, {immediate: true, deep: true});

// --- LÓGICA DE CÁLCULO (Sin cambios) ---
const consumptionCalculated = computed(() => {
    const last = parseFloat(quantityLast.value) || 0;
    const current = parseFloat(quantity.value) || 0;
    const result = current - last;
    return result >= 0 ? result : 0;
});

// Actualiza el campo de formulario 'consumption' para el submit
watch(consumptionCalculated, (newConsumption) => {
    setFieldValue('consumption', newConsumption);
});


// El watchEffect sigue siendo perfecto para la reactividad DINÁMICA.
// No se ejecutará al abrir el modal de edición porque los valores ya están seteados.
// Pero SÍ se ejecutará si el usuario CAMBIA la fecha o la casa.
watchEffect(async () => {
    // Para evitar que se ejecute con los valores iniciales del modo edición,
    // comprobamos si el modal está abierto y si no estamos usando el valor histórico.
    // Una forma simple es verificar si el label ha sido cambiado por este watchEffect.

    if (!dialog.value) return;


    const selectedHouse = house_id.value;
    const date = payment_date.value;

    if (!selectedHouse || !selectedHouse.id || !date || isNaN(new Date(date).getTime())) {
        quantityLast.value = 0;
        labelLastQuantity.value = 'Última Lectura';
        return;
    }

    isLoadingLastConsumption.value = true;
    try {
        const response = await axios.get(`/admin/houses/${selectedHouse.id}/last-consumption/`, {
            params: {
                type_service: props.typeServiceId,
                payment_date: date,
            }
        });
        if (response.data.success && response.data.data) {
            labelLastQuantity.value = `Última Lectura (${formatDate(response.data.data.payment_date)})`;
            quantityLast.value = response.data.data.quantity;
        } else {
            labelLastQuantity.value = 'Última Lectura (sin datos previos)';
            quantityLast.value = 0;
        }
    } catch (error) {
        labelLastQuantity.value = 'Última Lectura (error)';
        quantityLast.value = 0;
        if (mySnackbar.value) {
            mySnackbar.value.show(error.response?.data?.message || "Error al obtener última lectura", 'error');
        }
    } finally {
        isLoadingLastConsumption.value = false;
    }
});

// En el 'consumption' computed, ahora es más simple
const consumptionFinal = computed(() => {
    const last = parseFloat(quantityLast.value) || 0;
    if( last === 0 ) {
        return 0; // Si no hay última lectura, el consumo es 0
    }
    const current = parseFloat(quantity.value) || 0;
    const result = current - last;
    return result >= 0 ? result : 0;
});

// Tenemos que asegurarnos que el campo de formulario 'consumption' se actualice.
watch(consumptionFinal, (newValue) => {
    setFieldValue('consumption', newValue);
});

onMounted(() => {
    if (props.isAdmin) {
        getHouses();
    }
});
</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card>
            <v-card-title>
                {{ formTitle }}
            </v-card-title>
            <v-card-text>
                <v-form @submit.prevent="submitForm">
                        <v-row dense>
                            <v-col cols="12">
                                <v-autocomplete
                                    v-model="house_id"
                                    :items="houses"
                                    :loading="isLoadingHouses"
                                    :disabled="isLoadingHouses"
                                    item-title="address"
                                    item-value="id"
                                    label="Buscar y seleccionar casa..."
                                    placeholder="Escribe el nombre..."
                                    variant="outlined"
                                    return-object
                                    clearable
                                    no-data-text="No se encontraron casas"
                                    @update:search="onSearchInput"
                                    :error-messages="house_idError"
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
                            <v-col cols="12">
                                <v-text-field
                                    v-model="payment_date"
                                    :error-messages="payment_dateError"
                                    type="date"
                                    variant="outlined"
                                    label="Fecha"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    variant="outlined"
                                    :label="labelLastQuantity"
                                    v-model="quantityLast"
                                    placeholder="0"
                                    type="number"
                                    readonly
                                    color="grey"
                                    hide-details
                                    class="read-only-field"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    variant="outlined"
                                    :label="labelUnit"
                                    v-model="quantity"
                                    :error-messages="quantityError"
                                    placeholder="0"
                                    type="number"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    variant="outlined"
                                    :label="labelConsumption"
                                    v-model="consumptionFinal"
                                    placeholder="0"
                                    type="number"
                                    :error-messages="consumptionError"
                                    readonly
                                    color="grey"
                                    hide-details
                                    class="read-only-field"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="observations"
                                    label="Observaciones"
                                    rows="3"
                                    variant="outlined"
                                    required
                                ></v-textarea>
                            </v-col>
                            <v-col cols="12">
                                <v-file-input
                                    v-model="documentFile"
                                    :error-messages="documentFileError"
                                    label="Selecciono una (Imagen)"
                                    variant="outlined"
                                    :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                                    prepend-icon=""
                                    show-size
                                    clearable
                                ></v-file-input>
                            </v-col>
                            <v-col cols="12">
                                <v-switch
                                    v-model="replace"
                                    :label="replace ? 'Remplazo' : 'No Remplazo'"
                                    color="success"
                                    inset
                                ></v-switch>
                            </v-col>
                            <v-col cols="12" v-if="isEditing && existingImageUrl" class="mb-3">
                                <v-img
                                    :src="existingImageUrl"
                                    max-height="150"
                                    contain
                                    alt="Imagen actual"
                                    class="mb-2"
                                ></v-img>
                            </v-col>
                        </v-row>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="grey" variant="flat" @click="close" :disabled="isLoading">Cancelar</v-btn>
                        <v-btn color="primary" variant="flat" type="submit" :loading="isLoading">Guardar</v-btn>
                    </v-card-actions>
                </v-form>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
<style scoped>
.read-only-field {
    pointer-events: none;
    background-color: #f5f5f5;
    border-radius: 8px;
}
</style>
