<template>
    <v-col cols="12" :sm="sm" :md="md">
        <v-card :color="color" theme="dark" class="info-card" :to="to">
            <v-icon class="info-card-icon">{{ icon }}</v-icon>
            <v-card-text>
                <div class="text-h3 font-weight-bold">
                    {{ value }}<small v-if="suffix">{{ suffix }}</small>
                </div>
                <p class="mt-1">{{ title }}</p>
            </v-card-text>
            <v-card-actions class="info-card-actions" v-if="false">
                <v-btn variant="text" append-icon="mdi-arrow-right-circle">
                    More info
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-col>
</template>

<script setup>
defineProps({
    color: { type: String, default: 'primary' },
    icon: { type: String, required: true },
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    suffix: { type: String, default: '' },
    to: { type: String, default: '#' },
    sm: { type: [String, Number], default: 6 },
    md: { type: [String, Number], default: 3 },
});
</script>

<!-- El CSS se queda igual, puedes ponerlo aquí con <style scoped> o en un archivo global -->
<style scoped>
.info-card {
    position: relative;
    overflow: hidden;
    transition: transform 0.2s ease-in-out;
}
.info-card:hover {
    transform: translateY(-5px);
}
.info-card-icon {
    position: absolute;
    top: -10px;
    right: -10px;
    font-size: 90px !important;
    opacity: 0.2;
    transition: all 0.3s ease-in-out;
}
.info-card:hover .info-card-icon {
    transform: scale(1.1) rotate(-5deg);
    opacity: 0.3;
}
.info-card-actions {
    background-color: rgba(0, 0, 0, 0.15);
    justify-content: center;
}
</style>
