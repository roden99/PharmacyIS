<script setup>
import WarehouseItemForm from './WarehouseItemForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    warehouses: {
        type: Array,
        default: () => []
    },
    products: {
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
    router.post('/warehouse-items', formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Warehouse item created successfully!' });
            isProcessing.value = false;
            emit('form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create warehouse item.', { description: errors[firstErrorKey] });
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
        <WarehouseItemForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Warehouse Item'" :transaction-type="'create'" :warehouses="warehouses"
            :products="products" />
    </div>

</template>
