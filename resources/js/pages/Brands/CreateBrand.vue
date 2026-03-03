<script setup>
import SupplierForm from '@/pages/Suppliers/SupplierForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import BrandForm from './BrandForm.vue'

const emit = defineEmits(['form-closed']);

const handleClose = () => {
    emit('form-closed');
};



const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/brands', formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Brand created successfully!' });
            isProcessing.value = false;
            emit('form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create brand.', { description: errors[firstErrorKey] });
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
        <BrandForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Brand'" :transaction-type="'create'" />
    </div>

</template>
