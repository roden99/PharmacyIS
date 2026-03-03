<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, version } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';


import { CalendarDate, fromDate, getLocalTimeZone } from '@internationalized/date';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { normalizeDate, set } from '@vueuse/core';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { Field, FieldGroup, FieldLabel, FieldLegend, FieldSeparator, FieldSet } from '@/components/ui/field';
import BaseTab from '@/components/BaseTab.vue'
import BaseField from '@/components/BaseField.vue';




const props = defineProps({

    isProcessing: {
        type: Boolean,
        default: false,
    },

    cardTitle: {
        type: String,
        default: 'Form',
    },

    product: {
        type: Object,
        default: null,
    },

    brands: {
        type: Array,
        default: () => []
    },

    transactionType: {
        type: String,
        default: 'create',
    },

});

const confirmButtonText = computed(() => {
    if (props.transactionType === 'create') return 'Save';
    if (props.transactionType === 'update') return 'Update';
    if (props.transactionType === 'delete') return 'Delete';
    return 'Yes';
});




const form = useForm({
    productname: props.product?.productname || '',
    brand_id: props.product?.brand_id || null,
    isgeneric: props.product?.isgeneric || false,
});

// Convert brands to options format for BaseSelect
const brandOptions = computed(() => {
    return props.brands.map(brand => ({
        value: brand.id,
        label: brand.brandname
    }));
});




const emit = defineEmits(['save', 'form-closed']);



const save = () => {
    try {

        emit('save', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
}



</script>

<template>
    <!-- <FormCard v-show="!isDialogOpen" :card-title="cardTitle"> -->

    <FormCard :loading="isProcessing" :card-title="cardTitle" max-width="max-w-lg">
        <form @submit.prevent="Submit" class="space-y-4">

            <div class="w-full space-y-6">

                <BaseField>
                    <template #fieldGroups>
                        <!-- Product Information -->
                        <FieldSet>
                            <!-- <FieldLegend>Product Information</FieldLegend> -->

                            <!-- Product Input Fields Here -->
                            <FieldGroup class="rounded-lg border p-4">

                                <div class="grid w-full grid-cols-15 gap-4">
                                    <Field class="col-span-15">
                                        <FieldLabel class="font-normal">Product Name:</FieldLabel>
                                        <Input v-model="form.productname" required />
                                    </Field>

                                    <Field class="col-span-15">
                                        <FieldLabel class="font-normal">Brand:</FieldLabel>
                                        <BaseSelect v-model="form.brand_id" :options="brandOptions"
                                            placeholder="Select Brand" />
                                    </Field>

                                    <Field class="col-span-15">
                                        <div class="flex items-center space-x-2">
                                            <Checkbox id="isgeneric" v-model:checked="form.isgeneric" />
                                            <FieldLabel for="isgeneric" class="font-normal cursor-pointer">
                                                Is Generic Product
                                            </FieldLabel>
                                        </div>
                                    </Field>



                                </div>
                            </FieldGroup>
                        </FieldSet>
                    </template>

                </BaseField>


            </div>



            <div class="flex justify-end space-x-2">

                <BaseButton text="Cancel" variant="outline" color="secondary" type="button"
                    @click="emit('form-closed')">
                </BaseButton>

                <BaseButton :loading="isProcessing" :text="confirmButtonText" variant="default" color="primary"
                    type="button" @click="save">
                </BaseButton>


            </div>

        </form>

    </FormCard>



</template>
