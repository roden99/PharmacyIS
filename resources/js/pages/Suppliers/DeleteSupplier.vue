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
    router.delete(`/suppliers/${props.supplier.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Supplier deleted successfully!' });
            isProcessing.value = false;
            emit('member-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to delete supplier.', { description: errors[firstErrorKey] });
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
            :card-title="'Delete Supplier'" :transaction-type="'delete'" :supplier="supplier" />
    </div>

</template>
