<script setup>
import {formatDate, getUnitConsumption} from "../../utils/functions.js";
import {computed, onMounted, ref, watch} from "vue";
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
const labelUnit = computed(() => `Consumo ${getUnitConsumption(props.typeServiceUnit)}`);

const schema = yup.object({
    house_id: yup.object().required('La casa es requerida.'),
    payment_date: yup.string().required().min(10, 'Este campo es requerido.'),
});

const {handleSubmit, resetForm, setValues} = useForm({
    validationSchema: schema,
    initialValues: {
        payment_date: dayjs().format('YYYY-MM-DD'),
        quantity: '',
        replace: false,
        observations: '',
        documentFile: null,
    }
});

const quantity = useField('quantity');
const payment_date = useField('payment_date');
const replace = useField('replace');
const observations = useField('observations');
const file_path = useField('file_path');
const documentFile = useField('documentFile');
const house_id = useField('house_id');
const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const mySnackbar = ref(null);
const houses = ref([]);
const selectedHouse = ref(null); // Importante: usar return-object en v-autocomplete
const isLoadingHouses = ref(false);
const currentSearch = ref('');
const existingImageUrl = ref(null);

const isLoading = ref(false);

const submitForm = handleSubmit(async (values) => {

    isLoading.value = true;
    const formData = new FormData();
    formData.append('payment_date', values.payment_date);
    formData.append('quantity', values.quantity);
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

    // Doble chequeo por si acaso, aunque yup debería haberlo atrapado
    if (!fileToUpload && (props.element?.file_path && props.element.file_path.value === null)) {
        mySnackbar.value.show('Por favor, seleccione un archivo para subir.', 'error');
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
    console.log('Buscando:', value);
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
            replace: newValue.replace === 1 ,
            observations: newValue.observations || '',
            house_id: newValue.house || null,
            filepath: [],
        });
        existingImageUrl.value = newValue.file_path_url || null;
    } else {
        resetForm();
        existingImageUrl.value = null;
    }

}, {immediate: true, deep: true});

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
                <v-container>
                    <v-form @submit.prevent="submitForm">
                        <v-autocomplete
                            v-model="house_id.value.value"
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
                            :error-messages="house_id?.errorMessage.value"
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

                        <v-text-field
                            type="date"
                            variant="outlined"
                            label="Fecha"
                            v-model="payment_date.value.value"
                            :error-messages="payment_date.errorMessage.value"
                        />
                        <v-text-field
                            variant="outlined"
                            :label="labelUnit"
                            v-model="quantity.value.value"
                            placeholder="0"
                            type="number"
                        />
                        <v-textarea
                            v-model="observations.value.value"
                            label="Observaciones"
                            rows="3"
                            variant="outlined"
                            required
                        ></v-textarea>
                        <v-file-input
                            v-model="documentFile.value.value"
                            :error-messages="documentFile.errorMessage.value"
                            label="Selecciono una (Imagen)"
                            variant="outlined"
                            :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                            prepend-icon=""
                            show-size
                            clearable
                        ></v-file-input>
                        <v-switch
                            v-model="replace.value.value"
                            :label="replace.value.value ? 'Remplazo' : 'No Remplazo'"
                            color="success"
                            inset
                        ></v-switch>
                        <div v-if="isEditing && existingImageUrl" class="mb-3">
                            <v-img
                                :src="existingImageUrl"
                                max-height="150"
                                contain
                                alt="Imagen actual"
                                class="mb-2"
                            ></v-img>
                        </div>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="grey" @click="close" :disabled="isLoading">Cancelar</v-btn>
                            <v-btn color="red" type="submit" :loading="isLoading">Guardar</v-btn>
                        </v-card-actions>
                    </v-form>
                </v-container>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
