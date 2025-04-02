<template>
    <v-card>
        <v-card-title>Agregar Integrante</v-card-title>
        <v-card-text>
            <v-form @submit.prevent="submitForm">
                <v-text-field
                    variant="outlined"
                    label="Nombre"
                    v-model="memberName.value.value"
                    :error-messages="memberName.errorMessage.value"
                />
                <v-text-field
                    variant="outlined"
                    label="Teléfono"
                    v-model="memberPhone.value.value"
                    :error-messages="memberPhone.errorMessage.value"
                />
                <v-text-field
                    variant="outlined"
                    label="Correo Electrónico"
                    v-model="memberEmail.value.value"
                    :error-messages="memberEmail.errorMessage.value"
                />
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="close">Cancelar</v-btn>
                    <v-btn color="red" type="submit">Guardar</v-btn>
                </v-card-actions>
            </v-form>
        </v-card-text>
    </v-card>
</template>

<script setup>
import {ref, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';

const emit = defineEmits(['member-added', 'member-edit', 'close-modal']);
const props = defineProps({
    member: Object,
});


// Schema de validación con Yup
const schema = yup.object({
    memberName: yup.string().required().min(2, 'El nombre debe tener al menos 2 caracteres.'),
    memberPhone: yup.string().required().matches(/^\d+$/, 'El número de teléfono debe tener al menos 7 dígitos.').min(7),
    memberEmail: yup.string().required().email('Debe ser un correo electrónico válido.')
});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
    validationSchema: schema,
    initialValues: {
        memberName: 'Luis Saavedra',
        memberPhone: '960203783',
        memberEmail: 'saavedraphp@gmail.com'
    }
});

// Campos de VeeValidate
const memberName = useField('memberName');
const memberPhone = useField('memberPhone');
const memberEmail = useField('memberEmail');

watch(() => props.member, (newMember) => {
    if (newMember) {
        console.log('watchxx', newMember.name);
        memberName.value.value = newMember.name || "";
        memberPhone.value.value = newMember.phone || "";
        memberEmail.value.value = newMember.email || "";
    }
}, {immediate: true});

const submitForm = handleSubmit((values) => {
    if (props.member?.id) {
        emit('member-edit', {
            id: props.member.id,
            name: values.memberName,
            phone: values.memberPhone,
            email: values.memberEmail,
        });
    } else {
        emit('member-added', {
            name: values.memberName,
            phone: values.memberPhone,
            email: values.memberEmail,
        });
    }

    resetForm({values: {memberName: '', memberPhone: '', memberEmail: ''}});
    close();
});

const close = () => {
    emit('close-modal');
    resetForm(); // Limpiar el formulario al cerrar el modal
}
</script>
