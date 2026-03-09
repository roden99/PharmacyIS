<script setup>
import WarehouseItemForm from './WarehouseItemForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    warehouseItem: {
        type: Object,
        required: true,
    },
    warehouses: {
        type: Array,
        default: () => []
    },
    products: {
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
    router.put(`/warehouse-items/${props.warehouseItem.id}`, formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Warehouse item updated successfully!' });
            isProcessing.value = false;
            emit('item-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update warehouse item.', { description: errors[firstErrorKey] });
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
            :card-title="'Update Warehouse Item'" :transaction-type="'update'" :warehouseItem="warehouseItem"
            :warehouses="warehouses" :products="products" />
    </div>

</template>
