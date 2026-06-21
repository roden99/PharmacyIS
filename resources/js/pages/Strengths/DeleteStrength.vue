<script setup>
import StrengthForm from './StrengthForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'



const props = defineProps({
    strength: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['strength-form-closed']);

const handleClose = () => {
    emit('strength-form-closed');
};


const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.delete(`/strengths/${props.strength.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Strength deactivated successfully!' });
            isProcessing.value = false;
            emit('strength-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to deactivate strength.', { description: errors[firstErrorKey] });
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
        <StrengthForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'Delete Strength'" :transaction-type="'delete'" :strength="strength" />
    </div>

</template>
