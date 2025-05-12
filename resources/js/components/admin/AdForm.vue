<script setup>
// --- Título Computado para el Diálogo ---
import {watch} from "vue";
import {useField, useForm} from "vee-validate";
import * as yup from 'yup';

const emit = defineEmits(['ad-added', 'ad-edit', 'close-modal']);
/*const formTitle = computed(() => {
    return editedIndex.value === -1 ? 'Nuevo Anuncio' : 'Editar Anuncio';
});*/
const props = defineProps({
    ad: Object,
});

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
    description:  yup.string().required().min(20, 'la descripcion debe de tener al menos 20 caracteres.'),
    start_day: yup.string().required().min(10, 'la fecha de inicio es requerida.'),
   // phone_required: dateValidation.required('El teléfono es obligatorio'),
});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
    validationSchema: schema,
    initialValues: {
        title: '',
        description: '',
        start_day: '',
        end_day: '',
        active: true
    }
});

const title = useField('title');
const description = useField('description')
const start_day = useField('start_day')
const end_day = useField('end_day')
const active = useField('active')

let formTitle = 'Adicionar Anuncio';

watch(() => props.ad, (newValue) => {
    if (newValue) {
        title.value.value = newValue.title || "";
        description.value.value = newValue.description || "";
        start_day.value.value = newValue.start_day || "";
        end_day.value.value = newValue.end_day || "";
        active.value.value = newValue.active === true;
    }
    if(props.ad?.id) {
        formTitle = 'Editar Anuncio';
    }
}, {immediate: true});



const submitForm = handleSubmit((values) => {
    if (props.ad?.id) {
        emit('ad-edit', {
            id: props.ad.id,
            title: values.title,
            description: values.description,
            start_day: values.start_day,
            end_day: values.end_day,
            active: values.active ? 1 : 0,
        });
    } else {
        emit('ad-added', {
            title: values.title,
            description: values.description,
            start_day: values.start_day,
            end_day: values.end_day,
            active: values.active ? 1 : 0,
        });
    }

    resetForm({values: {title: '', description: '', start_day: '', end_day: '', active: true}});
    close();
});

const close = () => {
    emit('close-modal');
    resetForm();
}

</script>

<template>
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
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="end_day.value.value"
                                :error-messages="end_day.errorMessage.value"
                                label="Fecha de Fin (YYYY-MM-DD)"
                                type="date"
                                variant="outlined"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-switch
                                v-model="active.value.value"
                                :label="active.value.value ? 'Activo' : 'Inactivo'"
                                color="success"
                                inset
                            ></v-switch>
                        </v-col>
                    </v-row>
                </v-container>
                <small>*Campos requeridos</small>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue-darken-1" variant="text" @click="close">Cancelar</v-btn>
                    <v-btn color="red" type="submit">Guardar</v-btn>
                </v-card-actions>
            </v-form>
        </v-card-text>
    </v-card>
</template>
