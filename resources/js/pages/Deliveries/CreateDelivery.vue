<script setup>
import DeliveryForm from './DeliveryForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    suppliers: {
        type: Array,
        default: () => []
    },
});

const emit = defineEmits(['form-closed']);

const handleClose = () => {
    emit('form-closed');
};

const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/deliveries', formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Delivery created successfully!' });
            isProcessing.value = false;
            emit('form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create delivery.', { description: errors[firstErrorKey] });
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
            :card-title="'New Delivery'" :transaction-type="'create'" :suppliers="suppliers" />
    </div>
</template>
