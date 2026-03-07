<script setup>
import WarehouseItemForm from './WarehouseItemForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'



const props = defineProps({
    warehouseItem: {
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
    router.delete(`/warehouse-items/${props.warehouseItem.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Warehouse item deleted successfully!' });
            isProcessing.value = false;
            emit('item-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to delete warehouse item.', { description: errors[firstErrorKey] });
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
            :card-title="'Delete Warehouse Item'" :transaction-type="'delete'" :warehouseItem="warehouseItem" />
    </div>

</template>
