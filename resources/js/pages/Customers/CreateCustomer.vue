<script setup>
import CustomerForm from '@/pages/Customers/CustomerForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const emit = defineEmits(['member-form-closed', 'customer-created']);

const formRef = ref(null);

const handleClose = () => {
    emit('member-form-closed');
};

const handleSubmit = async (formData) => {
    try {
        const res = await axios.post('/customers', formData);
        toast.success('Success', { description: 'Customer created successfully!' });
        formRef.value?.closeDialog();
        emit('customer-created', res.data.customer);
        emit('member-form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create customer.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to create customer.');
        }
        formRef.value?.closeDialog();
    } finally {

    }
};
</script>

<template>
    <div>
        <CustomerForm ref="formRef" @handleSubmit="handleSubmit" @member-form-closed="handleClose"
            :card-title="'New Customer'" :transaction-type="'create'" />
    </div>
</template>
