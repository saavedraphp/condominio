<script setup>
import {ref, onMounted, watch} from 'vue';
import axios from 'axios'; // Asegúrate de tener axios instalado
import GeneralSettings from '@/components/admin/GeneralSettings.vue';
import Snackbar from "@/components/Snackbar.vue";

const tab = ref('general');
const loading = ref(false);
const settings = ref({});

const mySnackbar = ref(null);

// Carga las configuraciones para un grupo específico
const fetchSettings = async (group) => {
    if (settings.value[group]) return; // No volver a cargar si ya existen

    loading.value = true;
    try {
        const response = await axios.get(`/admin/settings?group=${group}`);
        settings.value[group] = response.data;
    } catch (error) {
        console.error(`Error al cargar la configuración para el grupo ${group}:`, error);
        mySnackbar.value.show('Error al cargar la configuración.', 'error');
    } finally {
        loading.value = false;
    }
};

// Guarda las configuraciones del grupo actual
const saveSettings = async () => {

    const price = parseFloat(settings.value.general.price_per_kw);
    if(isNaN(price) || price <= 0) {
        mySnackbar.value.show('El precio por KW debe ser un número positivo.', 'error');
        return;
    }
    loading.value = true;
    try {
        const currentGroup = tab.value;
        await axios.post('/admin/settings', {
            group: currentGroup,
            settings: settings.value[currentGroup],
        });
        mySnackbar.value.show('Configuración guardada.', 'success');
    } catch (error) {
        console.error('Error al guardar la configuración:', error);
    } finally {
        loading.value = false;
    }
};

// Carga las configuraciones del primer tab al montar el componente
onMounted(() => {
    fetchSettings(tab.value);
});

// Carga las configuraciones cuando el usuario cambia de tab
watch(tab, (newTab) => {
    fetchSettings(newTab);
});
</script>
<template>
    <v-container>
        <v-card>
            <v-tabs v-model="tab">
                <v-tab value="general">General</v-tab>
                <v-tab value="writing" v-if="false">Escritura</v-tab>
                <v-tab value="reading" v-if="false">Lectura</v-tab>
            </v-tabs>

            <v-card-text>
                <v-window v-model="tab">
                    <v-window-item value="general">
                        <!-- El componente para las configuraciones generales irá aquí -->
                        <GeneralSettings v-if="settings.general" v-model="settings.general"/>
                    </v-window-item>

                    <v-window-item value="writing">
                        <!-- Aquí iría un componente WritingSettings, etc. -->
                        Contenido de configuraciones de Escritura
                    </v-window-item>

                    <v-window-item value="reading">
                        Contenido de configuraciones de Lectura
                    </v-window-item>
                </v-window>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" variant="flat" :loading="loading" @click="saveSettings">
                    Guardar Cambios
                </v-btn>
            </v-card-actions>
        </v-card>
        <Snackbar ref="mySnackbar"/>
    </v-container>
</template>
