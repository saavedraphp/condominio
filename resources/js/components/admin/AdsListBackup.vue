<template>
    <v-container fluid>
        <v-card>
            <v-card-title class="d-flex align-center pe-2">
                <v-icon icon="mdi-bullhorn-variant"></v-icon>  
                Gestión de Anuncios

                <v-spacer></v-spacer>

                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="openAddDialog"
                >
                    Agregar Anuncio
                </v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <v-data-table-server
                v-model:items-per-page="itemsPerPage"
                :headers="headers"
                :items="serverItems"
                :items-length="totalItems"
                :loading="loading"
                :search="search"
                item-value="id"
                @update:options="loadItems"
            >
                <!-- Columna de Acciones Personalizada -->
                <template v-slot:item.actions="{ item }">
                    <v-tooltip text="Editar Anuncio">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-pencil"
                                variant="text"
                                color="primary"
                                size="small"
                                class="me-2"
                                @click="openEditDialog(item)"
                            ></v-btn>
                        </template>
                    </v-tooltip>
                    <v-tooltip text="Eliminar Anuncio">
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
                </template>

                <!-- Puedes añadir slots para formatear otras columnas si es necesario -->
                <!-- Ejemplo para formatear fecha -->
                <template v-slot:item.fechaInicio="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template v-slot:item.fechaFin="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template v-slot:item.activo="{ value }">
                    <v-chip :color="value ? 'success' : 'grey'" size="small">
                        {{ value ? 'Activo' : 'Inactivo' }}
                    </v-chip>
                </template>

            </v-data-table-server>
        </v-card>

        <!-- Diálogo para Agregar/Editar Anuncio -->
        <v-dialog v-model="dialog" persistent max-width="600px">
            <v-card>
                <v-card-title>
                    <span class="text-h5">{{ formTitle }}</span>
                </v-card-title>
                <v-card-text>
                    <v-form ref="form" v-model="validForm">
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editedItem.titulo"
                                        label="Título del Anuncio*"
                                        :rules="[rules.required]"
                                        required
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="editedItem.contenido"
                                        label="Contenido del Anuncio*"
                                        rows="3"
                                        :rules="[rules.required]"
                                        required
                                    ></v-textarea>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <!-- Aquí podrías usar v-date-picker o un componente similar -->
                                    <v-text-field
                                        v-model="editedItem.fechaInicio"
                                        label="Fecha de Inicio (YYYY-MM-DD)"
                                        type="date"
                                        :rules="[rules.required]"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editedItem.fechaFin"
                                        label="Fecha de Fin (YYYY-MM-DD)"
                                        type="date"
                                        :rules="[rules.required, rules.dateGreaterThan('fechaInicio')]"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-switch
                                        v-model="editedItem.activo"
                                        :label="editedItem.activo ? 'Activo' : 'Inactivo'"
                                        color="success"
                                        inset
                                    ></v-switch>
                                </v-col>
                            </v-row>
                        </v-container>
                        <small>*Campos requeridos</small>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue-darken-1" variant="text" @click="closeDialog">Cancelar</v-btn>
                    <v-btn color="blue-darken-1" variant="text" @click="saveAnuncio" :disabled="!validForm">Guardar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Diálogo de Confirmación de Eliminación -->
        <v-dialog v-model="dialogDelete" max-width="500px">
            <v-card>
                <v-card-title class="text-h5">Confirmar Eliminación</v-card-title>
                <v-card-text>¿Estás seguro de que quieres eliminar este anuncio?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue-darken-1" variant="text" @click="closeDeleteDialog">Cancelar</v-btn>
                    <v-btn color="red-darken-1" variant="text" @click="deleteAnuncioConfirm">Eliminar</v-btn>
                    <v-spacer></v-spacer>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </v-container>
</template>

<script setup>
import { ref, reactive, watch, computed, nextTick } from 'vue';

// --- Estado Reactivo ---
const itemsPerPage = ref(10); // Valor inicial para items por página
const headers = ref([        // Definición de las columnas de la tabla
    { title: 'Título', key: 'titulo', align: 'start', sortable: true },
    { title: 'Fecha Inicio', key: 'fechaInicio', sortable: true },
    { title: 'Fecha Fin', key: 'fechaFin', sortable: true },
    { title: 'Estado', key: 'activo', sortable: true },
    { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]);
const serverItems = ref([]); // Datos de los anuncios (vendrán del servidor)
const loading = ref(true);   // Indicador de carga
const totalItems = ref(0);   // Total de items (para paginación)
const search = ref('Buscando resultados');      // Término de búsqueda (si lo implementas)
const dialog = ref(false);   // Controla visibilidad del diálogo Add/Edit
const dialogDelete = ref(false); // Controla visibilidad del diálogo Delete
const editedIndex = ref(-1);   // Índice del item editado (-1 = nuevo)
const itemToDelete = ref(null); // Item a eliminar
const form = ref(null); // Referencia al v-form
const validForm = ref(false); // Estado de validación del formulario

// Modelo para el item editado/nuevo
const defaultItem = {
    id: null,
    titulo: '',
    contenido: '',
    fechaInicio: '',
    fechaFin: '',
    activo: true,
};
const editedItem = reactive({ ...defaultItem });

// --- Reglas de Validación ---
const rules = {
    required: value => !!value || 'Este campo es requerido.',
    dateGreaterThan: (otherFieldName) => (value) => {
        const otherDate = editedItem[otherFieldName];
        if (!value || !otherDate) return true; // Si alguna fecha falta, no validar aquí
        return new Date(value) >= new Date(otherDate) || 'La fecha de fin debe ser igual o posterior a la fecha de inicio.';
    }
};

// --- Título Computado para el Diálogo ---
const formTitle = computed(() => {
    return editedIndex.value === -1 ? 'Nuevo Anuncio' : 'Editar Anuncio';
});

// --- Mock API (Simulación de llamadas al backend) ---
// DEBERÁS REEMPLAZAR ESTO CON LLAMADAS REALES A TU API
const fakeApi = {
    async fetchAnuncios({ page, itemsPerPage, sortBy }) {
        console.log('Fetching from API:', { page, itemsPerPage, sortBy });
        // Simular delay de red
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Simular datos (esto debería venir de tu backend)
        const mockData = Array.from({ length: 55 }, (_, i) => ({ // 55 items de ejemplo
            id: i + 1,
            titulo: `Anuncio Importante ${i + 1}`,
            contenido: `Este es el contenido detallado del anuncio número ${i + 1}.`,
            fechaInicio: `2024-0${(i % 9) + 1}-01`,
            fechaFin: `2024-12-31`,
            activo: i % 3 !== 0, // Algunos inactivos
        }));

        // Simular ordenación (básica)
        if (sortBy.length > 0) {
            const key = sortBy[0].key;
            const order = sortBy[0].order;
            mockData.sort((a, b) => {
                const valA = a[key];
                const valB = b[key];
                if (valA < valB) return order === 'asc' ? -1 : 1;
                if (valA > valB) return order === 'asc' ? 1 : -1;
                return 0;
            });
        }

        // Simular paginación
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const items = mockData.slice(start, end);

        return { items, total: mockData.length };
    },
    async createAnuncio(anuncioData) {
        console.log('Creating anuncio:', anuncioData);
        await new Promise(resolve => setTimeout(resolve, 500));
        // Aquí llamarías a tu API POST /anuncios
        // Debería devolver el anuncio creado con su ID
        return { ...anuncioData, id: Math.floor(Math.random() * 1000) + 100 }; // Simular ID
    },
    async updateAnuncio(id, anuncioData) {
        console.log(`Updating anuncio ${id}:`, anuncioData);
        await new Promise(resolve => setTimeout(resolve, 500));
        // Aquí llamarías a tu API PUT /anuncios/{id}
        return { ...anuncioData }; // Devolver el anuncio actualizado
    },
    async deleteAnuncio(id) {
        console.log(`Deleting anuncio ${id}`);
        await new Promise(resolve => setTimeout(resolve, 500));
        // Aquí llamarías a tu API DELETE /anuncios/{id}
        return true; // Indicar éxito
    }
};
// --- FIN Mock API ---

// --- Métodos ---
async function  loadItems({ page, itemsPerPage, sortBy }) {
    loading.value = true;
    try {
        // Llama a tu API real aquí, pasando los parámetros de paginación y ordenación
        const { items, total } = await fakeApi.fetchAnuncios({ page, itemsPerPage, sortBy });

        serverItems.value = items;
        totalItems.value = total;
    } catch (error) {
        console.error("Error al cargar anuncios:", error);
        // Manejar error (mostrar notificación, etc.)
    } finally {
        loading.value = false;
    }
}

function openAddDialog() {
    editedIndex.value = -1;
    Object.assign(editedItem, defaultItem); // Resetear a valores por defecto
    dialog.value = true;
    nextTick(() => {
        form.value?.resetValidation(); // Resetear validación al abrir
    });
}

function openEditDialog(item) {
    editedIndex.value = serverItems.value.indexOf(item); // O mejor usar item.id si es consistente
    Object.assign(editedItem, item); // Copiar datos del item al formulario
    dialog.value = true;
    nextTick(() => {
        form.value?.resetValidation(); // Resetear validación al abrir
    });
}

function openDeleteDialog(item) {
    itemToDelete.value = item; // Guardar el item a eliminar
    dialogDelete.value = true;
}

function closeDialog() {
    dialog.value = false;
    nextTick(() => { // Esperar a que el diálogo se cierre visualmente
        Object.assign(editedItem, defaultItem); // Resetear item
        editedIndex.value = -1;
        form.value?.resetValidation(); // Resetear validación
    });
}

function closeDeleteDialog() {
    dialogDelete.value = false;
    nextTick(() => {
        itemToDelete.value = null; // Limpiar item a eliminar
    });
}

async function saveAnuncio() {
    // Validar formulario primero
    const { valid } = await form.value.validate();
    if (!valid) {
        console.log("Formulario inválido");
        return;
    }

    loading.value = true; // Podrías tener un loading específico para el guardado
    try {
        if (editedIndex.value > -1) {
            // Editar (Actualizar)
            const idToUpdate = serverItems.value[editedIndex.value].id; // O usar editedItem.id si lo tienes
            await fakeApi.updateAnuncio(idToUpdate, { ...editedItem }); // Llama a tu API real
            // Opcional: Actualizar el item en serverItems localmente para respuesta visual inmediata
            // Object.assign(serverItems.value[editedIndex.value], editedItem);
        } else {
            // Agregar (Crear)
            await fakeApi.createAnuncio({ ...editedItem }); // Llama a tu API real
            // No necesitas añadirlo localmente si vas a recargar
        }
        closeDialog();
        // Recargar la tabla para mostrar los cambios (podría requerir ajustar la página actual)
        // Para simplificar, recargamos con las opciones actuales (puede que te lleve a la pág 1 si no manejas bien el estado)
        loadItems({ page: 1, itemsPerPage: itemsPerPage.value, sortBy: [] }); // O usa las opciones actuales si quieres mantener la página
    } catch (error) {
        console.error("Error al guardar anuncio:", error);
        // Mostrar error al usuario
    } finally {
        loading.value = false;
    }
}

async function deleteAnuncioConfirm() {
    if (!itemToDelete.value) return;

    loading.value = true; // O loading específico para eliminar
    try {
        await fakeApi.deleteAnuncio(itemToDelete.value.id); // Llama a tu API real
        closeDeleteDialog();
        // Recargar la tabla
        loadItems({ page: 1, itemsPerPage: itemsPerPage.value, sortBy: [] }); // O usa las opciones actuales
    } catch(error) {
        console.error("Error al eliminar anuncio:", error);
        // Mostrar error al usuario
    } finally {
        loading.value = false;
    }
}

// Función auxiliar para formatear fechas (ejemplo)
function formatDate(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        // Asegurarse que la fecha sea válida y ajustar por zona horaria si es necesario
        if (isNaN(date.getTime())) return dateString; // Devolver original si no es válida
        // Sumar la diferencia de zona horaria para evitar que cambie el día
        const adjustedDate = new Date(date.getTime() + date.getTimezoneOffset() * 60000);
        return adjustedDate.toLocaleDateString('es-ES'); // Formato local español
    } catch (e) {
        return dateString; // Devolver original en caso de error
    }
}

// --- Observadores ---
// (No se necesita watch para options aquí porque v-data-table-server dispara @update:options)

</script>

<style scoped>
/* Puedes añadir estilos específicos aquí si los necesitas */
.v-card-title {
    background-color: #f5f5f5; /* Un fondo ligero para el título como en tu imagen */
    border-bottom: 1px solid #e0e0e0;
}
</style>
