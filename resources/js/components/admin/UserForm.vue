<script setup>
import {computed, ref, watch} from "vue";
import {useField, useForm} from "vee-validate";
import * as yup from 'yup';
import Snackbar from "@/components/Snackbar.vue";
import UploadFormModal from "@/components/UploadFormModal.vue";
import PreviewImageDialog from "@/components/user/PreviewImageDialog.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import axios from "axios";

const emit = defineEmits(['added', 'edit', 'close-modal', 'update:modelValue', 'refresh-data']);

const props = defineProps({
    modelValue: Boolean,
    user: Object,
});

const dialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
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
const isUploading = ref(false);
const isLoading = ref(false);
const mySnackbar = ref(null);
const localData = ref({});
const routes = ref(null);
const showDetailDialog = ref(false);
const urlPreviewFile = ref('');
const loadingFiles = ref(false);

const isEditing = computed(() => !!props.user?.id);
const formTitle = computed(() => isEditing.value ? 'Editar Usuario' : 'Adicionar Usuario');

watch(() => props.user, (newValue) => {
    if (newValue && newValue.id) {
        name.value.value = newValue.name || "";
        email.value.value = newValue.email || "";
        phone.value.value = newValue.phone || "";
        status.value.value = newValue.status === 'active';
        has_payment_arrangement.value.value = newValue.has_payment_arrangement || false;
        is_associated.value.value = newValue.is_associated || false;
        localData.value = JSON.parse(JSON.stringify(newValue));

        if (!localData.value.images) {
            localData.value.images = [];
        }
    } else {
        resetForm();
    }
}, {immediate: true});

if (props.user && props.user.id) {
    routes.value = {
        'store_document': `${window.location.origin}/admin/users/${props.user.id}/files`,
        'update_document': `${window.location.origin}/admin/users/${props.user.id}/files/PALCEHOLDER`,
        'preview_image': `${window.location.origin}/admin/users/files/PLACEHOLDER/preview-image`,
        'destroy_details': `${window.location.origin}/admin/users/${props.user.id}/files/PLACEHOLDER`
    }
}
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

const TABS_KEYS = {
    'DATA': 'data',
    'FILES': 'files'
};

const selectedElement = ref(null);
const editingElementIndex = ref(-1);
const showFormModal = ref(false);
const hasChanges = ref(false);

const deleteDialog = ref(false);
const elementToDelete = ref(null);
const elementIndexToDelete = ref(-1);

async function getFiles() {
    loadingFiles.value = true;

    try {
        const response = await axios.get(`/admin/users/${props.user.id}/files`);
        localData.value.images = response.data.data;
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema obtener la información. Intenta de nuevo, por favor.', 'error');
    } finally {
        loadingFiles.value = false;
    }
}

function openAddFileModal() {
    if (!props.user?.id) {
        mySnackbar.value.show('Primero tiene que crear el usuario antes de agregar un archivo.', 'error');
        return;
    }

    selectedElement.value = null;
    editingElementIndex.value = -1;
    showFormModal.value = true;
}

const handleFileSaved = (file) => {
    if (editingElementIndex.value >= 0) {
        // Update existing file
        // Assuming you have a method to update the file in your list
        // updateFile(editingElementIndex.value, file);
    } else {
        //localData.value.images.push(file);
        getFiles();
        hasChanges.value = true;
    }
    showFormModal.value = false;
};
const previewFile = (item) => {
    selectedElement.value = item;
    showDetailDialog.value = true;
    const templateUrl = `${routes.value.preview_image}`;
    urlPreviewFile.value = templateUrl.replace('PLACEHOLDER', item.id);
};

function confirmDeleteDetail(item, index) {
    elementToDelete.value = item;
    elementIndexToDelete.value = index;
    deleteDialog.value = true;
}



const deleteElementName = computed(() => {
    if (!elementToDelete.value) return '';
    return `Archivo : ${elementToDelete.value.title} (ID: ${elementToDelete.value.id})`;
});

async function deleteFileConfirmed() {
    if (!elementToDelete.value) return;
    isLoading.value = true;
    let urlToDelete = `/admin/users/${props.user.id}/files/${elementToDelete.value.id}`;

    try {
        const response = await axios.delete(urlToDelete);
        if (response.data.success) {
            localData.value.images.splice(elementIndexToDelete.value, 1);
            mySnackbar.value.show(response.data.message, 'success');
            hasChanges.value = true;
        } else {
            mySnackbar.value.show(response.data.message || 'Failed to delete quotation.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Error deleting quotation.', 'error');
        console.error(error);
    } finally {
        closeDeleteModal();
    }
}

const closeDeleteModal = () => {
    deleteDialog.value = false;
    if (hasChanges.value) {
        emit('update:modelValue', false);
        emit('refresh-data');
    }
    setTimeout(() => {
        elementToDelete.value = null;
        isLoading.value = false;
    }, 300);
};
const close = () => {
    dialog.value = false;
    if (hasChanges.value) {
        emit('refresh-data');
    }
}

const activeKey = ref(TABS_KEYS.DATA);
</script>
<template>
    <v-dialog :model-value="dialog" @update:model-value="close" persistent max-width="800px" scrollable>
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
                                <v-row dense>
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="name.value.value"
                                            :error-messages="name.errorMessage.value"
                                            label="Name*"
                                            variant="outlined"
                                            density="compact"
                                            required
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="email.value.value"
                                            :error-messages="email.errorMessage.value"
                                            label="Email*"
                                            variant="outlined"
                                            density="compact"
                                            required
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="phone.value.value"
                                            :error-messages="phone.errorMessage.value"
                                            label="Teléfono*"
                                            variant="outlined"
                                            density="compact"
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
                        <div class="d-flex justify-end pa-2">
                            <v-btn
                                color="secondary"
                                @click="openAddFileModal"
                                prepend-icon="mdi-plus"
                                :loading="isUploading"
                                :disabled="isUploading">
                                Agregar Archivo
                            </v-btn>
                        </div>
                        <v-row>
                            <v-col>
                                <v-list v-if="localData?.images && localData.images.length > 0" lines="two"
                                        density="compact">
                                    <v-list-item
                                        v-for="(item, index) in localData.images"
                                        :key="item.id || `new-${index}`"
                                        class="mb-2 elevation-1"
                                    >
                                        <v-list-item-title class="font-weight-medium">{{
                                                item.title || item.file_name || 'Archivo sin título'
                                            }}
                                        </v-list-item-title>
                                        <v-list-item-subtitle>
                                            {{ item.date_document || 'Fecha no especificada' }} {{item.is_visible ? '(Visible)' : '(No visible)'}}
                                        </v-list-item-subtitle>

                                        <template v-slot:append>
                                            <v-tooltip text="Preview File">
                                                <template v-slot:activator="{ props: tooltipProps }">
                                                    <v-btn
                                                        icon="mdi-eye"
                                                        variant="text"
                                                        color="primary"
                                                        size="small"
                                                        v-bind="props"
                                                        @click="previewFile(item)"
                                                        :disabled="isLoading"></v-btn>
                                                </template>
                                            </v-tooltip>
                                            <v-tooltip text="Delete imagen">
                                                <template v-slot:activator="{ props: tooltipProps }">
                                                    <v-btn v-bind="tooltipProps" icon="mdi-delete" variant="text"
                                                           color="error"
                                                           size="small"
                                                           @click="confirmDeleteDetail(item, index)"
                                                           :disabled="isLoading"></v-btn>
                                                </template>
                                            </v-tooltip>
                                        </template>
                                    </v-list-item>
                                </v-list>
                                <v-alert v-else type="info" density="compact" class="ma-2" outlined>
                                    Aún no se han adjuntado imagenes para este gasto.
                                </v-alert>
                            </v-col>
                        </v-row>
                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>
        <UploadFormModal
            v-model="showFormModal"
            :element="selectedElement"
            :routes="routes"
            @file-saved="handleFileSaved"

        />
        <PreviewImageDialog v-if="showDetailDialog"
                            v-model="showDetailDialog"
                            :api-base-url="urlPreviewFile"
                            :id="selectedElement?.id ?? null"
                            @close="showDetailDialog = false"
        />
        <DeleteConfirmationModal
            v-model:show="deleteDialog"
            :item-name="deleteElementName"
            :loading="isLoading"
            @confirm="deleteFileConfirmed"
            @cancel="closeDeleteModal"
        />
        <Snackbar ref="mySnackbar"/>
    </v-dialog>
</template>
