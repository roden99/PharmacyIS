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

    warehouse: {
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
        emit('form-closed')
    }
};


const isFormValidated = () => {
    if (!form.warehousename.toString().trim()) {
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

    //Warehouse Information
    warehousename: props.warehouse?.warehousename || '',

});




const emit = defineEmits(['handleSubmit', 'form-closed']);


const handleSubmit = () => {
    try {

        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
}



const isDialogOpen = ref(false);

onMounted(() => {



    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }

});



</script>

<template>
    <!-- <FormCard v-show="!isDialogOpen" :card-title="cardTitle"> -->

    <FormCard :loading="isProcessing" :card-title="cardTitle" max-width="max-w-lg">
        <form @submit.prevent="Submit" class="space-y-4">

            <div class="w-full space-y-6">

                <BaseField>
                    <template #fieldGroups>
                        <!-- Warehouse Information -->
                        <FieldSet>
                            <!-- <FieldLegend>Warehouse Information</FieldLegend> -->

                            <!-- Warehouse Input Fields Here -->
                            <FieldGroup class="rounded-lg border p-4">

                                <div class="grid w-full grid-cols-15 gap-4">
                                    <Field class="col-span-15">
                                        <FieldLabel class="font-normal">Warehouse Name:</FieldLabel>
                                        <Input v-model="form.warehousename" required />
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
                Are you sure you want to delete?
            </template>
        </template>

        <template #alertDescription>
            <template v-if="transactionType === 'create'">
                This will create a new warehouse in the system.
            </template>

            <template v-if="transactionType === 'update'">
                This will update the warehouse information.
            </template>

            <template v-if="transactionType === 'delete'">
                This action cannot be undone. This will permanently delete the warehouse.
            </template>
        </template>

        <template #alertActions>
            <BaseButton text="Cancel" variant="outline" color="secondary" type="button" @click="handleAlertClose">
            </BaseButton>

            <BaseButton :loading="isProcessing" :text="confirmButtonText" :variant="buttonVariants" color="primary"
                type="button" @click="handleSubmit">
            </BaseButton>
        </template>
    </BaseAlertDialog>

</template>
