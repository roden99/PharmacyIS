<script setup>
import DeliveryForm from './DeliveryForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    delivery: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['item-form-closed']);

const handleClose = () => {
    emit('item-form-closed');
};

const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.delete(`/deliveries/${props.delivery.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Delivery deleted successfully!' });
            isProcessing.value = false;
            emit('item-form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to delete delivery.', { description: errors[firstErrorKey] });
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
            :card-title="'Delete Delivery'" :transaction-type="'delete'" :delivery="delivery" />
    </div>
</template>
