<script setup>
import {ref, watch} from 'vue';
import ImageUpload from "@/components/admin/ImageUpload.vue";

// Definimos las props y los emits para la comunicación con el padre
const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);

// Usamos una copia local para no mutar las props directamente
const localSettings = ref({...props.modelValue});

// Observamos cambios en las props por si se recargan desde el padre
watch(() => props.modelValue, (newValue) => {
    localSettings.value = {...newValue};
}, {deep: true});

// Función para notificar al padre de cualquier cambio
const emitUpdate = () => {
    emit('update:modelValue', localSettings.value);
};

const weekDays = [
    {text: 'Lunes', value: '1'},
    {text: 'Martes', value: '2'},
    {text: 'Miércoles', value: '3'},
    {text: 'Jueves', value: '4'},
    {text: 'Viernes', value: '5'},
    {text: 'Sábado', value: '6'},
    {text: 'Domingo', value: '0'},
];
</script>
<template>
    <v-form>
        <v-row>
            <v-col cols="12" md="6">
                <ImageUpload label="Estadísticas de gastos anuales"
                             v-model="localSettings.annual_expense_statistics_imagen"
                             @update:modelValue="emitUpdate"/>
            </v-col>
            <v-col cols="12" md="6">
                <ImageUpload label="Firma para recibos"
                             v-model="localSettings.signature_for_receipts_imagen"
                             @update:modelValue="emitUpdate"
                />
            </v-col>
        </v-row>
        <v-divider class="my-6"></v-divider>
        <v-row>
            <v-col cols="12" md="8">
                <label class="v-label">Precio de KW</label>
                <v-text-field
                    v-model="localSettings.price_per_kw"
                    variant="outlined"
                    density="compact"
                    @update:modelValue="emitUpdate"
                ></v-text-field>
            </v-col>
        </v-row>
        <div v-if="true">
            <v-divider class="my-6"></v-divider>

            <v-row>
                <v-col cols="12" md="8">
                    <label class="v-label">Nombre del sitio</label>
                    <v-text-field
                        v-model="localSettings.site_title"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-text-field>

                    <label class="v-label">Presidente a cargo</label>
                    <v-text-field
                        v-model="localSettings.name_president"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-text-field>

                    <label class="v-label">Banco de abono</label>
                    <v-text-field
                        v-model="localSettings.name_deposit_bank"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-text-field>

                    <label class="v-label">Cuenta de abono</label>
                    <v-text-field
                        v-model="localSettings.bank_account_payment"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-text-field>

                    <label class="v-label">CCI de Cuenta de abono</label>
                    <v-text-field
                        v-model="localSettings.bank_account_cci_payment"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-text-field>

                    <label class="v-label">Email de Contacto</label>
                    <v-text-field
                        v-model="localSettings.email_contact"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-text-field>

                    <label class="v-label">Descripción del gráfico</label>
                    <v-text-field
                        v-model="localSettings.chart_description"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-text-field>

                    <label class="v-label">Desgloso del cobro</label>
                    <v-text-field
                        v-model="localSettings.details_payment"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-text-field>
                    <label class="v-label"  v-if="false">Descripción corta</label>
                    <v-text-field
                        v-if="false"
                        v-model="localSettings.tagline"
                        variant="outlined"
                        density="compact"
                        hint="En pocas palabras, explica de qué va este sitio."
                        persistent-hint
                        @update:modelValue="emitUpdate"
                    ></v-text-field>
                </v-col>
            </v-row>

            <v-divider class="my-6"></v-divider>

            <v-row v-if="false">
                <v-col cols="12" md="8">
                    <label class="v-label">Formato de fecha</label>
                    <v-radio-group v-model="localSettings.date_format" @update:modelValue="emitUpdate">
                        <v-radio label="Julio 11, 2025" value="F j, Y"></v-radio>
                        <v-radio label="2025-07-11" value="Y-m-d"></v-radio>
                        <v-radio label="07/11/2025" value="m/d/Y"></v-radio>
                        <v-radio label="11/07/2025" value="d/m/Y"></v-radio>
                        <div>
                            <v-radio value="custom_date" class="d-inline-block mr-2"></v-radio>
                            <label class="d-inline-block mr-2">Personalizado:</label>
                            <v-text-field
                                v-model="localSettings.date_format_custom"
                                class="d-inline-block"
                                style="width: 150px"
                                variant="outlined"
                                density="compact"
                                :disabled="localSettings.date_format !== 'custom_date'"
                                @update:modelValue="emitUpdate"
                            ></v-text-field>
                        </div>
                    </v-radio-group>
                </v-col>
            </v-row>

            <v-divider class="my-6"></v-divider>

            <v-row v-if="false">
                <v-col cols="12" md="8">
                    <label class="v-label">Formato de hora</label>
                    <v-radio-group v-model="localSettings.time_format" @update:modelValue="emitUpdate">
                        <v-radio label="4:56 pm" value="g:i a"></v-radio>
                        <v-radio label="4:56 PM" value="g:i A"></v-radio>
                        <v-radio label="16:56" value="H:i"></v-radio>
                        <div>
                            <v-radio value="custom_time" class="d-inline-block mr-2"></v-radio>
                            <label class="d-inline-block mr-2">Personalizado:</label>
                            <v-text-field
                                v-model="localSettings.time_format_custom"
                                class="d-inline-block"
                                style="width: 150px"
                                variant="outlined"
                                density="compact"
                                :disabled="localSettings.time_format !== 'custom_time'"
                                @update:modelValue="emitUpdate"
                            ></v-text-field>
                        </div>
                    </v-radio-group>
                </v-col>
            </v-row>

            <v-divider class="my-6"></v-divider>

            <v-row v-if="false">
                <v-col cols="12" md="4">
                    <label class="v-label">La semana comienza el</label>
                    <v-select
                        v-model="localSettings.start_of_week"
                        :items="weekDays"
                        item-title="text"
                        item-value="value"
                        variant="outlined"
                        density="compact"
                        @update:modelValue="emitUpdate"
                    ></v-select>
                </v-col>
            </v-row>
        </div>
    </v-form>
</template>
