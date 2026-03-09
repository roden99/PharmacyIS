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
import { onMounted, ref, computed, version, watch } from 'vue';
import { toast } from 'vue-sonner';


import { CalendarDate, fromDate, getLocalTimeZone } from '@internationalized/date';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { normalizeDate, set } from '@vueuse/core';
import SearchableCombobox from '@/components/SearchableCombobox.vue';
import { Field, FieldGroup, FieldLabel, FieldLegend, FieldSeparator, FieldSet } from '@/components/ui/field';
import BaseTab from '@/components/BaseTab.vue'
import Switch from '@/components/ui/switch/Switch.vue';
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

    productUnits: {
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

const buttonVariants = computed(() => {
    return props.transactionType === 'create' ? 'default' : props.transactionType === 'update' ? 'default' : 'destructive';
});

const isDialogOpen = ref(false);

const handleAlertClose = () => {
    isDialogOpen.value = false;

    if (props.transactionType === 'delete') {
        emit('form-closed');
    }
};

const openConfirmDialog = () => {
    form.clearErrors();
    isDialogOpen.value = true;
    return true;
};

onMounted(() => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
});




const form = useForm({
    productname: props.product?.productname || '',
    brand_id: props.product?.brand_id || null,
    product_unit_id: props.product?.product_unit_id || null,
    isgeneric: props.product?.isgeneric || false,
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const selectedBrand = ref(props.product?.brand_id || null);
const selectedUnit = ref(props.product?.product_unit_id || null);

// Prepare initial options with current product's brand and unit included
const brandsWithCurrent = computed(() => {
    const brandsList = [...props.brands];
    if (props.product?.brand && !brandsList.find(b => b.id === props.product.brand.id)) {
        brandsList.unshift(props.product.brand);
    }
    return brandsList;
});

const unitsWithCurrent = computed(() => {
    const unitsList = [...props.productUnits];
    if (props.product?.unit && !unitsList.find(u => u.id === props.product.unit.id)) {
        unitsList.unshift(props.product.unit);
    }
    return unitsList;
});

// Watch selectedBrand and sync with form.brand_id
watch(selectedBrand, (newValue) => {
    form.brand_id = newValue;
});

// Watch selectedUnit and sync with form.product_unit_id
watch(selectedUnit, (newValue) => {
    form.product_unit_id = newValue;
});

const handleSubmit = () => {
    try {

        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
}


</script>

<template>
    <!-- <FormCard v-show="!isDialogOpen" :card-title="cardTitle"> -->

    <FormCard :loading="isProcessing" :card-title="cardTitle" max-width="max-w-2xl">
        <form @submit.prevent="Submit" class="space-y-4">

            <div class="w-full space-y-6">

                <BaseField>
                    <template #fieldGroups>
                        <!-- Product Information -->
                        <FieldSet>
                            <!-- <FieldLegend>Product Information</FieldLegend> -->

                            <!-- Product Input Fields Here -->
                            <FieldGroup class="rounded-lg border p-4">

                                <div class="grid w-full grid-cols-12 gap-4">


                                    <Field class="col-span-12">
                                        <div class="flex items-center space-x-2">
                                            <Switch id="isgeneric" v-model:checked="form.isgeneric" />
                                            <FieldLabel for="isgeneric" class="font-normal cursor-pointer">
                                                Generic
                                            </FieldLabel>
                                        </div>
                                    </Field>



                                    <SearchableCombobox v-model="selectedBrand" label="Search Brand"
                                        :initial-options="brandsWithCurrent" endpoint="/brands" response-key="brands"
                                        label-key="brandname" empty-message="Empty Search. Create?"
                                        col-span="col-span-7" :max-results="5" />


                                    <SearchableCombobox v-model="selectedUnit" label="Search Unit"
                                        :initial-options="unitsWithCurrent" endpoint="/product-units"
                                        response-key="productUnits" label-key="unit_name" empty-message="No units found"
                                        col-span="col-span-5" :max-results="5" />


                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Product Name:</FieldLabel>
                                        <Input v-model="form.productname" required />
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
                    type="button" @click="openConfirmDialog">
                </BaseButton>


            </div>

        </form>

    </FormCard>

    <BaseAlertDialog v-model:open="isDialogOpen">
        <template #alertTitle>
            <template v-if="transactionType === 'create'">
                Are you sure you want to save?
            </template>

            <template v-if="transactionType === 'update'">
                Are you sure you want to update?
            </template>

            <template v-if="transactionType === 'delete'">
                Are you sure you want to deactivate this product?
            </template>
        </template>
        <template #alertDescription>
            <h4 class="font-semibold text-sm mb-2">Product Details:</h4>
            <div class="text-sm space-y-1">
                <p><span class="font-medium">Product Name:</span> {{ form.productname || 'N/A' }}</p>
                <p><span class="font-medium">Brand:</span> {{ brandsWithCurrent.find(b => b.id === form.brand_id)?.brandname || 'N/A' }}</p>
                <p><span class="font-medium">Unit:</span> {{ unitsWithCurrent.find(u => u.id === form.product_unit_id)?.unit_name || 'N/A' }}</p>
                <p><span class="font-medium">Type:</span> {{ form.isgeneric ? 'Generic' : 'Branded' }}</p>
            </div>
        </template>

        <template #alertFooter>
            <BaseButton text="Cancel" :disabled="isProcessing" :variant="'outline'" color="secondary" type="button"
                @click="handleAlertClose" />

            <BaseButton :text="confirmButtonText" :variant="buttonVariants" color="primary" type="button"
                @click="handleSubmit" :disabled="isProcessing" :loading="isProcessing" />
        </template>
    </BaseAlertDialog>

</template>
