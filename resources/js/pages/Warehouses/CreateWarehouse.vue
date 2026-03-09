<script setup>
import WarehouseForm from './WarehouseForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const emit = defineEmits(['form-closed']);

const handleClose = () => {
    emit('form-closed');
};



const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/warehouses', formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Warehouse created successfully!' });
            isProcessing.value = false;
            emit('form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create warehouse.', { description: errors[firstErrorKey] });
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
        <WarehouseForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Warehouse'" :transaction-type="'create'" />
    </div>

</template>
