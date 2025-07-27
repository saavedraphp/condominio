<script setup>
import {ref, watch} from "vue";
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
        name: '',
        email: '',
        phone: '',
        has_payment_arrangement: false,
        is_associated: false,
        status: true
    }
});

const name = useField('name');
const email = useField('email');
const phone = useField('phone')
const status = useField('status')
const has_payment_arrangement = useField('has_payment_arrangement', 'boolean');
const is_associated = useField('is_associated', 'boolean');

let formTitle = 'Adicionar Usuario';

watch(() => props.user, (newValue) => {
    if (newValue) {
        name.value.value = newValue.name || "";
        email.value.value = newValue.email || "";
        phone.value.value = newValue.phone || "";
        status.value.value = newValue.status === 'active';
        has_payment_arrangement.value.value = newValue.has_payment_arrangement || false;
        is_associated.value.value = newValue.is_associated || false;
    }
    if (props.user?.id) {
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
            has_payment_arrangement: values.has_payment_arrangement,
            is_associated: values.is_associated,
            status: values.status,
        });
    } else {
        emit('added', {
            name: values.name,
            email: values.email,
            phone: values.phone,
            has_payment_arrangement: values.has_payment_arrangement,
            is_associated: values.is_associated,
            status: values.status,
        });
    }

    resetForm({
        values: {
            name: '',
            email: '',
            phone: '',
            status: true,
            has_payment_arrangement: false,
            is_associated: false
        }
    });
    close();
});

const close = () => {
    emit('close-modal');
    resetForm();
}

const TABS_KEYS = {
    'DATA': 'data',
    'FILES': 'files'
};

const activeKey = ref(TABS_KEYS.DATA);
</script>
<template>
    <v-card>
        <v-card-title>
            <span class="text-h5">{{ formTitle }}</span>
        </v-card-title>
        <v-divider></v-divider>
        <v-tabs v-model="activeKey">
            <v-tab :value="TABS_KEYS.DATA">Datos</v-tab>
            <v-tab :value="TABS_KEYS.FILES">Archivos</v-tab>
        </v-tabs>
        <v-card-text>
            <v-window v-model="activeKey">
                <v-window-item :value="TABS_KEYS.DATA">
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
                                    <v-checkbox
                                        v-model="has_payment_arrangement.value.value"
                                        label="Tiene Arreglo de pagos"
                                        class="pa-0 ma-0"
                                        density="compact"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-checkbox
                                        v-model="is_associated.value.value"
                                        label="Es Asociado"
                                        class="pa-0 ma-0"
                                        density="compact"
                                    />
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
                        <blockquote v-if="false">*Se requiere verificación por correo electrónico.
                            Se enviará un mensaje a {{ email.value }} con los pasos para confirmar
                            su cuenta. Una vez confirmada, podrá acceder y modificar su contraseña.
                        </blockquote>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="grey" variant="flat" @click="close">Cancelar</v-btn>
                            <v-btn color="primary" variant="flat" type="submit">Guardar</v-btn>
                        </v-card-actions>
                    </v-form>
                </v-window-item>
                <v-window-item :value="TABS_KEYS.FILES">
                    <p>Archivos</p>
                </v-window-item>
            </v-window>
        </v-card-text>
    </v-card>
</template>

<style scoped>

</style>
