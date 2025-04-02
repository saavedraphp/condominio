<script setup>
import { ref, onMounted } from "vue";

// Datos de la tabla
const bathrooms = ref([]);
const headers = ref([
    { text: "ID", value: "id" },
    { text: "Nombre", value: "name" },
    { text: "Ubicación", value: "location" },
    { text: "Acciones", value: "data-table-expand"},
]);

// Simular carga de datos (o usa Axios para API)
onMounted(() => {
    bathrooms.value = [
        { id: 1, name: "Sergio Astete", location: "Planta Baja", actions: "" },
        { id: 2, name: "Giovanna Cabrera Linares de Astete", location: "Primer Piso", actions: "" },
    ];
    console.log(bathrooms);
});

// Función para agregar un baño
const addBathroom = () => {
    bathrooms.value.push({
        id: bathrooms.value.length + 1,
        name: "Nuevo Baño",
        location: "Ubicación Desconocida",
    });
};

// Función para editar un baño
const editBathroom = (bathroom) => {
    alert(`Editar: ${bathroom.name}`);
};

// Función para eliminar un baño
const deleteBathroom = (id) => {
    bathrooms.value = bathrooms.value.filter((b) => b.id !== id);
};
</script>
<template>
    <v-app>
    <v-container>
        <v-card>
            <v-card-title>
                <v-btn color="primary" @click="addBathroom">Agregar</v-btn>
            </v-card-title>

            <v-data-table
                :header="headers"
                :items="bathrooms"
                disable-sort
                class="elevation-1"
                dense
            >
                <template v-slot:item.actions="{ item }">
                    <v-btn icon color="blue" @click="editBathroom(item)">
                        <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn icon color="red" @click="deleteBathroom(item.id)">
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
                </template>
            </v-data-table>
        </v-card>
    </v-container>
    </v-app>
</template>


