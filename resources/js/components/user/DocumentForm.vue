<script setup>
import {computed, ref, watch} from "vue";
import {useField, useForm} from 'vee-validate';
import Snackbar from "../Snackbar.vue"; // Asegúrate que la ruta sea correcta
import * as yup from "yup";
import axios from "axios";

// --- Emits ---
// document-submitted: Se emite en caso de éxito, puede pasar datos de respuesta si es necesario.
// close-modal: Se emite para cerrar el modal/dialogo.
const emit = defineEmits(['document-added', 'document-updated', 'close-modal']);

// --- Props ---
const props = defineProps({
    document: Object,
    default: null,
    apiUrl: {
        type: String,
        required: true,
    },
});

const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
// --- Refs ---
const isSubmitting = ref(false); // Renombrado de isRecording para claridad
const mySnackbar = ref(null);
const existingImageUrl = ref(null);


// --- Validación con Yup (simplificada) ---
const schema = yup.object({
    title: yup.string().required('El título es requerido.'),
    description: yup.string().required('La descripción es requerida.'),
    // Solo valida que se haya seleccionado *algo* en el campo de archivo
    /*    documentFile: yup.mixed()
            .required('Debe seleccionar un archivo.')
            // Asegurarse que el valor no sea null o undefined, y si es array, que no esté vacío.
            // v-file-input puede modelar null, File, o File[]
            .test('is-file-selected', 'Debe seleccionar un archivo.', value => {
                if (!value) return false; // null o undefined
                if (Array.isArray(value) && value.length === 0) return false; // Array vacío
                // Si es un array con un archivo, o un objeto File directamente, es válido aquí.
                // La validación de tipo/tamaño se hará en backend.
                return true;
            })*/
});

// --- Configuración de VeeValidate ---
const {handleSubmit, resetForm, setValues} = useForm({
    validationSchema: schema,
    initialValues: {
        title: '',
        description: '',
        documentFile: null, // Iniciar como null es común para v-file-input
    }
});

// --- Campos del Formulario ---
const title = useField('title');
const description = useField('description');
const documentFile = useField('documentFile'); // Campo para el v-file-input

const isEditing = computed(() => !!props.document?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Documento' : 'Adicionar Documento');

// --- Métodos ---
const submitForm = handleSubmit(async (values) => {
    let fileToUpload = null;

    if (Array.isArray(values.documentFile) && values.documentFile.length > 0) {
        fileToUpload = values.documentFile[0];
    } else if (values.documentFile instanceof File) {
        fileToUpload = values.documentFile;
    }

    if (!fileToUpload && (props.document?.file_path && props.document.file_path.value === null)) {
        mySnackbar.value.show('Por favor, seleccione un archivo para subir.', 'error');
        return;
    }

    // Crear FormData
    const formData = new FormData();
    formData.append('title', values.title);
    formData.append('description', values.description);
    // Asegúrate que el nombre 'file' coincida con lo que espera tu backend
    if (fileToUpload) {
        formData.append('file_path', fileToUpload, fileToUpload.name);
    }

    isSubmitting.value = true;

    try {
        const isEditingMode = isEditing.value;
        let url = props.apiUrl;
        const config = {
            headers: {
                'Accept': 'application/json',
            }
        };

        if (isEditingMode) {
            url = `${props.apiUrl}/${props.document.id}`;
            formData.append('_method', 'PUT');
        }

        const response = await axios.post(url, formData, config);

        if (response.data.success) {
            if (isEditingMode) {
                emit('document-updated', response.data.message);
            } else {
                emit('document-added', response.data.message);
            }
            close();
        } else {
            mySnackbar.value.show(response.data.message || 'Ocurrió un error inesperado.', 'error');
        }
    } catch (error) {
        console.error("Error submitting document:", error);
        let errorMessage = 'Lo sentimos, hubo un problema al guardar. Intenta de nuevo.';
        // Intentar obtener un mensaje más específico del error de backend si existe
        if (error.response && error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = error.message;
        }
        mySnackbar.value.show(errorMessage, 'error');
    } finally {
        isSubmitting.value = false;
    }
});

watch(() => props.document, (newValue) => {
    if (newValue) {
        setValues({
            id: newValue.id || null,
            title: newValue.title || '',
            description: newValue.description ?? null,
            file_path: [],
        });
        existingImageUrl.value = newValue.file_path || null;
    } else {
        resetForm();
        existingImageUrl.value = null;
    }
}, {immediate: true, deep: true});

const close = () => {
    emit('close-modal');
    resetForm();
};

</script>

<template>
    <v-card>
        <v-card-title>
            <span class="text-h5">{{ formTitle }}</span>
        </v-card-title>
        <v-card-text>
            <!-- Usar @submit.prevent en el <form> o <v-form> -->
            <v-form @submit.prevent="submitForm">
                <v-text-field
                    v-model="title.value.value"
                    :error-messages="title.errorMessage.value"
                    variant="outlined"
                    label="Título"
                    class="mb-3"
                ></v-text-field>

                <v-textarea
                    v-model="description.value.value"
                    :error-messages="description.errorMessage.value"
                    variant="outlined"
                    label="Descripción"
                    rows="3"
                    class="mb-3"
                ></v-textarea>
                <v-file-input
                    v-model="documentFile.value.value"
                    :error-messages="documentFile.errorMessage.value"
                    label="Seleccionar Archivo"
                    variant="outlined"
                    :accept="ACCEPTED_IMAGE_TYPES.join(',')"
                    prepend-icon=""
                    clearable
                    class="mb-3"
                ></v-file-input>
                <div v-if="document?.original_filename">
                    file: {{ document?.original_filename }}
                </div>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="grey"
                        variant="text"
                        @click="close"
                        :disabled="isSubmitting"
                    >
                        Cancelar
                    </v-btn>
                    <v-btn
                        color="primary"
                        type="submit"
                        :loading="isSubmitting"
                        :disabled="isSubmitting"
                    >
                        Guardar
                    </v-btn>
                </v-card-actions>
            </v-form>
        </v-card-text>
        <Snackbar ref="mySnackbar"/>
    </v-card>
</template>

<style scoped>
/* Estilos si son necesarios */
.mb-3 {
    margin-bottom: 1rem; /* Espaciado simple */
}
</style>
