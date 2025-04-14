<script setup>
import {watch} from "vue";
import {useField, useForm} from "vee-validate";
import * as yup from 'yup';

const emit = defineEmits(['added', 'edit', 'close-modal']);

const props = defineProps({
    user: Object,
});

 // Schema de validación con Yup
const schema = yup.object({
    name: yup.string().required().min(2, 'El nombre debe tener al menos 2 caracteres.'),
    email: yup.string().required().email('Debe ser un correo electrónico válido.'),
    phone: yup.string().required().matches(/^\d+$/, 'El número de teléfono debe tener al menos 7 dígitos.').min(7),

});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
    validationSchema: schema,
    initialValues: {
        name: 'Adecon Peru',
        email: 'adeconperuventas@gmail.com',
        phone: '960203783',
        status: true
    }
});

const name = useField('name');
const email = useField('email');
const phone = useField('phone')
const status = useField('status')

let formTitle = 'Adicionar Usuario';

watch(() => props.user, (newValue) => {
    if (newValue) {
        name.value.value = newValue.name || "";
        email.value.value = newValue.email || "";
        phone.value.value = newValue.phone || "";
        status.value.value = newValue.status === 'active';
    }
    if(props.user?.id) {
        formTitle = 'Editar Usuario';
    }
}, {immediate: true});

const submitForm = handleSubmit((values) => {
    if (props.user?.id) {
        emit('edit', {
            id: props.user.id,
            name: values.name,
            email: values.email,
            phone: values.phone,
            status: values.status,
        });
    } else {
        emit('added', {
            name: values.name,
            email: values.email,
            phone: values.phone,
            status: values.status,
        });
    }

    resetForm({values: {name: '', email: '', phone: '', status: true}});
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
                                v-model="name.value.value"
                                :error-messages="name.errorMessage.value"
                                label="Name*"
                                variant="outlined"
                                required
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                v-model="email.value.value"
                                :error-messages="email.errorMessage.value"
                                label="Email*"
                                variant="outlined"
                                required
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                v-model="phone.value.value"
                                :error-messages="phone.errorMessage.value"
                                label="Teléfono*"
                                variant="outlined"
                                required
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-switch
                                v-model="status.value.value"
                                :label="status.value.value ? 'Activo' : 'Inactivo'"
                                color="success"
                                inset
                            ></v-switch>
                        </v-col>
                    </v-row>
                </v-container>
                <small>*Campos requeridos</small>
                <blockquote>*Se requiere verificación por correo electrónico.
                    Se enviará un mensaje a {{ email.value}} con los pasos para confirmar
                    su cuenta. Una vez confirmada, podrá acceder y modificar su contraseña.</blockquote>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue-darken-1" variant="text" @click="close">Cancelar</v-btn>
                    <v-btn color="red" type="submit">Guardar</v-btn>
                </v-card-actions>
            </v-form>
        </v-card-text>
    </v-card>
</template>

<style scoped>

</style>
