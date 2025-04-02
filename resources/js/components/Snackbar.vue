<template>
    <v-snackbar
        v-model="snackbar"
        :color="color"
        :timeout="timeout"
        location="top"
        position="absolute"
        :multi-line="multiLine"
    >
        {{ text }}
    </v-snackbar>
</template>

<script setup>
import { ref, watch } from 'vue';

const snackbar = ref(false);
const text = ref('');
const color = ref('success');
const timeout = ref(3000);
const multiLine = ref(false);

watch(snackbar, (newValue) => {
    if (!newValue) {
        text.value = '';
        color.value = 'success';
    }
});

function show(newText, newColor = 'success', newTimeout = 5000, newMultiLine = false) {
    text.value = newText;
    color.value = newColor;
    timeout.value = newTimeout;
    multiLine.value = newMultiLine;
    snackbar.value = true;
}

defineExpose({
    show
});
</script>
