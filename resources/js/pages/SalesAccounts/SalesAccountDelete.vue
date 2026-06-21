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

const emit = defineEmits(['form-closed', 'sales-account-deleted']);

const formRef = ref(null);

const handleClose = () => {
    emit('form-closed');
};

const handleSubmit = async () => {
    try {
        await axios.delete(`/sales-accounts/${props.salesAccount.id}`);
        toast.success('Success', { description: 'Sales account deactivated successfully!' });
        formRef.value?.closeDialog();
        emit('sales-account-deleted');
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to deactivate sales account.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to deactivate sales account.');
        }
        formRef.value?.closeDialog();
        emit('form-closed');
    } finally {

    }
};
</script>

<template>
    <div>
        <SalesAccountForm ref="formRef" @handleSubmit="handleSubmit" @form-closed="handleClose"
            :card-title="'Delete Sales Account'" :transaction-type="'delete'" :sales-account="salesAccount" />
    </div>
</template>