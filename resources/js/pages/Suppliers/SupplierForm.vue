<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';


import { CalendarDate, fromDate, getLocalTimeZone } from '@internationalized/date';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { normalizeDate, set } from '@vueuse/core';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { Field, FieldGroup, FieldLabel, FieldLegend, FieldSeparator, FieldSet } from '@/components/ui/field';
import BaseTab from '@/components/BaseTab.vue'
import BaseField from '@/components/BaseField.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';




const props = defineProps({

    isProcessing: {
        type: Boolean,
        default: false,
    },

    cardTitle: {
        type: String,
        default: 'Form',
    },

    supplier: {
        type: Object,
        default: null,
    },

    transactionType: {
        type: String,
        default: 'create',
    },

});


const handleAlertClose = () => {
    isDialogOpen.value = false;

    if (props.transactionType === 'delete') {
        emit('form-closed')
    }
};


const isFormValidated = () => {
    if (!form.company.toString().trim()) {
        toast.error('ERROR', { description: 'Please complete all required fields.' });
        return false;
    }

    return true;
};


const openConfirmDialog = () => {

    form.clearErrors();
    if (!isFormValidated()) return false;
    isDialogOpen.value = true;
    return true;

};

const buttonVariants = computed(() => {

    return props.transactionType === 'create' ? 'default' : props.transactionType === 'update' ? 'default' : 'destructive';
});





const form = useForm({

    //Supplier Information
    company: props.supplier?.company || '',
    tin: props.supplier?.tin || '',
    lastname: props.supplier?.lastname || '',
    firstname: props.supplier?.firstname || '',
    middlename: props.supplier?.middlename || '',
    contact_email: props.supplier?.contact_email || '',
    contact_phone: props.supplier?.contact_phone || '',
    address: props.supplier?.address || '',
});




const emit = defineEmits(['handleSubmit', 'form-closed']);

const handleSubmit = () => {
    try {

        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
}


// Address fields
const selectedProvince = ref('');
const selectedCity = ref('');
const selectedBarangay = ref('');

const provinceOptions = ref([]);
const cityOptions = ref([]);
const barangayOptions = ref([]);



const isDialogOpen = ref(false);
const isLoading = ref(true);

// Fields: company(12), contact person(12), phone(4), email(4), tin(4), address(12), street(12)
const { skeletonLayout } = useFieldGroupSkeleton([12, 12, 4, 4, 4, 12, 12]);

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        isDialogOpen.value = false;
    }
});

onMounted(async () => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
    isLoading.value = true;
    try {
        // await db calls here
    } finally {
        isLoading.value = false;
    }
});

// Fetch provinces on mount
// onMounted(async () => {
//     try {
//         const response = await axios.get('/api/address/provinces');
//         provinceOptions.value = response.data.map(p => ({
//             value: p.province_id,
//             label: p.name
//         }));
//     } catch (error) {
//         console.error('Error loading provinces:', error);
//     }
// });

// // Load cities when province changes
// const loadCities = async (provinceId) => {
//     if (!provinceId) return;
//     try {
//         const response = await axios.get(`/api/address/cities/${provinceId}`);
//         cityOptions.value = response.data.map(c => ({
//             value: c.city_id,
//             label: c.name
//         }));
//         selectedCity.value = '';
//         selectedBarangay.value = '';
//         barangayOptions.value = [];
//     } catch (error) {
//         console.error('Error loading cities:', error);
//     }
// };

// // Load barangays when city changes
// const loadBarangays = async (cityId) => {
//     if (!cityId) return;
//     try {
//         const response = await axios.get(`/api/address/barangays/${cityId}`);
//         barangayOptions.value = response.data.map(b => ({
//             value: b.code,
//             label: b.name
//         }));
//         selectedBarangay.value = '';
//     } catch (error) {
//         console.error('Error loading barangays:', error);
//     }
// };


</script>

<template>
    <!-- <FormCard v-show="!isDialogOpen" :card-title="cardTitle"> -->

    <FormCard :loading="isProcessing" size="lg">
        <form @submit.prevent="Submit" class="space-y-4 mt-4">


            <BaseField legend="Supplier Information" description="Enter supplier details">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Supplier Name:</FieldLabel>
                                <Input v-model="form.company" required />
                            </Field>

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Contact Person:</FieldLabel>
                                <div class="grid grid-cols-3 gap-4">
                                    <Input v-model="form.lastname" placeholder="Last Name" />
                                    <Input v-model="form.firstname" placeholder="First Name" />
                                    <Input v-model="form.middlename" placeholder="Middle Name" />
                                </div>
                            </Field>

                            <Field class="col-span-4">
                                <FieldLabel class="font-normal">Phone Number:</FieldLabel>
                                <Input v-model="form.contact_phone" />
                            </Field>

                            <Field class="col-span-4">
                                <FieldLabel class="font-normal">Email Address:</FieldLabel>
                                <Input v-model="form.contact_email" type="email" />
                            </Field>

                            <Field class="col-span-4">
                                <FieldLabel class="font-normal">TIN Number:</FieldLabel>
                                <Input v-model="form.tin" />
                            </Field>

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Address:</FieldLabel>
                                <div class="grid grid-cols-3 gap-4">
                                    <BaseCombobox v-model="selectedProvince" placeholder="Province"
                                        empty-message="No province found" width="w-full" />
                                    <BaseCombobox v-model="selectedCity" placeholder="Municipality"
                                        empty-message="No municipality found" width="w-full" />
                                    <BaseCombobox v-model="selectedBarangay" placeholder="Select Barangay"
                                        empty-message="No barangay found" width="w-full" />
                                </div>
                            </Field>

                            <Field class="col-span-12 mb-6">
                                <FieldLabel class="font-normal">Street Address / Unit / Building:</FieldLabel>
                                <Input v-model="form.address"
                                    placeholder="Enter street address, unit number, building name, etc." />
                            </Field>
                        </div>
                    </FieldGroup>

                </template>

            </BaseField>


        </form>
        <template #footer>
            <Skeleton v-if="isLoading" class="h-9 w-20" />
            <BaseButton v-else type="button" :disabled="isProcessing" @click="emit('form-closed')"
                transactionType="cancel">
            </BaseButton>
            <Skeleton v-if="isLoading" class="h-9 w-20" />
            <BaseButton v-else type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isProcessing" :disabled="isProcessing">
            </BaseButton>
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />



    </FormCard>




</template>
