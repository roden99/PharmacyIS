<script setup>
import CustomerAccountForm from './CustomerAccountForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

const emit = defineEmits(['member-form-closed', 'customer-account-created']);

const formRef = ref(null);

const handleClose = () => {
    emit('member-form-closed');
};

const handleSubmit = async (formData) => {
    let saved = false;
    try {
        const response = await axios.post('/customer-accounts', formData);
        const results = response.data.results ?? [];
        const successes = results.filter((r) => r.success);
        const failures = results.filter((r) => !r.success);

        if (successes.length > 0) {
            saved = true;
            toast.success('Success', {
                description: `${successes.length} customer(s) assigned successfully!`,
            });
        }
        failures.forEach((f) => {
            toast.warning('Skipped', { description: f.message });
        });

        formRef.value?.closeDialog();
    } catch (error) {
        const errors = error.response?.data?.errors;
        const message = error.response?.data?.message;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to assign customer account.', { description: errors[firstErrorKey][0] });
        } else if (message) {
            toast.warning('Failed to assign customer account.', { description: message });
        } else {
            toast.error('Failed to assign customer account.');
        }
        formRef.value?.closeDialog();
    }

    if (saved) {
        emit('customer-account-created');
        emit('member-form-closed');
    }
};
</script>

<template>
    <div>
        <CustomerAccountForm ref="formRef" @handleSubmit="handleSubmit" @member-form-closed="handleClose"
            :card-title="'New Customer Account'" :transaction-type="'create'" />
    </div>
</template>
