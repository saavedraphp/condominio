<script setup> // Using <script setup> syntax (Vue 3 Composition API)
import { ref, onMounted } from 'vue';
import axios from 'axios'; // Make sure axios is installed and configured

const qrCodeUrl = ref(null);
const userName = ref('');
const url = ref(''); // This will hold the URL for the QR code
const loading = ref(true);
const error = ref(null);

// Function to fetch data from the backend
const fetchQrCodeData = async () => {
    loading.value = true;
    error.value = null;
    try {
        // Adjust the URL if your API prefix is different or if using web routes
        const response = await axios.get('/user/get-qr-code');

        qrCodeUrl.value = response.data.qr_code_url;
        userName.value = response.data.user_name;
        url.value = response.data.url;

    } catch (err) {
        console.error("Error fetching QR code data:", err);
        if (err.response && err.response.status === 401) {
            error.value = "Authentication error. Please log in again.";
        } else {
            error.value = "Failed to load QR code. Please try again later.";
        }
        // Optionally clear data on error
        qrCodeUrl.value = null;
        userName.value = '';
    } finally {
        loading.value = false;
    }
};

// Fetch data when the component is mounted
onMounted(() => {
    fetchQrCodeData();
});

</script>
<template>
    <v-container fluid class="fill-height">
        <v-row align="center" justify="center" class="fill-height">
            <v-col cols="12" md="8" lg="6" class="text-center">

                <!-- Loading Indicator -->
                <div v-if="loading" class="pa-5">
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        size="64"
                    ></v-progress-circular>
                    <p class="mt-4 text-subtitle-1">Loading QR Code...</p>
                </div>

                <!-- Error Message -->
                <v-alert v-else-if="error" type="error" dense outlined prominent>
                    {{ error }}
                </v-alert>

                <!-- QR Code and Name Display -->
                <div v-else-if="qrCodeUrl">
                    <v-card elevation="2"  >
                        <v-img
                            :src="qrCodeUrl"
                            alt="User QR Code"
                            contain
                            max-height="350"
                        max-width="350"
                        class="qr-code-image"
                        ></v-img>
                    </v-card>

                    <div class="text-h5 mt-5 font-weight-medium"> <!-- Larger text for name -->
                        {{ userName }}
                    </div>
                </div>

                <!-- Fallback if no data -->
                <div v-else>
                    <p>Could not load user QR code information.</p>
                </div>

            </v-col>
        </v-row>
    </v-container>
</template>



// Optional: Add some basic styling if needed
<style scoped>
.qr-code-image {
    display: block; /* Ensure image behaves like a block element within the card */
    margin: auto; /* Helps centering if card padding isn't enough */
}
.fill-height {
    min-height: calc(100vh - 64px); /* Adjust 64px based on your AppBar height */
    /* Or simply use height: 100% if the parent allows it */
}
</style>
