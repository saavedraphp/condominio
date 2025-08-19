<script setup>
import {computed, ref, watch} from "vue";
import {useField, useForm} from "vee-validate";
import * as yup from 'yup';
import Snackbar from "@/components/Snackbar.vue";
import axios from "axios";

const emit = defineEmits(['added', 'edited', 'close-modal', 'update:modelValue', 'refresh-data']);

const props = defineProps({
    modelValue: Boolean,
    element: Object,
    routes: {
        type: Object,
        required: true,
    },
    required: true,
});

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Schema de validación con Yup
const schema = yup.object({
    title: yup.string().required().min(2, 'El titulo debe tener al menos 5 caracteres.'),
    code: yup.string().required().min(2, 'El codigo  debe tener al menos 5 caracteres.'),
});

// Configuración de vee-validate
const {handleSubmit, resetForm, setValues} = useForm({
    validationSchema: schema,
    initialValues: {
        title: '',
        description: '',
        code: '',
    }
});

const {value: title, errorMessage: titleError} = useField('title');
const {value: description, errorMessage: descriptionError} = useField('description');
const {value: code, errorMessage: codeError} = useField('code');
const isLoading = ref(false);
const mySnackbar = ref(null);

const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Código QR' : 'Adicionar Código QR');

watch(() => props.element, (newValue) => {
    if (newValue && newValue.id) {
        setValues({
            title: newValue.title || "",
            description: newValue.description || "",
            code: newValue.code || "",
        });
    } else {
        resetForm();
    }
}, {immediate: true});

const submitForm = handleSubmit(async (values) => {

    const formData = new FormData();
    formData.append('title', values.title);
    formData.append('description', values.description);
    formData.append('code', values.code);
    formData.append('type','PATROL_CHECKPOINT')

    try {
        isLoading.value = true;
        let url = props.routes.base;
        let action = 'added';
        if (isEditing.value) {
            url = `${props.routes.base}/${props.element.id}`
            formData.append('_method', 'PUT');
            action = 'edited';
        }

        let response = await axios.post(url, formData, {
            headers: {'Content-Type': 'multipart/form-data'}
        });

        if (response.data.success) {
            emit(action, response.data.message);
            close();
        } else {
            mySnackbar.value.show(response.data.message || 'Ocurrió un error inesperado.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Error al grabar el registro.', 'error');
        console.error(error);
    } finally {
        isLoading.value = false;
    }

});

const selectedElement = ref(null);

const close = () => {
    dialog.value = false;
}

</script>
<template>
    <v-dialog :model-value="dialog" persistent max-width="800px" scrollable>
        <v-card>
            <v-card-title>
                <span class="text-h5">{{ formTitle }}</span>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text>
                <v-form @submit.prevent="submitForm">
                    <v-container>
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="title"
                                    :error-messages="titleError"
                                    label="Título*"
                                    variant="outlined"
                                    density="compact"
                                    required
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="description"
                                    :error-messages="descriptionError"
                                    label="Descipción"
                                    variant="outlined"
                                    rows="4"
                                    density="compact"
                                ></v-textarea>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="code"
                                    :error-messages="codeError"
                                    label="Código*"
                                    placeholder="Ejemplo: PUERTA-01"
                                    variant="outlined"
                                    density="compact"
                                    required
                                    @input="code = code.toUpperCase()"
                                    :maxlength="14"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </v-container>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="grey" variant="flat" @click="close">Cancelar</v-btn>
                        <v-btn color="primary" variant="flat" type="submit"
                               :loading="isLoading"
                               :disabled="isLoading">Guardar
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
