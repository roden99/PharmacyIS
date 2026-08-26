<script setup>
import EditReturnToSupplierForm from './EditReturnToSupplierForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    record: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        await axios.put(`/return-to-suppliers/${props.record.id}`, formData, {
            headers: { Accept: 'application/json' },
        });
        toast.success('Return to supplier updated successfully.');
        router.reload({ only: ['records'] });
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        const first = errors ? Object.values(errors)[0][0] : 'Failed to update return.';
        toast.error(first);
        isProcessing.value = false;
    }
};
</script>

<template>
    <EditReturnToSupplierForm :record="record" @handleSubmit="handleSubmit" @form-closed="emit('form-closed')"
        :is-processing="isProcessing" />
</template>
