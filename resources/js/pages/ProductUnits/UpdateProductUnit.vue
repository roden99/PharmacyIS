<script setup>
import ProductUnitForm from './ProductUnitForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    productUnit: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['product-unit-form-closed']);

const handleClose = () => {
    emit('product-unit-form-closed');
};

const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/product-units/${props.productUnit.id}`, formData, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Product Unit updated successfully!' });
            isProcessing.value = false;
            emit('product-unit-form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update product unit.', { description: errors[firstErrorKey] });
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
        <ProductUnitForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'Update Product Unit'" :transaction-type="'update'" :product-unit="productUnit" />
    </div>
</template>
