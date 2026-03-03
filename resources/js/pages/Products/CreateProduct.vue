<script setup>
import ProductForm from './ProductForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    brands: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['form-closed']);

const handleClose = () => {
    emit('form-closed');
};



const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/products', formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Product created successfully!' });
            isProcessing.value = false;
            emit('form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create product.', { description: errors[firstErrorKey] });
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
        <ProductForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Product'" :transaction-type="'create'" :brands="brands" />
    </div>

</template>
