<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";

const emit = defineEmits(['update:modelValue', 'annual-budget-created', 'annual-budget-edited']);
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
    amount: yup.string().required( 'El monto es requerido.'),
});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
    validationSchema: schema,
    initialValues: {
        year: '',
        amount: '',
    }
});

// Campos de VeeValidate
const selectedBudgetType = ref(null);
const budgetTypes = ref([]);
const isLoadingBudget = ref(false);
const year = useField('year');
const amount = useField('amount');
const isLoading = ref(false);
const mySnackbar = ref(null);
const errorMessage = ref(null);
const currentSearch = ref('');

// --- Computed Properties ---
const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Presupuesto' : 'Adicionar Presupuesto');

const getBudgetType = async () => {
    isLoadingBudget.value = true;
    budgetTypes.value = [];
    selectedBudgetType.value = null;
    try {
        const response = await axios.get(`/admin/budget-types`);
        budgetTypes.value = response.data.data || response.data || [];
    } catch (error) {
        console.error("Error obteniendo  las casas:", error);
        errorMessage.value = error.response?.data?.message || error.message || 'No se pudo conectar a la API.';
        budgetTypes.value = [];
    } finally {
        isLoadingBudget.value = false;
    }
};


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
    { immediate: true }
);

const submitForm = handleSubmit(async (values) => {
    if (!selectedBudgetType.value) {
        mySnackbar.value.show('Por favor selecciona un tipo de presupuesto.', 'error');
        return;
    }

    isLoading.value = true;
    const url = isEditing.value
        ? `${props.urlBase}/${props.element?.id}`
        : `${props.urlBase}/`;
    const method = isEditing.value ? 'put' : 'post';
    const typeEmit = isEditing.value ? 'annual-budget-edited' : 'annual-budget-created';

    const payload = {
        year: values.year,
        amount: values.amount,
    };

    if (selectedBudgetType.value) {
        payload.budget_type_id = selectedBudgetType.value.id;
    }

    try {
        const response = await axios({
            method: method,
            url: url,
            data: payload
        });
        if (response.data.success) {
            emit(typeEmit,response.data.message);
            close()
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response.data.message, 'error');
    } finally {
        isLoading.value = false;
    }
});

const onSearchInput = (value) => {
    console.log('Buscando:', value);
    currentSearch.value = value;
};

const close = () => {
    dialog.value = false;
    resetForm();
}

onMounted(() => {
    getBudgetType();
});
</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card>
            <v-card-title>{{ formTitle }}</v-card-title>
            <v-card-text>
                <v-form @submit.prevent="submitForm">
                    <v-autocomplete
                        v-model="selectedBudgetType"
                        :items="budgetTypes"
                        :loading="isLoadingBudget"
                        :disabled="isLoadingBudget"
                        item-title="Tipo de presupuesto"
                        item-value="id"
                        label="Buscar y seleccionar presupuesto..."
                        placeholder="Escribe el nombre..."
                        variant="outlined"
                        return-object
                        clearable
                        no-data-text="No se encontraron presupuestos"
                        @update:search="onSearchInput"
                    >
                        <!-- Opcional: Personalizar cómo se muestra cada item en la lista -->
                        <template v-slot:item="{ props, item }">
                            <v-list-item
                                v-bind="props"
                                :title="item.raw.name"
                            ></v-list-item>
                        </template>

                        <!-- Opcional: Mostrar algo más que el item-title cuando está seleccionado -->
                        <template v-slot:selection="{ item }">
                            <span>{{ item.raw.name }}</span>
                        </template>

                    </v-autocomplete>
                    <v-text-field
                        variant="outlined"
                        label="Año"
                        v-model="year.value.value"
                        :error-messages="year.errorMessage.value"
                        maxlength="4"
                    />
                    <v-text-field
                        variant="outlined"
                        label="Monto"
                        v-model="amount.value.value"
                        :error-messages="amount.errorMessage.value"
                        type="number"
                    />
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="grey" @click="close">Cancelar</v-btn>
                        <v-btn color="red" type="submit">Guardar</v-btn>
                    </v-card-actions>

                            <v-alert type="error" variant="tonal" v-if="errorMessage">
                               {{ errorMessage }}
                            </v-alert>
                </v-form>
                <Snackbar ref="mySnackbar"/>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>


