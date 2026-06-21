<script setup>
import SalesOrderForm from './SalesOrderForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/sales-orders', formData, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Sales order created successfully!' });
            isProcessing.value = false;
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to create sales order.', { description: errors[firstKey] });
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <SalesOrderForm @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" :is-processing="isProcessing"
            :card-title="'New Sales Order'" :transaction-type="'create'" />
    </div>
</template>
