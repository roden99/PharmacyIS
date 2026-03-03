<script setup>
import CustomerForm from '@/pages/Customers/CustomerForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['member-form-closed']);

const handleClose = () => {
    emit('member-form-closed');
};


const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/customers/${props.customer.id}`, formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Customer updated successfully!' });
            isProcessing.value = false;
            emit('member-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update customer.', { description: errors[firstErrorKey] });
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
            :card-title="'Update Customer'" :transaction-type="'update'" :customer="customer" />
    </div>

</template>
