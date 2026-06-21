<script setup>
import SalesAccountForm from './SalesAccountForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const props = defineProps({
    salesAccount: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['form-closed', 'sales-account-updated']);
const formRef = ref(null);
const handleClose = () => {
    emit('form-closed');
};

const handleSubmit = async (formData) => {
    try {
        const res = await axios.put(`/sales-accounts/${props.salesAccount.id}`, formData);
        toast.success('Success', { description: 'Sales account updated successfully!' });
        formRef.value?.closeDialog();
        emit('sales-account-updated', res.data.salesAccount);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update sales account.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to update sales account.');
        }
        formRef.value?.closeDialog();
    } finally {

    }
};

</script>


<template>

    <div>
        <SalesAccountForm ref="formRef" @handleSubmit="handleSubmit" @form-closed="handleClose"
            :card-title="'Update Sales Account'" :transaction-type="'update'" :sales-account="salesAccount" />
    </div>

</template>
