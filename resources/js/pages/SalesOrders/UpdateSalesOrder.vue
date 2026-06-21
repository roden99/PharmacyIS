<script setup>
import SalesOrderForm from './SalesOrderForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['item-form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/sales-orders/${props.order.id}`, formData, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Sales order updated successfully!' });
            isProcessing.value = false;
            emit('item-form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to update sales order.', { description: errors[firstKey] });
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
        <SalesOrderForm @handleSubmit="handleSubmit" @form-closed="emit('item-form-closed')"
            :is-processing="isProcessing" :card-title="'Update Sales Order'" :transaction-type="'update'"
            :order="order" />
    </div>
</template>
