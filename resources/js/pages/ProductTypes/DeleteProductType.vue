<script setup>
import ProductTypeForm from './ProductTypeForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
    productType: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['product-type-form-closed']);

const handleClose = () => {
    emit('product-type-form-closed');
};

const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.delete(`/product-types/${props.productType.id}`, {
        preserveScroll: "errors",
        preserveState: "errors",
        onSuccess: () => {
            toast.success('Success', { description: 'Product Type deactivated successfully!' });
            isProcessing.value = false;
            emit('product-type-form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to deactivate product type.', { description: errors[firstErrorKey] });
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
        <ProductTypeForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'Delete Product Type'" :transaction-type="'delete'" :product-type="productType" />
    </div>
</template>
