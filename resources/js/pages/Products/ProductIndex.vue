<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';


import CreateProduct from '@/pages/Products/CreateProduct.vue';
import UpdateProduct from '@/pages/Products/UpdateProduct.vue';
import DeleteProduct from '@/pages/Products/DeleteProduct.vue';


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

});


const selectOptions = props.columns.filter(col => col.isParameter === true).map((s) => ({
    value: s.accessorKey,
    label: s.header,
}))
const selectModelValue = ref(
    selectOptions.length > 0 ? selectOptions[0].value : ''
);

const showCreateProductModal = ref(false);

const showUpdateProductModal = ref(false);
const showDeleteProductModal = ref(false);
const selectedProduct = ref(null);


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
            <BaseIndex IndexType="Products" :data="props.products"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'productname', label: 'Product Name' },
                    { field: 'brand_name', label: 'Brand' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateProductModal = true">
                    New Product
                </Button>

            </BaseIndex>

            <CreateProduct v-if="showCreateProductModal" @form-closed="showCreateProductModal = false"
                :brands="brands" />



            <UpdateProduct v-if="showUpdateProductModal" :product="selectedProduct" :brands="brands"
                @product-form-closed="showUpdateProductModal = false" />

            <DeleteProduct v-if="showDeleteProductModal" :product="selectedProduct"
                @product-form-closed="showDeleteProductModal = false" />


        </div>
    </AppLayout>
</template>
