<script setup>
import SupplierForm from '@/pages/Suppliers/SupplierForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const emit = defineEmits(['member-form-closed']);

const handleClose = () => {
    emit('member-form-closed');
};



const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/suppliers', formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Supplier created successfully!' });
            isProcessing.value = false;
            emit('member-form-closed'); // Close modal on success
        },
        onError: (errors) => {
            console.log('asdadasd')
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create supplier.', { description: errors[firstErrorKey] });
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
            :card-title="'New Supplier'" :transaction-type="'create'" />
    </div>

</template>
