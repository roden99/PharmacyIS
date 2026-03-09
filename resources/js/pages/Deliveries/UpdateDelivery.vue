<script setup>
import DeliveryForm from './DeliveryForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    delivery: {
        type: Object,
        required: true,
    },
    suppliers: {
        type: Array,
        default: () => []
    },
});

const emit = defineEmits(['item-form-closed']);

const handleClose = () => {
    emit('item-form-closed');
};

const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/deliveries/${props.delivery.id}`, formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Delivery updated successfully!' });
            isProcessing.value = false;
            emit('item-form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update delivery.', { description: errors[firstErrorKey] });
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
        <DeliveryForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'Update Delivery'" :transaction-type="'update'" :delivery="delivery" :suppliers="suppliers" />
    </div>
</template>
