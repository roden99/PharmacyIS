<script setup>
import ProductForm from './ProductForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'



const props = defineProps({
    product: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['product-form-closed']);

const handleClose = () => {
    emit('product-form-closed');
};


const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.delete(`/products/${props.product.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Product deleted successfully!' });
            isProcessing.value = false;
            emit('product-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to delete product.', { description: errors[firstErrorKey] });
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
        <ProductForm @save="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'Delete Product'" :transaction-type="'delete'" :product="product" />
    </div>

</template>
