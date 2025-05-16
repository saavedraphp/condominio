<script setup>
import axios from "axios";
import {computed, onMounted, ref} from 'vue'
import {useField, useForm} from 'vee-validate'

import Snackbar from "@/components/Snackbar.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";

const props = defineProps({
    userId: String
});

const mySnackbar = ref(null);
 const documentFile = useField('documentFile');

const TABS_KEYS = {
    'PROFILE': 'profile',
};

const activeKey = ref(TABS_KEYS.PROFILE);
const messageError = 'Ocurrió un error inesperado';

const tab = ref(1) // Para que "Settings" esté seleccionado por defecto
const showModal = ref(false)
const form = ref({name: 'Luis', phone: '960203783', email: 'saavedra@gmail.com', actions: ""})
const rules = {
    required: value => !!value || 'Campo requerido',
    minLength: value => (value.length >= 2) || 'Mínimo 2 caracteres',
    phone: value => (/^[0-9-]{7,}$/.test(value)) || 'Teléfono inválido',
    email: value => (/^[a-z.-]+@[a-z.-]+\.[a-z]+$/i.test(value)) || 'Correo inválido'
};


/* EVENTOS PROFILE*/
const {handleSubmit, handleReset} = useForm({
    validationSchema: {
        name(value) {
            if (value?.length >= 2) return true

            return 'El nombre debe tener al menos 2 caracteres.'
        },
        phone(value) {
            if (/^[0-9-]{7,}$/.test(value)) return true

            return 'El número de teléfono debe tener al menos 7 dígitos.'
        },
        email(value) {
            if (/^[a-z.-]+@[a-z.-]+\.[a-z]+$/i.test(value)) return true

            return 'Debe ser un correo electrónico válido.'
        },
        /*        select(value) {
                    if (value) return true

                    return 'Select an item.'
                },
                image(value) {
                    if (!value) return 'An image is required.'
                    if (!(value instanceof File)) return 'Invalid file format.'
                    if (!['image/jpeg', 'image/png'].includes(value.type)) {
                        return 'Only JPG or PNG images are allowed.'
                    }
                    return true
                }*/
    },
})
const user = ref(null)
const houseId = ref()
const loading = ref(true)
const error = ref(null)
const name = useField('name')
const phone = useField('phone')
const email = useField('email')
const select = useField('select')
const checkbox = useField('checkbox')
const username = ref('')
const snackbar = ref(false);
const message = ref('');

const experience = ref('')
const skills = ref('')
const terms = ref(false)

function initialValues(data) {
    name.value.value = data.name;
    phone.value.value = data.phone;
    email.value.value = data.email;
    if(data.file_path) {
        previewImage.value = data.file_path_url;
    } else {
        previewImage.value = defaultImage;
    }
}

async function getUserData() {
    loading.value = true;

    try {
        const response = await axios.get('/user/get-user-data');
        user.value = response.data;
        initialValues(response.data);
    } catch (error) {
        error.value = 'Error al obtener los datos del usuario';
        console.error(error);
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await getUserData();

});


// Imagen de perfil
const image = ref(null)
const previewImage = ref(null)
const defaultImage = '/storage/images/web_user/default-profile.jpg' // Imagen predeterminada

const fileInput = ref(null) // Referencia al input file

const items = ref([
    {'title': 'Item 1', subtitle: 'subtitle', total: 100},
    {'title': 'Item 2', subtitle: 'subtitle', total: 200},
    {'title': 'Item 3', subtitle: 'subtitle', total: 300},
    {'title': 'Item 4', subtitle: 'subtitle', total: 400},
])

// Método para manejar la selección de imagen
const onImageChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        image.value = file
        previewImage.value = URL.createObjectURL(file) // Crear una vista previa de la imagen
    }
}

// Método para limpiar la imagen
const removeImage = () => {
    image.value = null
    previewImage.value = null
}

const submit = handleSubmit(async values => {
    const formData = new FormData();
    formData.append('name', values.name);
    formData.append('phone', values.phone);
    formData.append('email', values.email);

    if(image.value instanceof File) {
        formData.append('file_path', image.value);
    }

    formData.append('_method', 'PUT');
    try {
        const response = await axios.post(`/user/profile/${props.userId}`, formData);
        if (response.data.success) {
            message.value = response.data.message;
            snackbar.value = true;
        } else {
            alert('Hubo un problema al actualizar el perfil');
        }
    } catch (error) {
        console.error('Error al guardar el perfil:', error);
        alert('Ocurrió un error al intentar guardar el perfil.');
    }
//    alert(JSON.stringify(values, null, 2))
})



function getImage(fileName, imageDefault = '') {
    let image = fileName || imageDefault;
    return `/storage/web_user/${image}`;
}
</script>
<template>
    <v-container>
        <p v-if="loading">Cargango datos</p>
        <p v-else-if="error"> {{ error }}</p>
        <v-row v-else>
            <v-col cols="12" md="4">
                <v-card class="pa-4">
                    <!-- Imagen de Perfil -->
                    <v-avatar size="100">
                        <img :src="previewImage" alt="User Profile"
                             class="profile-preview">
                    </v-avatar>

                    <h3 class="mt-3 text-center">{{ name.value.value }}</h3>
                    <p class="text-center text-grey"></p>
                    <div class="image-upload">
                        <label for="imageUpload">
                            <v-btn color="primary" class="mt-2" @click="fileInput.click()">
                                Seleccionar Imagen
                            </v-btn>
                        </label>
                        <input
                            ref="fileInput"
                            id="imageUpload"
                            type="file"
                            accept="image/png, image/jpeg"
                            @change="onImageChange"
                            hidden
                        />
                    </div>
                    <v-list lines="two">
                        <v-list-subheader inset></v-list-subheader>

                        <v-list-item
                            v-for="folder in items"
                            :key="folder.title"
                            :title="folder.title"
                        >
                            <template v-slot:append>
                                <span class="total-list text-primary">{{ folder.total }}</span>
                            </template>
                        </v-list-item>
                    </v-list>

                    <v-btn color="blue" block class="mt-3">Follow</v-btn>
                    <v-btn color="blue lighten-1" block class="mt-2">About Me</v-btn>
                </v-card>
            </v-col>
            <!-- Panel Derecho (Formulario) -->
            <v-col cols="12" md="8">
                <v-card>
                    <v-tabs v-model="activeKey">
                        <v-tab :value="TABS_KEYS.PROFILE">Perfil</v-tab>
                    </v-tabs>

                    <v-card-text>
                        <v-window v-model="activeKey">
                            <!-- Pestaña 1: Profile (Formulario) -->
                            <v-window-item :value="TABS_KEYS.PROFILE">
                                <v-form @submit.prevent="submit" class="mt-2">
                                    <v-text-field
                                        v-model="name.value.value"
                                        variant="outlined"
                                        :error-messages="name.errorMessage.value"
                                        label="Name"
                                    ></v-text-field>

                                    <v-text-field
                                        v-model="phone.value.value"
                                        variant="outlined"
                                        :counter="7"
                                        :error-messages="phone.errorMessage.value"
                                        label="Phone Number"
                                    ></v-text-field>

                                    <v-text-field
                                        v-model="email.value.value"
                                        variant="outlined"
                                        :error-messages="email.errorMessage.value"
                                        label="E-mail"
                                    ></v-text-field>
                                    <v-btn color="red" class="mt-3" type="submit">Grabar</v-btn>
                                    <v-snackbar
                                        v-model="snackbar"
                                        :timeout="3000"
                                        location="top"
                                        position="absolute"
                                        color="light-blue-accent-4"
                                        theme="dark"
                                    >
                                        <span class="text-white font-weight-bold">{{ message }}</span>
                                    </v-snackbar>
                                </v-form>
                            </v-window-item>
                        </v-window>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
<style scoped>
.image-upload {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.image-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.profile-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ddd;
}

.v-avatar {
    display: block;
    margin: 0 auto;
}

.v-list-item-content {
    font-weight: bold;
}

.total-list {
    font-weight: bold;
}

.v-btn {
    text-transform: none;
}

.truncate-text {
    display: block; /* O inline-block */
    max-width: 200px; /* ¡AJUSTA ESTE VALOR! Define el ancho máximo deseado */
    white-space: nowrap; /* Evita que el texto salte a la siguiente línea */
    overflow: hidden; /* Oculta el texto que sobrepasa el max-width */
    text-overflow: ellipsis; /* Muestra "..." al final del texto truncado */
}

/* Opcional: Estilo para asegurar que las celdas de acciones no se compriman demasiado */
.action-column {
    width: 1%; /* Intenta ocupar el mínimo espacio necesario */
    white-space: nowrap; /* Evita que los iconos se apilen verticalmente */
}
</style>

