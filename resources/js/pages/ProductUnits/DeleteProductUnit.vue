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
    router.delete(`/product-units/${props.productUnit.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Product Unit deactivated successfully!' });
            isProcessing.value = false;
            emit('product-unit-form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to deactivate product unit.', { description: errors[firstErrorKey] });
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
            :card-title="'Delete Product Unit'" :transaction-type="'delete'" :product-unit="productUnit" />
    </div>
</template>
