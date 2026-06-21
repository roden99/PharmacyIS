<script setup>
import BrandForm from './BrandForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const emit = defineEmits(['form-closed', 'brand-created']);

const handleClose = () => {
    emit('form-closed');
};

const isProcessing = ref(false);
const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        const res = await axios.post('/brands', formData);
        toast.success('Success', { description: 'Brand created successfully!' });
        emit('brand-created', res.data.brand);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create brand.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to create brand.');
        }
    } finally {
        isProcessing.value = false;
    }
};

</script>
<template>


    <div>
        <BrandForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Brand'" :transaction-type="'create'" />
    </div>

</template>
