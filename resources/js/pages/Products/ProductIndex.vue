<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch, h } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';
import { Pill, Tag, AlertTriangle } from 'lucide-vue-next';


import CreateProduct from '@/pages/Products/CreateProduct.vue';
import UpdateProduct from '@/pages/Products/UpdateProduct.vue';
import DeleteProduct from '@/pages/Products/DeleteProduct.vue';
import InitialProduct from '@/pages/Products/InitialProduct.vue';
import ReorderLevel from '@/pages/Products/ReorderLevel.vue';
import ProductHistory from '@/pages/Products/ProductHistory.vue';


const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Product List',
        href: '/products',
    },
];



const props = defineProps({
    products: {
        // type: Array,
        required: true,
    },

    columns: {
        type: Array,
        required: true,
    },

    brands: {
        type: Array,
        default: () => []
    },

    productUnits: {
        type: Array,
        default: () => []
    },

    strengths: {
        type: Array,
        default: () => []
    },

    drugforms: {
        type: Array,
        default: () => []
    },

});


const selectOptions = props.columns.filter(col => col.isParameter === true).map((s) => ({
    value: s.accessorKey,
    label: s.header,
}))
const selectModelValue = ref(
    selectOptions.length > 0 ? selectOptions[0].value : ''
);

const transformedColumns = computed(() =>
    props.columns
        .filter(col => col.isVisible === true)
        .map(col => {
            if (col.accessorKey === 'product_qty') {
                return {
                    ...col,
                    cell: ({ row }) => row.original.is_inventory ? (row.original.product_qty ?? 0) : '-',
                };
            }
            return col;
        })
);

const showCreateProductModal = ref(false);

const showUpdateProductModal = ref(false);
const showDeleteProductModal = ref(false);
const showInitialProductModal = ref(false);
const showReorderLevelModal = ref(false);
const showHistoryModal = ref(false);
const selectedProduct = ref(null);

const currentType = ref(new URLSearchParams(window.location.search).get('type') || 'all');

const showReorderOnly = ref(false);

const filteredProducts = computed(() => {
    if (!showReorderOnly.value) return props.products;
    const filtered = props.products.data.filter(row =>
        row.is_inventory &&
        row.product_qty !== '-' &&
        row.reorder_level !== '-' &&
        Number(row.product_qty) < Number(row.reorder_level)
    );
    return { ...props.products, data: filtered };
});

const handleTypeFilter = (type) => {
    currentType.value = type;
    const currentUrl = new URL(window.location.href);
    if (type === 'all') {
        currentUrl.searchParams.delete('type');
    } else {
        currentUrl.searchParams.set('type', type);
    }
    currentUrl.searchParams.delete('page');
    router.get(currentUrl.pathname + currentUrl.search, {}, { preserveState: true });
};


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        productData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateProductModal.value = true;
            selectedProduct.value = data;

            break;

        case 'initial':
            showInitialProductModal.value = true;
            selectedProduct.value = data;
            break;

        case 'reorder':
            showReorderLevelModal.value = true;
            selectedProduct.value = data;
            break;

        case 'history':
            showHistoryModal.value = true;
            selectedProduct.value = data;
            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteProductModal.value = true;
            selectedProduct.value = data;


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};




</script>


<template>

    <Head title="Products" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive products data -->
            <BaseIndex IndexType="Products" :data="filteredProducts" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" :row-class="(row) => {
                    if (!row.is_inventory) return 'bg-yellow-50 dark:bg-yellow-950';
                    if (row.product_qty !== '-' && row.reorder_level !== '-' && Number(row.product_qty) < Number(row.reorder_level)) return 'bg-red-200 dark:bg-red-900';
                    return '';
                }" v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'productname', label: 'Product Name' },
                    { field: 'brand_name', label: 'Brand' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateProductModal = true">
                    New Product
                </Button>

                <Button :variant="showReorderOnly ? 'destructive' : 'outline'" class="mr-2 gap-1"
                    @click="showReorderOnly = !showReorderOnly">
                    <AlertTriangle class="h-4 w-4" />
                    {{ showReorderOnly ? 'Showing: Low Stock' : 'Low Stock' }}
                </Button>

                <div class="flex items-center gap-1 ml-2 border rounded-md p-1">
                    <Button :variant="currentType === 'all' ? 'default' : 'ghost'" size="sm"
                        @click="handleTypeFilter('all')">
                        All
                    </Button>
                    <Button :variant="currentType === 'generic' ? 'default' : 'ghost'" size="sm" class="gap-1"
                        @click="handleTypeFilter('generic')">
                        <Pill class="h-4 w-4" /> Generic
                    </Button>
                    <Button :variant="currentType === 'branded' ? 'default' : 'ghost'" size="sm" class="gap-1"
                        @click="handleTypeFilter('branded')">
                        <Tag class="h-4 w-4" /> Branded
                    </Button>
                </div>

            </BaseIndex>

            <CreateProduct v-if="showCreateProductModal" @form-closed="showCreateProductModal = false" :brands="brands"
                :product-units="productUnits" :strengths="strengths" :drugforms="drugforms" />

            <UpdateProduct v-if="showUpdateProductModal" :product="selectedProduct" :brands="brands"
                :product-units="productUnits" :strengths="strengths" :drugforms="drugforms"
                @product-form-closed="showUpdateProductModal = false" />

            <DeleteProduct v-if="showDeleteProductModal" :product="selectedProduct"
                @product-form-closed="showDeleteProductModal = false" />

            <InitialProduct v-if="showInitialProductModal" :product="selectedProduct"
                @form-closed="showInitialProductModal = false" />

            <ReorderLevel v-if="showReorderLevelModal" :product="selectedProduct"
                @form-closed="showReorderLevelModal = false" />

            <ProductHistory v-if="showHistoryModal" :product="selectedProduct"
                @form-closed="showHistoryModal = false" />


        </div>
    </AppLayout>
</template>
