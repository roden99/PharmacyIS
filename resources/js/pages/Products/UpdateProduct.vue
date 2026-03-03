<script setup>
import ProductForm from './ProductForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    brands: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['product-form-closed']);

const handleClose = () => {
    emit('product-form-closed');
};


const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/products/${props.product.id}`, formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Product updated successfully!' });
            isProcessing.value = false;
            emit('product-form-closed'); // Close modal on success
        },
        onError: (errors) => {

            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update product.', { description: errors[firstErrorKey] });
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
            :card-title="'Update Product'" :transaction-type="'update'" :product="product" :brands="brands" />
    </div>

</template>
