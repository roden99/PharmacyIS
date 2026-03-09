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
    router.delete(`/warehouses/${props.warehouse.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Warehouse deleted successfully!' });
            isProcessing.value = false;
            emit('warehouse-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to delete warehouse.', { description: errors[firstErrorKey] });
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
            :card-title="'Delete Warehouse'" :transaction-type="'delete'" :warehouse="warehouse" />
    </div>

</template>
