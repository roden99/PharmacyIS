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
    router.delete(`/drugforms/${props.drugform.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Drug form deactivated successfully!' });
            isProcessing.value = false;
            emit('drugform-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to deactivate drug form.', { description: errors[firstErrorKey] });
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
            :card-title="'Delete Drug Form'" :transaction-type="'delete'" :drugform="drugform" />
    </div>

</template>
