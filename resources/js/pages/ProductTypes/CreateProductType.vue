<script setup>
import ProductTypeForm from './ProductTypeForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const emit = defineEmits(['form-closed', 'type-created']);

const handleClose = () => {
    emit('form-closed');
};

const isProcessing = ref(false);
const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        const res = await axios.post('/product-types', formData);
        toast.success('Success', { description: 'Product Type created successfully!' });
        emit('type-created', res.data.productType);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create product type.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to create product type.');
        }
    } finally {
        isProcessing.value = false;
    }
};
</script>
<template>
    <div>
        <ProductTypeForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Product Type'" :transaction-type="'create'" />
    </div>
</template>
