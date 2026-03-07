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

    customer: {
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

const handleAlertClose = () => {
    isDialogOpen.value = false;

    if (props.transactionType === 'delete') {
        emit('member-form-closed')
    }
};


const isFormValidated = () => {
    if (!form.first_name.toString().trim() ||
        !form.last_name.toString().trim() ||
        !form.phone.toString().trim() ||
        !form.email.toString().trim()) {
        toast.error('Fill up the forms properly');
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

    //Customer Information
    first_name: props.customer?.first_name || '',
    last_name: props.customer?.last_name || '',
    middle_name: props.customer?.middle_name || '',
    email: props.customer?.email || '',
    phone: props.customer?.phone || '',
    address: props.customer?.address || '',
});




const emit = defineEmits(['handleSubmit', 'member-form-closed']);


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

onMounted(() => {



    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }

});

</script>

<template>
    <FormCard :loading="isProcessing" :card-title="cardTitle">
        <form @submit.prevent="Submit" class="space-y-4">

            <div class="w-full min-h-[310px] space-y-6">

                <BaseField>
                    <template #fieldGroups>
                        <!-- Customer Information -->
                        <FieldSet>
                            <!-- Customer Input Fields Here -->
                            <FieldGroup class="rounded-lg border p-4">

                                <div class="grid w-full grid-cols-15 gap-4">

                                    <Field class="col-span-15">
                                        <FieldLabel class="font-normal">Customer Name:</FieldLabel>
                                        <div class="grid grid-cols-3 gap-4">
                                            <Input v-model="form.last_name" placeholder="Last Name" required />
                                            <Input v-model="form.first_name" placeholder="First Name" required />
                                            <Input v-model="form.middle_name" placeholder="Middle Name" />
                                        </div>
                                    </Field>

                                    <Field class="col-span-7">
                                        <FieldLabel class=" font-normal">Phone Number:</FieldLabel>
                                        <Input v-model="form.phone" required />
                                    </Field>


                                    <Field class="col-span-8">
                                        <FieldLabel class="font-normal">Email Address:</FieldLabel>
                                        <Input v-model="form.email" type="email" required />
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
                    type="button" @click="openConfirmDialog">
                </BaseButton>

            </div>
        </form>
    </FormCard>

    <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing">
        <template #alertTitle>
            <template v-if="transactionType === 'create'">
                Are you sure you want to save?
            </template>

            <template v-if="transactionType === 'update'">
                Are you sure you want to update?
            </template>

            <template v-if="transactionType === 'delete'">
                Are you sure you want to delete?
            </template>

        </template>
        <template #alertDescription>
            <h4 class="font-semibold text-sm mb-2">Customer Details:</h4>
            <div class="text-sm space-y-1">
                <p><span class="font-medium">Name:</span> {{ form.first_name }} {{ form.middle_name }} {{ form.last_name
                }}</p>
                <p><span class="font-medium">Email:</span> {{ form.email || 'N/A' }}</p>
                <p><span class="font-medium">Phone:</span> {{ form.phone || 'N/A' }}</p>
                <p v-if="form.address"><span class="font-medium">Address:</span> {{ form.address }}</p>
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