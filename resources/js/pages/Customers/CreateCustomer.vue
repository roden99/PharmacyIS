<script setup>
import CustomerForm from '@/pages/Customers/CustomerForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const emit = defineEmits(['member-form-closed']);

const handleClose = () => {
    emit('member-form-closed');
};



const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/customers', formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Customer created successfully!' });
            isProcessing.value = false;
            emit('member-form-closed'); // Close modal on success
        },
        onError: (errors) => {
            console.log('asdadasd')
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create customer.', { description: errors[firstErrorKey] });
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
        <CustomerForm @handleSubmit="handleSubmit" @member-form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Customer'" :transaction-type="'create'" />
    </div>

</template>
