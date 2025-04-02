<script setup>
import axios from "axios";
import { onMounted, ref } from "vue";
import { useField, useForm } from 'vee-validate'
import Snackbar from "./../Snackbar.vue"

const loading = ref(false)
const error = ref(null)
const mySnackbar = ref(null);

// Definir props
const props = defineProps({
    houseId: Number // Tipo de dato esperado
});
onMounted(() => {
    getHouses();
});

async function getHouses() {
    loading.value = true;
    error.value = null;

    try {
        const { data } = await axios.get(`/user/get-house/${props.houseId}`);

        initialValues(data);
    } catch (err) {
        error.value = "Error al obtener los datos de la casa";
        console.error(err);
    } finally {
        loading.value = false;
    }
}

function initialValues(data) {
    propertyUnit.value.value = data.property_unit;
    address.value.value = data.address;
    constructionArea.value.value = data.construction_area;
    participationPercentage.value.value = data.participation_percentage;

}

const {handleSubmit, handleReset} = useForm({
    validationSchema: {
        propertyUnit(value) {
            if (value?.length >= 5) return true

            return 'El código de propiedad tiene que ser de 5 diguitos'
        },
        address(value) {
            if (value?.length >= 5) return true

            return 'La dirección tiene que tener como mininimo 5 caracteres'
        },
        constructionArea(value) {
            if (/^\d+$/.test(value)) return true;

            return "Tiene que ingresar un valor numérico.";
        },
        participationPercentage(value) {
            if (/^\d+(\.\d+)?$/.test(value)) return true;

            return "Debe ser un número válido.";
        },
    }
})

const propertyUnit = useField('propertyUnit')
const address = useField('address')
const constructionArea = useField('constructionArea')
const participationPercentage = useField('participationPercentage')

const submit = handleSubmit(async values => {

    try {
        const response = await axios.post(`/user/house/${props.houseId}`, {
            construction_area: values.constructionArea,
            property_unit: values.propertyUnit,
            address: values.address,
            participation_percentage: values.participationPercentage,
        });

        if (response.data.success) {
            mySnackbar.value.show(response.data.message, 'success');
        } else {
            mySnackbar.value.show(response.data.message, 'error');
        }
    } catch (error) {
        mySnackbar.value.show('Lo sentimos, hubo un problema al guardar la información de la casa. Intenta de nuevo, por favor.', 'error');
        console.error('Lo sentimos, hubo un problema al guardar la información de la casa. Intenta de nuevo, por favor.:', error);

    }
//    alert(JSON.stringify(values, null, 2))
})

</script>

<template>
    <div>
        <p v-if="loading">Cargango datos</p>
        <p v-else-if="error"> {{ error }}</p>
        <v-form @submit.prevent="submit" class="mt-2">
            <v-text-field
                v-model="propertyUnit.value.value"
                :error-messages="propertyUnit.errorMessage.value"
                variant="outlined"
                label="Código de propiedad"
            ></v-text-field>
            <v-text-field
                v-model="address.value.value"
                :error-messages="address.errorMessage.value"
                variant="outlined"
                label="Address"
            ></v-text-field>
            <v-text-field
                v-model="constructionArea.value.value"
                :error-messages="constructionArea.errorMessage.value"
                variant="outlined"
                label="Area de construcción"
            ></v-text-field>
            <v-text-field
                v-model="participationPercentage.value.value"
                :error-messages="participationPercentage.errorMessage.value"
                variant="outlined"
                label="Porcentaje de participación"
            ></v-text-field>
            <v-btn color="red" class="mt-3" type="submit">Grabar</v-btn>
        </v-form>
        <Snackbar ref="mySnackbar" />
    </div>
</template>

<style scoped>

</style>
