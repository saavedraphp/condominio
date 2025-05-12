<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {useField, useForm} from 'vee-validate';
import * as yup from 'yup';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";


const emit = defineEmits(['update:modelValue', 'member-created', 'member-edited']);
const props = defineProps({
    modelValue: Boolean,
    user: Object,
    house: Object,
    member: Object,
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
    memberName: yup.string().required().min(2, 'El nombre debe tener al menos 2 caracteres.'),
    memberPhone: yup.string().required().matches(/^\d+$/, 'El número de teléfono debe tener al menos 7 dígitos.').min(7),
    memberEmail: yup.string().required().email('Debe ser un correo electrónico válido.')
});

// Configuración de vee-validate
const {handleSubmit, resetForm} = useForm({
    validationSchema: schema,
    initialValues: {
        memberName: 'David Saavedra',
        memberPhone: '987458745',
        memberEmail: 'david@gmail.com'
    }
});

// Campos de VeeValidate
const memberName = useField('memberName');
const memberPhone = useField('memberPhone');
const memberEmail = useField('memberEmail');
const isLoading = ref(false);
const mySnackbar = ref(null);

// --- Computed Properties ---
const isEditing = computed(() => !!props.member?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Miembro' : 'Adicionar Miembro');


watch(() => props.member, (newMember) => {
    if (newMember) {
        memberName.value.value = newMember.name || "";
        memberPhone.value.value = newMember.phone || "";
        memberEmail.value.value = newMember.email || "";
    }
}, {immediate: true});


const submitForm = handleSubmit(async (values) => {
    isLoading.value = true;
    const url = isEditing.value
        ? `${props.urlBase}/${props.member?.id}`
        : `${props.urlBase}/`;
    const method = isEditing.value ? 'put' : 'post';
    const typeEmit = isEditing.value ? 'member-edited' : 'member-created';

    const payload = {
        name: values.memberName,
        email: values.memberEmail,
        phone: values.memberPhone,
        house_id: props.house.id
    };

    try {
        const response = await axios({
            method: method,
            url: url,
            data: payload
        });
        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            emit(typeEmit);
            close()
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        isLoading.value = false;
    }
    resetForm({values: {memberName: '', memberPhone: '', memberEmail: ''}});
    close();
});

const close = () => {
    dialog.value = false;
    resetForm(); // Limpiar el formulario al cerrar el modal
}
</script>
<template>
    <v-dialog v-model="dialog" persistent max-width="600px">
        <v-card>
            <v-card-title>{{ formTitle }}</v-card-title>
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
                <Snackbar ref="mySnackbar"/>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>


