<script setup>
import SupplierForm from '@/pages/Suppliers/SupplierForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    supplier: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['member-form-closed']);

const handleClose = () => {
    emit('member-form-closed');
};


const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/suppliers/${props.supplier.id}`, formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Supplier updated successfully!' });
            isProcessing.value = false;
            emit('member-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update supplier.', { description: errors[firstErrorKey] });
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
        <SupplierForm @saveSupplier="handleSubmit" @member-form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'Update Supplier'" :transaction-type="'update'" :supplier="supplier" />
    </div>

</template>
