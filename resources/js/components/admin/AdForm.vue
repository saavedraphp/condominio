<script setup>
// --- Título Computado para el Diálogo ---
import {computed, ref, watch} from "vue";
import {useField, useForm} from "vee-validate";
import * as yup from 'yup';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";

const emit = defineEmits(['ad-created', 'ad-updated', 'close-modal', 'update:modelValue']);
const props = defineProps({
    modelValue: Boolean,
    element: {
        type: Object,
        default: {
            active: false
        }
    },
    urlBase: {
        type: String,
        required: true
    },
    isReadonly: {
        type: Boolean,
        default: false
    }
});

const dialog = computed(({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
}));

const dateValidation = yup
    .string()
    .test(
        'is-valid-date',
        'Fecha inválido',
        (value) => {
            if (!value) return true; // Permite valores vacíos/nulos/undefined (considerado válido por *esta* regla)
            return /^[0-9-]{7,}$/.test(value);
        }
    );
// Schema de validación con Yup
const schema = yup.object({
    title: yup.string().required().min(10, 'El título tiene que tener al menos 10 caracteres.'),
    description: yup.string().required().min(20, 'la descripcion debe de tener al menos 20 caracteres.'),
    start_day: yup.string().required().min(10, 'la fecha de inicio es requerida.'),
    // phone_required: dateValidation.required('El teléfono es obligatorio'),
});

// Configuración de vee-validate
const {handleSubmit, resetForm, setValues} = useForm({
    validationSchema: schema,
    initialValues: {
        title: 'Anuncio de prueba',
        description: 'Detalle del anuncio de prueba',
        start_day: '2025-01-02',
        end_day: '2025-02-02',
        active: true,
        documentFile: null,
    }
});

const title = useField('title');
const description = useField('description')
const start_day = useField('start_day')
const end_day = useField('end_day')
const active = useField('active')
const documentFile = useField('documentFile');
const existingImageUrl = ref(null);
const isLoading = ref(false);
const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const mySnackbar = ref(null);


const isEditing = computed(() => !!props.element?.id);
const formTitle = computed(() => {
    if (props.isReadonly) {
        return 'Ver Anuncio';
    } else {
        return isEditing.value ? 'Editar Anuncio' : 'Adicionar Anuncio';
    }
});

watch(() => props.element, (newValue) => {
    if (newValue) {
        setValues({
            id: newValue.id || null,
            title: newValue.title || "",
            description: newValue.description || "",
            start_day: newValue.start_day || "",
            end_day: newValue.end_day || "",
            active: newValue.active === true,
        });
        existingImageUrl.value = newValue.file_path_url || null;
    } else {
        resetForm();
        existingImageUrl.value = null;
    }
}, {immediate: true, deep: true});


const submitForm = handleSubmit(async (values) => {
    isLoading.value = true;
    const formData = new FormData();
    formData.append('title', values.title);
    formData.append('description', values.description);
    formData.append('start_day', values.start_day);
    formData.append('end_day', values.end_day);
    formData.append('active', values.active ? 1 : 0);

    let fileToUpload = null;
    const proofValue = values.documentFile;

    if (Array.isArray(proofValue) && proofValue.length > 0) {
        fileToUpload = proofValue[0];
    } else if (proofValue instanceof File) {
        fileToUpload = proofValue;
    }

    // Doble chequeo por si acaso, aunque yup debería haberlo atrapado
    if (!fileToUpload && !props.element?.file_path) {
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
                emit('ad-updated', response.data.message);
            } else {
                emit('ad-created', response.data.message);
            }
            close();
        } else {
            mySnackbar.value.show(response.data.message || 'Ocurrió un error inesperado.', 'error');
        }
    } catch (error) {
        //mySnackbar.value.show(error.response.data.message, 'error');
        let errorMessage = 'Lo sentimos, hubo un problema al guardar. Intenta de nuevo.';
        if (error.response && error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = error.message;
        }
        mySnackbar.value.show(errorMessage, 'error');
    } finally {
        isLoading.value = false;
    }
});

const close = () => {
    dialog.value = false;
    resetForm();
    emit('close-modal');
}

</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card>
            <v-card-title>
                <span class="text-h5">{{ formTitle }}</span>
            </v-card-title>
            <v-card-text>
                <v-form @submit.prevent="submitForm">
                    <v-container>
                        <v-row>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="title.value.value"
                                    :error-messages="title.errorMessage.value"
                                    label="Título del Anuncio*"
                                    required
                                    variant="outlined"
                                    :readonly="props.isReadonly"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="description.value.value"
                                    :error-messages="description.errorMessage.value"
                                    label="Contenido del Anuncio*"
                                    rows="3"
                                    variant="outlined"
                                    required
                                    :readonly="props.isReadonly"
                                ></v-textarea>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <!-- Aquí podrías usar v-date-picker o un componente similar -->
                                <v-text-field
                                    v-model="start_day.value.value"
                                    :error-messages="start_day.errorMessage.value"
                                    label="Fecha de Inicio (YYYY-MM-DD)"
                                    variant="outlined"
                                    type="date"
                                    :readonly="props.isReadonly"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="end_day.value.value"
                                    :error-messages="end_day.errorMessage.value"
                                    label="Fecha de Fin (YYYY-MM-DD)"
                                    type="date"
                                    variant="outlined"
                                    :readonly="props.isReadonly"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-file-input v-show="!props.isReadonly"
                                              v-model="documentFile.value.value"
                                              :error-messages="documentFile.errorMessage.value"
                                              label="Selecciono una (Imagen)"
                                              variant="outlined"
                                              :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                                              prepend-icon=""
                                              show-size
                                              clearable

                                ></v-file-input>
                            </v-col>
                            <v-col cols="12">
                                <v-switch v-show="!props.isReadonly"
                                          v-model="active.value.value"
                                          :label="active.value.value ? 'Activo' : 'Inactivo'"
                                          color="success"
                                          inset
                                ></v-switch>
                            </v-col>
                            <v-col>
                                <div v-if="existingImageUrl" class="mb-3">
                                    <v-img
                                        :src="existingImageUrl"
                                        max-height="150"
                                        contain
                                        alt="Imagen actual"
                                        class="mb-2"
                                    ></v-img>
                                </div>
                            </v-col>
                        </v-row>
                    </v-container>
                    <small>*Campos requeridos</small>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="blue-darken-1" variant="text" @click="close">Cancelar</v-btn>
                        <v-btn color="red" type="submit" v-show="!props.isReadonly">Guardar</v-btn>
                    </v-card-actions>
                </v-form>
            </v-card-text>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
