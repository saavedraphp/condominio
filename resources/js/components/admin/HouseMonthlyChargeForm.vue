<script setup>
import {computed, onMounted, reactive, ref, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import HouseSelector from "@/components/HouseSelector.vue";
import dayjs from "dayjs";

const emit = defineEmits(['update:modelValue', 'monthly-charge-created', 'annual-budget-edited']);
const props = defineProps({
    modelValue: Boolean,
    element: Object,
    urlBase: {
        type: String,
        required: true
    },
});

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Schema de validación con Yup
const schema = yup.object({
    year: yup.number()
        .typeError('El año debe ser un valor numérico.')
        .required('El año es obligatorio.')
        .integer('El año debe ser un número entero.')
        .positive('El año debe ser un número positivo.'),
    monthPeriod: yup.string()
        .required('El mes es obligatorio.')
        .matches(/^(0[1-9]|1[0-2])$/, 'El mes debe ser un número entre 01 y 12.'),
});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
//    validationSchema: schema,
    /*    initialValues: {
            year: dayjs().get('year'), // Año actual
            monthPeriod: dayjs().format('MM'),
            amount: '',
        }*/
});

// Campos de VeeValidate
const selectedBudgetType = ref(null);
const budgetTypes = ref([]);
const year = useField('year');
const amount = useField('amount');
const monthPeriod = useField('monthPeriod')

const isLoading = ref(false);
const mySnackbar = ref(null);
const errorMessage = ref(null);
const currentSearch = ref('');
const feedback = reactive({
    message: '',
    type: 'success', // 'success' o 'error'
});

// --- Computed Properties ---
const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Recibo por Mantenimiento' : 'Adicionar Recibo por Mantenimiento');

watch(() => props.element, (newValue) => {
    if (newValue) {
        year.value.value = newValue.year || "";
        amount.value.value = newValue.amount || "";
    }
}, {immediate: true});

watch(
    [() => props.element, budgetTypes],
    ([element, budgets]) => {
        if (element && budgets.length > 0) {
            selectedBudgetType.value = budgets.find(
                item => item.id === element.budget_type_id
            ) || null;
        }
    },
    {immediate: true}
);

const handlePreview = () => {
    if (!selectedHouseObject?.value?.id) {
        mySnackbar.value.show('Por favor seleccione una casa.', 'error');
        return;
    }

    //const params = new URLSearchParams(form);
    const params = {
        'house_id': selectedHouseObject.value.id,
        'is_preview': true,
    };
    const previewUrl = `/admin/receipt/preview?${new URLSearchParams(params).toString()}`;
    window.open(previewUrl, '_blank',
        'noopener,noreferrer,width=900,height=600,scrollbars=yes,resizable=yes');
};

const handleGenerateAndSave = async () => {
    const typeEmit = isEditing.value ? 'annual-budget-edited' : 'monthly-charge-created';
    isLoading.value = true;
    feedback.message = ''; // Limpiar mensajes anteriores

    if (!selectedHouseObject?.value?.id) {
        mySnackbar.value.show('Por favor seleccione una casa.', 'error');
        return;
    }

    const params = {
        'house_id': selectedHouseObject.value.id,
        'is_preview': true,
    };

    try {
        const response = await axios.post(`/admin/receipt/generate`, params);

/*        // Muestra el mensaje de éxito del backend
        feedback.type = 'success';
        feedback.message = response.data.message;*/

        if (response.data.success) {
            emit(typeEmit, response.data.message);
            close()
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        // Muestra el mensaje de error del backend o un mensaje genérico
        feedback.type = 'error';
        if (error.response && error.response.data && error.response.data.message) {
            feedback.message = error.response.data.message;
        } else {
            feedback.message = 'No se pudo completar la operación.';
        }
        console.error('Error:', error);
    } finally {
        // Se ejecuta siempre, haya éxito o error
        isLoading.value = false;
    }
};

const houseIdToEdit = ref(null);
const selectedHouseObject = ref(null);

const clearSelection = () => {
    selectedHouseObject.value = null; // Limpiar el objeto
    houseIdToEdit.value = null; // Limpiar el ID inicial para que no se vuelva a cargar
}

const setInitialToVilla = () => {
    // Cambiar dinámicamente el ID a editar
    houseIdToEdit.value = 18;
}

const close = () => {
    dialog.value = false;
    resetForm();
}
</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card>
            <v-card-title>{{ formTitle }}</v-card-title>
            <v-card-text>
                <v-alert
                    v-if="feedback.message"
                    :type="feedback.type"
                    variant="tonal"
                    class="mb-4"
                    closable
                    @click:close="feedback.message = ''"
                >
                    {{ feedback.message }}
                </v-alert>

                <v-form>
                    <v-row dense>
                        <v-col cols="12">
                            <HouseSelector
                                v-model="selectedHouseObject"
                                :initial-house-id="houseIdToEdit"
                            />
                        </v-col>
                        <v-col cols="12" md="6" v-if="false">
                            <v-text-field
                                variant="outlined"
                                label="Año Periodo"
                                v-model="year.value.value"
                                :error-messages="year.errorMessage.value"
                                maxlength="4"
                            />
                        </v-col>
                        <v-col cols="12" md="6" v-if="false">
                            <v-text-field
                                variant="outlined"
                                label="Mes Periodo"
                                v-model="monthPeriod.value.value"
                                :error-messages="monthPeriod.errorMessage.value"
                                type="number"
                            />
                        </v-col>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="grey" variant="flat" @click="close">Cancelar</v-btn>
                            <v-btn
                                color="secondary"
                                variant="tonal"
                                @click="handlePreview"
                                :disabled="isLoading"
                            >
                                Ver Vista Previa
                            </v-btn>
                            <v-btn color="primary" variant="flat"
                                   @click="handleGenerateAndSave"
                                   :loading="isLoading"
                                   :disabled="isLoading || !selectedHouseObject?.id">
                                Generar y Registrar
                            </v-btn>
                        </v-card-actions>

                        <v-alert type="error" variant="tonal" v-if="errorMessage">
                            {{ errorMessage }}
                        </v-alert>
                    </v-row>
                </v-form>
                <Snackbar ref="mySnackbar"/>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>


