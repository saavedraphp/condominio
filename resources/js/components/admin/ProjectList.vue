<script setup>
import {ref, computed, onMounted} from 'vue';
import axios from "axios";
import Snackbar from "@/components/Snackbar.vue";
import {formatDate} from "@/utils/functions.js";
import ProjectForm from "@/components/admin/ProjectForm.vue";
import DeleteConfirmationModal from "@/components/DeleteConfirmationModal.vue"; // Reutilizas el tuyo

// Props (si necesitas, como isAdmin, etc. similar a tu otro componente de lista)
const props = defineProps({
    isAdmin: Boolean,
    urlBase: {
        type: String,
        require: true
    }
});

const API_BASE_URL = `${window.location.origin}/api/projects`; // Ajusta si es necesario

const mySnackbar = ref(null);
const projects = ref([]);
const loadingProjects = ref(true);
const search = ref(''); // Para la búsqueda en v-data-table

const showProjectFormModal = ref(false);
const showDeleteProjectModal = ref(false);
const selectedProject = ref(null);
const projectToDelete = ref(null);
const isDeletingProject = ref(false);

const headers = ref([
    {title: 'Nombre del Proyecto', key: 'name', align: 'start', sortable: true},
    {title: 'F. Inicio', key: 'start_date', sortable: true},
    {title: 'F. Fin', key: 'end_date', sortable: true},
    {title: 'Presupuestos', key: 'quotations_summary', sortable: false},
    {title: 'Otros Gastos', key: 'additional_expenses', align: 'end', sortable: true},
    {title: 'Actions', key: 'actions', sortable: false, align: 'center'},
]);

onMounted(() => {
    getProjects();
});

async function getProjects() {
    loadingProjects.value = true;
    try {
        const response = await axios.get(`${props.urlBase}`);
        projects.value = response.data.data.map(project => ({
            ...project,
            start_date_format: formatDate(project.start_date),
            end_date_format: formatDate(project.end_date),
        }));
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'Failed to load projects.', 'error');
    } finally {
        loadingProjects.value = false;
    }
}

function getQuotationsSummary(project) {
    if (!project.quotations || project.quotations.length === 0) {
        return "No quotations";
    }
    const count = project.quotations.length;
    const chosenOne = project.quotations.find(q => q.id === project.chosen_quotation_id);
    let summary = `${count} Quotation(s)`;
    if (chosenOne) {
        summary += ` / Chosen: S/${parseFloat(chosenOne.amount).toFixed(2)}`;
    }
    return summary;
}

function formatCurrency(value) {
    return `S/${parseFloat(value).toFixed(2)}`;
}

const openAddProjectModal = () => {
    selectedProject.value = null;
    showProjectFormModal.value = true;
};

const openEditProjectModal = (project) => {
    selectedProject.value = JSON.parse(JSON.stringify(project));
    showProjectFormModal.value = true;
};

const handleProjectSaved = (message, isUpdate = false) => {
    mySnackbar.value.show(message, 'success');
    showProjectFormModal.value = false;
    getProjects();
};

const openDeleteProjectModal = (project) => {
    projectToDelete.value = project;
    showDeleteProjectModal.value = true;
};

const deleteProjectModalItemName = computed(() => {
    if (!projectToDelete.value) return '';
    return `Proyecto: ${projectToDelete.value.name} (ID: ${projectToDelete.value.id})`;
});

const closeDeleteProjectModal = () => {
    showDeleteProjectModal.value = false;
    setTimeout(() => {
        projectToDelete.value = null;
        isDeletingProject.value = false;
    }, 300);
};

const deleteProjectConfirmed = async () => {
    if (!projectToDelete.value) return;
    isDeletingProject.value = true;
    try {
        const response = await axios.delete(`${props.urlBase}/${projectToDelete.value.id}`);
        if (response.data && response.data.success) {
            mySnackbar.value.show(response.data.message || 'Project deleted successfully.', 'success');
            getProjects(); // Recarga la lista
        } else {
            mySnackbar.value.show(response.data.message || 'Failed to delete project.', 'error');
        }
    } catch (error) {
        mySnackbar.value.show(error.response?.data?.message || 'An error occurred while deleting.', 'error');
    } finally {
        isDeletingProject.value = false;
        closeDeleteProjectModal();
    }
};

</script>

<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-briefcase-check-outline"></v-icon>
                  Gestión de Proyectos
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="openAddProjectModal"
                >
                    Agregar Proyecto
                </v-btn>
            </v-card-title>

            <v-text-field v-if="false"
                v-model="search"
                density="compact"
                label="Search Projects..."
                prepend-inner-icon="mdi-magnify"
                variant="solo-filled"
                flat
                hide-details
                single-line
                class="pa-2"
            ></v-text-field>
            <v-divider></v-divider>

            <v-data-table
                :headers="headers"
                :items="projects"
                :search="search"
                :loading="loadingProjects"
                class="elevation-1"
                item-value="id"
            >
                <template v-slot:item.start_date="{ item }">
                    <span>{{ item.start_date_format }}</span>
                </template>
                <template v-slot:item.end_date="{ item }">
                    <span>{{ item.end_date_format }}</span>
                </template>
                <template v-slot:item.quotations_summary="{ item }">
                    <span>{{ getQuotationsSummary(item) }}</span>
                </template>
                <template v-slot:item.additional_expenses="{ item }">
                    <span>{{ formatCurrency(item.additional_expenses) }}</span>
                </template>
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Edit Project">
                        <template v-slot:activator="{ props: tooltipProps }">
                            <v-btn
                                v-bind="tooltipProps"
                                icon="mdi-pencil"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="openEditProjectModal(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Delete Project">
                        <template v-slot:activator="{ props: tooltipProps }">
                            <v-btn
                                v-bind="tooltipProps"
                                icon="mdi-delete"
                                variant="text"
                                color="error"
                                size="small"
                                @click="openDeleteProjectModal(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                </template>
                <template v-slot:loading>
                    <v-skeleton-loader type="table-row@5"></v-skeleton-loader>
                </template>
                <template v-slot:no-data v-if="false">
                    <v-alert type="info" class="ma-3">No hay datos.</v-alert>
                </template>
            </v-data-table>
        </v-card>

        <ProjectForm
            v-model="showProjectFormModal"
            :project-data-prop="selectedProject"
            :url-base="props.urlBase"
            @project-saved="handleProjectSaved"
        />

        <DeleteConfirmationModal
            v-model:show="showDeleteProjectModal"
            :item-name="deleteProjectModalItemName"
            :loading="isDeletingProject"
            @confirm="deleteProjectConfirmed"
            @cancel="closeDeleteProjectModal"
        />

        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
