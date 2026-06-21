<script setup>
import StrengthForm from './StrengthForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const emit = defineEmits(['form-closed', 'strength-created']);

const handleClose = () => {
    emit('form-closed');
};

const isProcessing = ref(false);
const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        const res = await axios.post('/strengths', formData);
        toast.success('Success', { description: 'Strength created successfully!' });
        emit('strength-created', res.data.strength);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create strength.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to create strength.');
        }
    } finally {
        isProcessing.value = false;
    }
};

</script>
<template>


    <div>
        <StrengthForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Strength'" :transaction-type="'create'" />
    </div>

</template>
