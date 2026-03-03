<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

    supplier: {
        type: Object,
        default: null,
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

    //Supplier Information
    company: props.customer?.company || '',
    lastname: props.customer?.lastname || '',
    firstname: props.customer?.firstname || '',
    middlename: props.customer?.middlename || '',
    contact_email: props.customer?.contact_email || '',
    contact_phone: props.customer?.contact_phone || '',
    address: props.customer?.address || '',
});




const emit = defineEmits(['saveCustomer', 'member-form-closed']);



const saveCustomer = () => {
    try {

        emit('saveCustomer', form.data());
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

    <FormCard :loading="isProcessing" :card-title="cardTitle">
        <form @submit.prevent="Submit" class="space-y-4">

            <div class="w-full min-h-[310px] space-y-6">

                <BaseField>
                    <template #fieldGroups>
                        <!-- Customer Information -->
                        <FieldSet>
                            <!-- <FieldLegend>Customer Information</FieldLegend> -->

                            <!-- Customer Input Fields Here -->
                            <FieldGroup class="rounded-lg border p-4">

                                <div class="grid w-full grid-cols-15 gap-4">

                                    <Field class="col-span-15">
                                        <FieldLabel class="font-normal">Customer Details:</FieldLabel>
                                        <div class="grid grid-cols-3 gap-4">
                                            <Input v-model="form.lastname" placeholder="Last Name" required />
                                            <Input v-model="form.firstname" placeholder="First Name" required />
                                            <Input v-model="form.middlename" placeholder="Middle Name" />
                                        </div>
                                    </Field>

                                    <Field class="col-span-7">
                                        <FieldLabel class=" font-normal">Phone Number:</FieldLabel>
                                        <Input v-model="form.contact_phone" required />
                                    </Field>


                                    <Field class="col-span-8">
                                        <FieldLabel class="font-normal">Email Address:</FieldLabel>
                                        <Input v-model="form.contact_email" type="email" required />
                                    </Field>


                                    <Field class="col-span-15">
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

                                    <Field class="col-span-15 mb-6">
                                        <FieldLabel class="font-normal">Street Address / Unit / Building:</FieldLabel>
                                        <Input v-model="form.address"
                                            placeholder="Enter street address, unit number, building name, etc." />
                                    </Field>
                                </div>
                            </FieldGroup>
                        </FieldSet>
                    </template>

                </BaseField>


            </div>



            <div class="flex justify-end space-x-2">

                <BaseButton text="Cancel" variant="outline" color="secondary" type="button"
                    @click="emit('member-form-closed')">
                </BaseButton>

                <BaseButton :loading="isProcessing" :text="confirmButtonText" variant="default" color="primary"
                    type="button" @click="saveSupplier">
                </BaseButton>


            </div>

        </form>


    </FormCard>



</template>
