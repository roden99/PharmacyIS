<script setup>
import SalesAccountForm from './SalesAccountForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const emit = defineEmits(['form-closed', 'sales-account-created']);

const formRef = ref(null);

const handleClose = () => {
    emit('form-closed');
};


const handleSubmit = async (formData) => {

    try {
        const res = await axios.post('/sales-accounts', formData);
        toast.success('Success', { description: 'Sales account created successfully!' });
        emit('sales-account-created', res.data.salesAccount);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create sales account.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to create sales account.');
        }
        formRef.value?.closeDialog();
    }
    finally {

    }
};

</script>


<template>

    <div>
        <SalesAccountForm ref="formRef" @handleSubmit="handleSubmit" @form-closed="handleClose"
            :card-title="'New Sales Account'" :transaction-type="'create'" />
    </div>

</template>
