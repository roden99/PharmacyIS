<script setup>
import ProductUnitForm from './ProductUnitForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const emit = defineEmits(['form-closed', 'unit-created']);

const handleClose = () => {
    emit('form-closed');
};

const isProcessing = ref(false);
const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        const res = await axios.post('/product-units', formData);
        toast.success('Success', { description: 'Product Unit created successfully!' });
        emit('unit-created', res.data.productUnit);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create product unit.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to create product unit.');
        }
    } finally {
        isProcessing.value = false;
    }
};
</script>
<template>
    <div>
        <ProductUnitForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Product Unit'" :transaction-type="'create'" />
    </div>
</template>
