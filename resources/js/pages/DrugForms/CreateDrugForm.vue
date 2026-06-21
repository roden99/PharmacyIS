<script setup>
import DrugFormForm from './DrugFormForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const emit = defineEmits(['form-closed', 'drugform-created']);

const handleClose = () => {
    emit('form-closed');
};

const isProcessing = ref(false);
const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        const res = await axios.post('/drugforms', formData);
        toast.success('Success', { description: 'Drug form created successfully!' });
        emit('drugform-created', res.data.drugform);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create drug form.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to create drug form.');
        }
    } finally {
        isProcessing.value = false;
    }
};

</script>
<template>


    <div>
        <DrugFormForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Drug Form'" :transaction-type="'create'" />
    </div>

</template>
