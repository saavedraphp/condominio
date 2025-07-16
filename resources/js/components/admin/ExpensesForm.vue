<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";

const emit = defineEmits(['update:modelValue', 'expense-created', 'expense-edited']);
const props = defineProps({
    modelValue: Boolean,
    element: Object,
    urlBase: {
        type: Object,
        required: true
    },
    budgetScope: {
        type: String,
        required: true
    }
});

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Schema de validación con Yup
const schema = yup.object({
    description: yup.string().required().min(2, 'El año debe tener al menos 4 caracteres.'),
    amount: yup.string().required('El monto es requerido.'),
    expense_date: yup.string().required().min(10, 'la fecha de inicio es requerida.'),
});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
    validationSchema: schema,
    initialValues: {
        description: '',
        amount: '',
        expense_date: new Date().toISOString().split('T')[0], // Formato YYYY-MM-DD
        documentFile: null,
    }
});

const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const existingImageUrl = ref(null);
// Campos de VeeValidate
const selectedAnnualBudget = ref(null);
const annualBudget = ref([]);
const isLoadingBudget = ref(false);
const expense_date = useField('expense_date');
const description = useField('description');
const {value: documentFile, errorMessage: documentFileError} = useField('documentFile');
const amount = useField('amount');
const isRecording = ref(false);
const mySnackbar = ref(null);
const errorMessage = ref(null);
const currentSearch = ref('');

// --- Computed Properties ---
const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar gasto' : 'Adicionar gasto');

const getAnnualBudget = async () => {
    isLoadingBudget.value = true;
    annualBudget.value = [];
    selectedAnnualBudget.value = null;
    try {
        const response = await axios.get(`${props.urlBase['budget_scope']}/`, {
            params: {
                budget_scope: props.budgetScope,
                search: currentSearch.value,
            }
        });
        annualBudget.value = response.data.data || response.data || [];
    } catch (error) {
        console.error("Error obteniendo  los presupuestos:", error);
        errorMessage.value = error.response?.data?.message || error.message || 'No se pudo conectar a la API.';
        annualBudget.value = [];
    } finally {
        isLoadingBudget.value = false;
    }
};

watch(() => props.element, (newValue) => {
    if (newValue) {
        description.value.value = newValue.description || "";
        amount.value.value = newValue.amount || "";
        expense_date.value.value = new Date(newValue.expense_date).toISOString().split('T')[0] || "";
        existingImageUrl.value = newValue.file_path_url || null;
    }else {
        resetForm();
        existingImageUrl.value = null;
    }
}, {immediate: true});

watch(
    [() => props.element, annualBudget],
    ([element, budgets]) => {
        if (element && budgets.length > 0) {
            selectedAnnualBudget.value = budgets.find(
                item => item.id === element.annual_budget_id
            ) || null;
        }
    },
    {immediate: true}
);

const submitForm = handleSubmit(async (values) => {
    if (!selectedAnnualBudget.value) {
        mySnackbar.value.show('Por favor selecciona un tipo de gasto.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('description', values.description);
    formData.append('amount', values.amount);
    formData.append('expense_date', values.expense_date);
    formData.append('annual_budget_id', selectedAnnualBudget.value.id);

    let fileToUpload = null;
    const proofValue = values.documentFile;

    if (Array.isArray(proofValue) && proofValue.length > 0) {
        fileToUpload = proofValue[0];
    } else if (proofValue instanceof File) {
        fileToUpload = proofValue;
    }
    // Doble chequeo por si acaso, aunque yup debería haberlo atrapado
    if (!fileToUpload && !props.element?.file_path) {
        mySnackbar.value.show('Por favor, seleccione una imagen para subir.', 'error');
        return;
    }

    if (fileToUpload instanceof File) {
        formData.append('file_path', fileToUpload, fileToUpload.name);
    }

    isRecording.value = true;
    let url = `${props.urlBase['base']}/`;

    if (isEditing.value) {
        url = `${props.urlBase['base']}/${props.element?.id}`
        formData.append('_method', 'PUT');
    }

    const typeEmit = isEditing.value ? 'expense-edited' : 'expense-created';

    const config = {
        headers: {
            'Accept': 'application/json',
        }
    };

    try {
        const response = await axios.post(url, formData, config);
        if (response.data.success) {
            emit(typeEmit, response.data.message);
            close()
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response.data.message, 'error');
    } finally {
        isRecording.value = false;
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
    getAnnualBudget();
});
</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card>
            <v-card-title>{{ formTitle }}</v-card-title>
            <v-card-text>
                <v-form @submit.prevent="submitForm">
                    <v-autocomplete
                        v-model="selectedAnnualBudget"
                        :items="annualBudget"
                        :loading="isLoadingBudget"
                        :disabled="isLoadingBudget"
                        item-title="Tipo de gasto"
                        item-value="id"
                        label="Buscar y seleccionar el tipo de gasto..."
                        placeholder="Escribe el nombre..."
                        variant="outlined"
                        return-object
                        clearable
                        no-data-text="No se encontraron gastos"
                        @update:search="onSearchInput"
                    >
                        <!-- Opcional: Personalizar cómo se muestra cada item en la lista -->
                        <template v-slot:item="{ props, item }">
                            <v-list-item
                                v-bind="props"
                                :title="`${item.raw.budget_type.name} (${item.raw.year})`"
                            ></v-list-item>
                        </template>

                        <!-- Opcional: Mostrar algo más que el item-title cuando está seleccionado -->
                        <template v-slot:selection="{ item }">
                            <span>{{ item.raw.budget_type.name }} ({{ item.raw.year }})</span>
                        </template>

                    </v-autocomplete>
                    <v-text-field
                        v-model="description.value.value"
                        :error-messages="description.errorMessage.value"
                        variant="outlined"
                        label="Descripción del gasto"
                    />
                    <v-text-field
                        v-model="amount.value.value"
                        :error-messages="amount.errorMessage.value"
                        variant="outlined"
                        label="Monto"
                        type="number"
                    />
                    <v-text-field
                        v-model="expense_date.value.value"
                        :error-messages="expense_date.errorMessage.value"
                        variant="outlined"
                        label="Fecha del gasto"
                        type="date"
                    />
                    <v-file-input
                        v-model="documentFile"
                        :error-messages="documentFileError"
                        label="Selecciono una (Imagen) maximo 2MB"
                        variant="outlined"
                        :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                        prepend-icon=""
                        show-size
                        clearable
                    ></v-file-input>
                    <div v-if="existingImageUrl" class="mb-3">
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
                        <v-btn color="grey"
                               variant="flat"
                               @click="close"
                               :disabled="isRecording"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn color="primary"
                               variant="flat"
                               type="submit"
                               :loading="isRecording"
                               :disabled="isRecording"
                        >
                            Guardar
                        </v-btn>
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


