<script setup>
import BrandForm from './BrandForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'



const props = defineProps({
    brand: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['brand-form-closed']);

const handleClose = () => {
    emit('brand-form-closed');
};


const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.delete(`/brands/${props.brand.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Brand deleted successfully!' });
            isProcessing.value = false;
            emit('brand-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to delete brand.', { description: errors[firstErrorKey] });
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
            :card-title="'Delete Brand'" :transaction-type="'delete'" :brand="brand" />
    </div>

</template>
