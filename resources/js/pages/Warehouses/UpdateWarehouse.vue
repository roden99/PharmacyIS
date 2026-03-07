<script setup>
import WarehouseForm from './WarehouseForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    warehouse: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['warehouse-form-closed']);

const handleClose = () => {
    emit('warehouse-form-closed');
};


const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/warehouses/${props.warehouse.id}`, formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Warehouse updated successfully!' });
            isProcessing.value = false;
            emit('warehouse-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update warehouse.', { description: errors[firstErrorKey] });
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
            :card-title="'Update Warehouse'" :transaction-type="'update'" :warehouse="warehouse" />
    </div>

</template>
