<script setup>
import axios from "axios";
import {computed, onMounted, ref} from 'vue'
import {useField, useForm} from 'vee-validate'

import House from "@/components/user/House.vue";
import AddMemberModal from "@/components/user/AddMemberModal.vue";
import Snackbar from "@/components/Snackbar.vue";
import VehicleForm from "@/components/user/VehicleForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";

const props = defineProps({
    userId: String
});


const mySnackbar = ref(null);
const dialogDeleteVisible = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false); // Para el estado de carga
const houses = ref([]);

const TABS_KEYS = {
    'PROFILE': 'profile',
    'HOUSES': 'houses',
    'VEHICLES': 'vehicles',
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

const headerMembers = ref([
    {text: 'Nombre', value: 'name'},
    {text: 'Teléfono', value: 'phone'},
    {text: 'email', value: 'email'},
    {text: "Acciones", value: "actions", sortable: false},
])

const members = ref([])
const showDialogDelete = ref(false);
const memberToDelete = ref(null);

const addMember = async (member) => {
    try {
        const response = await axios.post('/user/house-residents/', {
            house_id: houseId.value,
            name: member.name,
            email: member.email,
            phone: member.phone
        });
        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            members.value.push(response.data.data);
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
        console.error('Ocurrió un error al intentar agregar un mienbro:', error);
    }

    showModal.value = false;
};

const editMember = async (item) => {

    try {
        const response = await axios.put(`/user/house-residents/${item.id}`, {
            house_id: houseId.value,
            name: item.name,
            email: item.email,
            phone: item.phone
        });

        if (response.data.success) {
            const updateMemberArray = members.value.map(member => {
                if (member.id === item.id) {
                    return response.data.data;
                }
                return member;
            });

            members.value = updateMemberArray;
            message.value = response.data.message;
            snackbar.value = true;

        } else {
            alert('Error :', response.data.message);
        }
    } catch (error) {
        console.error('Ocurrió un error al intentar editar un mienbro:', error);
    }

    showModal.value = false;
};

const openDeleteDialog = (item) => {
    memberToDelete.value = item;
    showDialogDelete.value = true;
};

const closeDeleteDialog = () => {
    showDialogDelete.value = false;
    setTimeout(() => {
        memberToDelete.value = null;
    }, 300); // Pequeño delay para que no desaparezca info del diálogo bruscamente
};

const deleteMember = async () => {
    try {
        if (!memberToDelete.value) return;
        const id = memberToDelete.value.id;

        const response = await axios.delete(`/user/house-residents/${id}`)

        if (response.data && response.data.success) {
            members.value = members.value.filter(member => member.id !== id);
            mySnackbar.value.show(response.data.message, 'success');

        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        console.error('Error al eliminar el residente:', error);
        const errorMessage = error.response?.data?.message || 'Ocurrió un error al eliminar.';
        mySnackbar.value.show(`Error: ${errorMessage}`, 'error');
    } finally {
        closeDeleteDialogModal();
    }
};

/* METHODS VEHICLES*/

const vehicles = ref([]);
const selectedVehicle = ref(null)
const showModalFormVehicle = ref(false)

const headerVehicles = ref([
    {text: 'Placa', value: 'plate_number'},
    {text: 'Marcar', value: 'brand'},
    {text: 'Modelo', value: 'model'},
    {text: "Acciones", value: "actions", sortable: false},
])

async function getVehiclesData() {
    loading.value = true;

    try {
        const response = await axios.get(`/user/get-vehicles-data/${props.userId}`);
        vehicles.value = response.data;

    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
        console.error('Ocurrió un error inesperado:', error);
    } finally {
        loading.value = false;
    }
}

const addVehicle = async (item) => {
    try {
        const response = await axios.post('/user/vehicles/', {
            user_id: props.userId,
            plate_number: item.plate_number,
            brand: item.brand,
            model: item.model
        });
        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
            vehicles.value.push(response.data.data);
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información. Intenta de nuevo, por favor.', 'error');
        console.error('Ocurrió un error inesperado:', error);
    }

    showModalFormVehicle.value = false;
};

const showModalVehicle = (vehicle) => {
    selectedVehicle.value = {...vehicle};
    showModalFormVehicle.value = true;
};

const editVehicle = async (item) => {
    try {
        const response = await axios.put(`/user/vehicles/${item.id}`, {
            user_id: props.userId,
            plate_number: item.plate_number,
            brand: item.brand,
            model: item.model
        });

        if (response.data.success) {
            vehicles.value = vehicles.value.map(vehicle => {
                if (vehicle.id === item.id) {
                    return response.data.data;
                }
                return vehicle;
            });
            mySnackbar.value.show(response.data.message, 'success');
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response.data.errors, 'error');
    }

    showModal.value = false;
};

const deleteVehicle = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`/user/vehicles/${id}`)

        if (response.data && response.data.success) {
            vehicles.value = vehicles.value.filter(vehicle => vehicle.id !== id);
            mySnackbar.value.show(response.data.message, 'success');

        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }

    } catch (error) {
        const errorMessage = error.response?.data?.errors || messageError;
        mySnackbar.value.show(`Error: ${errorMessage}`, 'error');
        console.error(errorMessage);
    } finally {
        closeDeleteDialogModal();
    }
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
const selectedMember = ref(null);
const experience = ref('')
const skills = ref('')
const terms = ref(false)

function initialValues(data) {
    name.value.value = data.name;
    phone.value.value = data.phone;
    email.value.value = data.email;
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

async function getResidentsData() {
    loading.value = true;

    try {
        const response = await axios.get(`/user/get-house-residents-data/${houseId.value}`);
        members.value = response.data;
    } catch (error) {
        error.value = 'Error al obtener los recidentes';
        console.error(error);
    } finally {
        loading.value = false;
    }
}

const showModalMember = (member) => {
    selectedMember.value = {...member};
    showModal.value = true;
};


onMounted(async () => {
    await getUserData();
    await getHousesData();
    if (houseId.value) {
        await getResidentsData();
    }
    await getVehiclesData();
});

const deleteDialogMessage = computed(() => {
    // Ejemplo: Personalizar mensaje según el tipo si lo sabes
    if (itemToDelete.value?.plate_number) { // Asumiendo que los vehículos tienen plate_number
        return '¿Estás seguro de que deseas eliminar el siguiente vehículo?';
    }
    if (itemToDelete.value?.name) { // Asumiendo que los miembros tienen name
        return '¿Estás seguro de que deseas eliminar al siguiente miembro?';
    }
    return '¿Estás seguro de que deseas eliminar este elemento?';
});

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';
    // Devuelve una representación del ítem (nombre, placa, id, etc.)
    return itemToDelete.value.name || itemToDelete.value.plate_number || `ID: ${itemToDelete.value.id}`;
});

// --- Métodos del Componente Padre ---
const openDeleteDialogModal = (item) => {
    itemToDelete.value = item; // Guarda la referencia al ítem
    dialogDeleteVisible.value = true; // Abre el modal
};

const closeDeleteDialogModal = () => {
    dialogDeleteVisible.value = false;
    // Es buena idea limpiar itemToDelete después de que el diálogo se cierre (opcional, con timeout)
    setTimeout(() => {
        itemToDelete.value = null;
        isDeleting.value = false; // Asegurarse de resetear el loading si se cancela
    }, 300); // Espera a la animación de cierre
};

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
    //values.image = image.value // Agregar la imagen a los valores del formulario
    try {
        const response = await axios.put(`/user/profile/${props.userId}`, values);
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

function closeModalMember() {
    selectedMember.value = null;
    showModal.value = false;
}

function closeModalVehicle() {
    selectedVehicle.value = null;
    showModalFormVehicle.value = false;
}

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
                        <img :src="`/storage/images/web_user/default-profile.jpg`" alt="User Profile"
                             class="profile-preview">
                    </v-avatar>

                    <h3 class="mt-3 text-center">{{ name.value.value }}</h3>
                    <p class="text-center text-grey">Software Engineer</p>
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
                        <v-list-subheader inset>Folders</v-list-subheader>

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
                        <v-tab :value="TABS_KEYS.HOUSES">Casa e Integrantes</v-tab>
                        <v-tab :value="TABS_KEYS.VEHICLES">Vehículos</v-tab>
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
                            <!-- Pestaña 2: House -->
                            <v-window-item :value="TABS_KEYS.HOUSES">
                                <div v-if="houseId">
                                    <House :houseId="houseId"/>
                                    <v-divider class="my-6" style="height: 2px; background-color: black;"></v-divider>
                                    <v-btn color="primary" class="mt-3" @click="showModal = true"> + Agregar Integrantes
                                    </v-btn>
                                    <v-dialog max-width="500px" v-model="showModal">
                                        <AddMemberModal
                                            :member="selectedMember"
                                            @member-added="addMember"
                                            @member-edit="editMember"
                                            @close-modal="closeModalMember"
                                        />
                                    </v-dialog>
                                    <v-data-table v-if="members.length"
                                                  :headers="headerMembers"
                                                  :items="members"
                                                  class="elevation-1"
                                                  density="compact"
                                    >
                                        <template v-slot:item.name="{ item }">
                                            <v-tooltip location="top">
                                                <template v-slot:activator="{ props }">
                                                    <span v-bind="props" class="truncate-text">
                                                        {{ item.name }}
                                                        {{ item.name }}
                                                    </span>
                                                </template>
                                                <span>{{ item.name }}</span>
                                            </v-tooltip>
                                        </template>
                                        <template v-slot:item.actions="{ item }">
                                            <div class="d-flex align-center">
                                                <v-tooltip text="Editar">
                                                    <template v-slot:activator="{ props }">
                                                        <v-btn
                                                            v-bind="props"
                                                            icon="mdi-pencil"
                                                            variant="text"
                                                            color="primary"
                                                            size="small"
                                                            class="me-2"
                                                            @click="showModalMember(item)"
                                                        ></v-btn>
                                                    </template>
                                                </v-tooltip>
                                                <v-tooltip text="Eliminar">
                                                    <template v-slot:activator="{ props }">
                                                        <v-btn
                                                            v-bind="props"
                                                            icon="mdi-delete"
                                                            variant="text"
                                                            color="error"
                                                            size="small"
                                                            @click="openDeleteDialog(item)"
                                                        ></v-btn>
                                                    </template>
                                                </v-tooltip>
                                            </div>
                                        </template>
                                    </v-data-table>
                                </div>
                                <v-alert
                                    v-else
                                    type="info"
                                    variant="tonal"
                                    border="start"
                                    prominent
                                    icon="mdi-information-outline"
                                    class="ma-4"
                                >
      <span class="text-body-1 font-weight-medium">
        Aún no se le ha asignado una casa para su administración. Por favor, contacte con el administrador.
      </span>
                                </v-alert>


                            </v-window-item>

                            <!-- Pestaña 3:  -->
                            <v-window-item :value="TABS_KEYS.VEHICLES">
                                <v-btn color="primary" class="mt-3" @click="showModalFormVehicle = true"> + Agregar
                                    Vehículos
                                </v-btn>
                                <v-dialog max-width="500px" v-model="showModalFormVehicle">
                                    <VehicleForm
                                        :vehicle="selectedVehicle"
                                        @vehicle-added="addVehicle"
                                        @vehicle-edit="editVehicle"
                                        @close-modal="closeModalVehicle"
                                    />
                                </v-dialog>
                                <v-data-table v-show="vehicles.length"
                                              :headers="headerVehicles"
                                              :items="vehicles"
                                              class="elevation-1"
                                              dense
                                >
                                    <template v-slot:item.actions="{ item }">
                                        <v-tooltip text="Editar">
                                            <template v-slot:activator="{ props }">
                                                <v-btn
                                                    v-bind="props"
                                                    icon="mdi-pencil"
                                                    variant="text"
                                                    color="primary"
                                                    size="small"
                                                    class="me-2"
                                                    @click="showModalVehicle(item)"
                                                ></v-btn>
                                            </template>
                                        </v-tooltip>
                                        <v-tooltip text="Eliminar">
                                            <template v-slot:activator="{ props }">
                                                <v-btn
                                                    v-bind="props"
                                                    icon="mdi-delete"
                                                    variant="text"
                                                    color="error"
                                                    size="small"
                                                    @click="openDeleteDialogModal(item)"
                                                ></v-btn>
                                            </template>
                                        </v-tooltip>
                                    </template>
                                </v-data-table>
                                <DeleteConfirmationModal
                                    v-model:show="dialogDeleteVisible"
                                    :message="deleteDialogMessage"
                                    :item-name="deleteDialogItemName"
                                    :loading="isDeleting"
                                    @confirm="deleteVehicle"
                                    @cancel="closeDeleteDialogModal"
                                />
                            </v-window-item>
                        </v-window>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="showDialogDelete" max-width="500px" persistent>
            <v-card>
                <v-card-title class="text-h5 grey lighten-2">
                    Confirmar Eliminación
                </v-card-title>

                <v-card-text>
                    <!-- Muestra el nombre si lo guardaste -->
                    <span v-if="memberToDelete">
              ¿Estás seguro de que deseas eliminar a <strong>{{
                            memberToDelete.name
                        }}</strong>?
          </span>
                    <span v-else>
              ¿Estás seguro de que deseas eliminar este residente?
          </span>
                    <br>
                    Esta acción no se puede deshacer.
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="blue darken-1"
                        text
                        @click="closeDeleteDialog"
                    >
                        Cancelar
                    </v-btn>
                    <v-btn
                        color="red darken-1"
                        text
                        @click="deleteMember"
                    >
                        Eliminar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
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

