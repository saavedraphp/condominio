<script setup>
import {computed, onMounted, ref} from "vue";
import axios from "axios";
import MemberForm from "@/components/admin/MemberForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue";
import Snackbar from "@/components/Snackbar.vue";

const props = defineProps({
    user: Object,
    house: Object,
});

let apiBaseMemberUrl = `${window.location.origin}/admin/user/${props.user.id}/house/${props.house.id}/house-residents`;
const loading = ref(true)
const showModal = ref(false)
const members = ref([])

const selectedMember = ref(null);
const dialogDeleteVisible = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);
const mySnackbar = ref(null);
const headers = ref([
    {title: 'Nombre', key: 'name'},
    {title: 'Teléfono', key: 'phone'},
    {title: 'email', key: 'email'},
    {title: "Acciones", key: "actions", sortable: false},
])

async function getMembers() {
    loading.value = true;
    try {
        const response = await axios.get(`${apiBaseMemberUrl}`);
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

const openDeleteDialog = (item) => {
    itemToDelete.value = item;
    dialogDeleteVisible.value = true;
};

const closeDeleteDialog = () => {
    dialogDeleteVisible.value = false;
    setTimeout(() => {
        itemToDelete.value = null;
    }, 300);
};

const deleteDialogItemName = computed(() => {
    if (!itemToDelete.value) return '';

    return itemToDelete.value.name;
});

const deleteMember = async () => {
    try {
        if (!itemToDelete.value) return;
        const id = itemToDelete.value.id;

        const response = await axios.delete(`${apiBaseMemberUrl}/${id}`)

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
        closeDeleteDialog();
    }
};

onMounted(() => {
    getMembers();
})
</script>

<template>
    <div class="d-flex justify-end mb-4">
        <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="showModal = true"
        >
            Agregar Integrante
        </v-btn>
    </div>
    <v-data-table
        :headers="headers"
        :items="members"
        class="elevation-1"
        density="compact"
    >
        <template v-slot:item.name="{ item }">
            <v-tooltip location="top">
                <template v-slot:activator="{ props }">
                    <span v-bind="props" class="truncate-text">
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
    <MemberForm
        v-model="showModal"
        :member="selectedMember"
        :user="props.user"
        :house="props.house"
        :url-base="apiBaseMemberUrl"
        @member-created="getMembers"
        @member-edited="getMembers"
    />
    <DeleteConfirmationModal
        v-model:show="dialogDeleteVisible"
        :item-name="deleteDialogItemName"
        :loading="isDeleting"
        @confirm="deleteMember"
        @cancel="closeDeleteDialog"
    />
    <Snackbar ref="mySnackbar"/>
</template>
