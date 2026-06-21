<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreateProductType from '@/pages/ProductTypes/CreateProductType.vue';
import UpdateProductType from '@/pages/ProductTypes/UpdateProductType.vue';
import DeleteProductType from '@/pages/ProductTypes/DeleteProductType.vue';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Product Type List',
        href: '/product-types',
    },
];

const props = defineProps({
    productTypes: {
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
});

const selectOptions = props.columns.filter(col => col.isParameter === true).map((s) => ({
    value: s.accessorKey,
    label: s.header,
}))
const selectModelValue = ref(
    selectOptions.length > 0 ? selectOptions[0].value : ''
);

const showCreateProductTypeModal = ref(false);
const showUpdateProductTypeModal = ref(false);
const showDeleteProductTypeModal = ref(false);
const selectedProductType = ref(null);

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'edit':
            showUpdateProductTypeModal.value = true;
            selectedProductType.value = data;
            break;

        case 'delete':
            showDeleteProductTypeModal.value = true;
            selectedProductType.value = data;
            break;

        default:
            console.log(`Unknown action "${type}" for:`, data);
    }
};
</script>

<template>

    <Head title="Product Types" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Product Types" :data="props.productTypes"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'type_name', label: 'Type Name' }
                ]">
                <Button variant="default" class="mr-2" @click="showCreateProductTypeModal = true">
                    New Product Type
                </Button>
            </BaseIndex>

            <CreateProductType v-if="showCreateProductTypeModal" @form-closed="showCreateProductTypeModal = false" />

            <UpdateProductType v-if="showUpdateProductTypeModal" :product-type="selectedProductType"
                @product-type-form-closed="showUpdateProductTypeModal = false" />

            <DeleteProductType v-if="showDeleteProductTypeModal" :product-type="selectedProductType"
                @product-type-form-closed="showDeleteProductTypeModal = false" />
        </div>
    </AppLayout>
</template>
