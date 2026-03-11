<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreateProductUnit from '@/pages/ProductUnits/CreateProductUnit.vue';
import UpdateProductUnit from '@/pages/ProductUnits/UpdateProductUnit.vue';
import DeleteProductUnit from '@/pages/ProductUnits/DeleteProductUnit.vue';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Product Unit List',
        href: '/product-units',
    },
];

const props = defineProps({
    productUnits: {
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

const showCreateProductUnitModal = ref(false);
const showUpdateProductUnitModal = ref(false);
const showDeleteProductUnitModal = ref(false);
const selectedProductUnit = ref(null);

const handleAction = ({ type, data }) => {
    console.log('🎯 Action Clicked:', {
        actionType: type,
        productUnitData: data,
        timestamp: new Date().toISOString(),
    });

    switch (type) {
        case 'edit':
            showUpdateProductUnitModal.value = true;
            selectedProductUnit.value = data;
            break;

        case 'delete':
            showDeleteProductUnitModal.value = true;
            selectedProductUnit.value = data;
            break;

        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }
};
</script>

<template>

    <Head title="Product Units" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Product Units" :data="props.productUnits"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'unit_name', label: 'Unit Name' },
                    { field: 'unit_code', label: 'Unit Code' }
                ]">
                <Button variant="default" class="mr-2" @click="showCreateProductUnitModal = true">
                    New Product Unit
                </Button>
            </BaseIndex>

            <CreateProductUnit v-if="showCreateProductUnitModal" @form-closed="showCreateProductUnitModal = false" />

            <UpdateProductUnit v-if="showUpdateProductUnitModal" :product-unit="selectedProductUnit"
                @product-unit-form-closed="showUpdateProductUnitModal = false" />

            <DeleteProductUnit v-if="showDeleteProductUnitModal" :product-unit="selectedProductUnit"
                @product-unit-form-closed="showDeleteProductUnitModal = false" />
        </div>
    </AppLayout>
</template>
