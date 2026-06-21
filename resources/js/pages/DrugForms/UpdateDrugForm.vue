<script setup>
import DrugFormForm from './DrugFormForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    drugform: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['drugform-form-closed']);

const handleClose = () => {
    emit('drugform-form-closed');
};


const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/drugforms/${props.drugform.id}`, formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Drug form updated successfully!' });
            isProcessing.value = false;
            emit('drugform-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update drug form.', { description: errors[firstErrorKey] });
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        }
    });
};

</script>
<template>


    <div>
        <DrugFormForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'Update Drug Form'" :transaction-type="'update'" :drugform="drugform" />
    </div>

</template>
