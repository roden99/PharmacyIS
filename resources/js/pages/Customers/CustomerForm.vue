<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';



import { CalendarDate, fromDate, getLocalTimeZone } from '@internationalized/date';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { normalizeDate, set } from '@vueuse/core';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { Field, FieldGroup, FieldLabel, FieldLegend, FieldSeparator, FieldSet } from '@/components/ui/field';
import BaseTab from '@/components/BaseTab.vue'
import BaseField from '@/components/BaseField.vue';
import Switch from '@/components/ui/switch/Switch.vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';




const props = defineProps({

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
    if (props.transactionType === 'delete') return 'Deactivate';
    return 'Yes';
});

const handleAlertClose = () => {
    isDialogOpen.value = false;

    if (props.transactionType === 'delete') {
        emit('member-form-closed')
    }
};


const isFormValidated = () => {
    const hasName = form.last_name.toString().trim();
    const hasCompany = form.company.toString().trim();

    if (form.is_drugstore) {
        if (!hasCompany) {
            toast.error('A company name is required for drugstore customers');
            return false;
        }
    } else {
        if (!hasName) {
            toast.error('Customer name is required');
            return false;
        }
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
    is_drugstore: props.customer?.is_drugstore === true || props.customer?.is_drugstore === 1 || props.customer?.is_drugstore === 'Yes',
    company: props.customer?.company || '',
    first_name: props.customer?.first_name || '',
    last_name: props.customer?.last_name || '',
    middle_name: props.customer?.middle_name || '',
    email: props.customer?.email || '',
    phone: props.customer?.phone || '',
    address: props.customer?.address || '',
    sales_account_id: props.customer?.sales_account_id ? Number(props.customer.sales_account_id) : null,
});




const emit = defineEmits(['handleSubmit', 'member-form-closed']);

const handleSubmit = () => {
    try {
        isSaving.value = true;
        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
        isSaving.value = false;
    }
}


// Address fields
const selectedProvince = ref('');
const selectedCity = ref('');
const selectedBarangay = ref('');

const provinceOptions = ref([]);
const cityOptions = ref([]);
const barangayOptions = ref([]);


const isSaving = ref(false);
const isLoading = ref(true);
const isBusy = computed(() => isSaving.value);
const isDialogOpen = ref(false);

onMounted(async () => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
        isLoading.value = false;
        return;
    }
    isLoading.value = false;
});

const closeDialog = () => {
    isDialogOpen.value = false;
    isSaving.value = false;
};

defineExpose({ closeDialog });

</script>

<template>
    <FormCard :loading="isBusy" size="lg">
        <form @submit.prevent="Submit" class="space-y-4 mt-4">
            <BaseField legend="Customer Information" description="Enter customer details">
                <template #fields>
                    <FieldGroup>
                        <div class="grid w-full grid-cols-12 gap-4">

                            <Field class="col-span-12">
                                <div class="flex items-center space-x-2">
                                    <Skeleton v-if="isLoading" class="h-5 w-9 rounded-full" />
                                    <Switch v-else :modelValue="form.is_drugstore"
                                        @update:modelValue="val => form.is_drugstore = val" />
                                    <Skeleton v-if="isLoading" class="h-4 w-16" />
                                    <FieldLabel v-else for="is_drugstore" class="font-normal cursor-pointer">
                                        Drugstore
                                    </FieldLabel>
                                </div>
                            </Field>


                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-20 mb-1" />
                                <FieldLabel v-else class="font-normal">Company:</FieldLabel>
                                <Skeleton v-if="isLoading" class="h-9 w-full" />
                                <Input v-else v-model="form.company" placeholder="Company Name" />
                            </Field>

                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-32 mb-1" />
                                <FieldLabel v-else class="font-normal">Customer Name:</FieldLabel>

                                <Skeleton v-if="isLoading" class="h-9 w-full" />
                                <Input v-else v-model="form.last_name" placeholder="Customer Name" />
                                <Skeleton v-if="isLoading" class="h-9 w-full" />

                                <!-- <Input v-else v-model="form.first_name" placeholder="First Name" />
                                    <Skeleton v-if="isLoading" class="h-9 w-full" />
                                    <Input v-else v-model="form.middle_name" placeholder="Middle Name" /> -->

                            </Field>

                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-24 mb-1" />
                                <FieldLabel v-else class="font-normal">Phone Number:</FieldLabel>
                                <Skeleton v-if="isLoading" class="h-9 w-full" />
                                <Input v-else v-model="form.phone" placeholder="Phone Number" />
                            </Field>

                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-28 mb-1" />
                                <FieldLabel v-else class="font-normal">Email Address:</FieldLabel>
                                <Skeleton v-if="isLoading" class="h-9 w-full" />
                                <Input v-else v-model="form.email" type="email" placeholder="Email Address" />
                            </Field>

                            <Field class="col-span-12 mb-6">
                                <Skeleton v-if="isLoading" class="h-4 w-36 mb-1" />
                                <FieldLabel v-else class="font-normal">Customer Address:</FieldLabel>
                                <Skeleton v-if="isLoading" class="h-9 w-full" />
                                <Input v-else v-model="form.address" placeholder="Customer Address" />
                            </Field>
                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>
        <template #footer>


            <BaseButton :skeleton="isLoading" type="button" :disabled="isBusy" @click="emit('member-form-closed')"
                transactionType="cancel">
            </BaseButton>



            <BaseButton type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isBusy" :disabled="isBusy" :skeleton="isLoading">
            </BaseButton>



        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :disabled="isBusy" :loading="isBusy"
            :transaction-type="props.transactionType" @cancel="handleAlertClose" @confirm="handleSubmit" />

    </FormCard>
</template>