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

const emit = defineEmits(['member-form-closed', 'customer-updated']);

const formRef = ref(null);

const handleClose = () => {
    emit('member-form-closed');
};

const handleSubmit = (formData) => {
    router.put(`/customers/${props.customer.id}`, formData, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Customer updated successfully!' });
            emit('member-form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update customer.', { description: errors[firstErrorKey] });
            emit('member-form-closed');
        },
    });
};
</script>

<template>
    <div>
        <CustomerForm ref="formRef" @handleSubmit="handleSubmit" @member-form-closed="handleClose"
            :card-title="'Update Customer'" :transaction-type="'update'" :customer="customer" />
    </div>
</template>
