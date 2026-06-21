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

const handleSubmit = () => {
    isProcessing.value = true;
    router.delete(`/sales-orders/${props.order.id}`, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Sales order deleted successfully!' });
            isProcessing.value = false;
            emit('item-form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to delete sales order.', { description: errors[firstKey] });
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
            :is-processing="isProcessing" :card-title="'Delete Sales Order'" :transaction-type="'delete'"
            :order="order" />
    </div>
</template>
