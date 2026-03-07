<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';


import CreateWarehouseItem from '@/pages/WarehouseItems/CreateWarehouseItem.vue';
import UpdateWarehouseItem from '@/pages/WarehouseItems/UpdateWarehouseItem.vue';
import DeleteWarehouseItem from '@/pages/WarehouseItems/DeleteWarehouseItem.vue';


const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Warehouse Items',
        href: '/warehouse-items',
    },
];



const props = defineProps({
    warehouseItems: {
        required: true,
    },

    columns: {
        type: Array,
        required: true,
    },

    warehouses: {
        type: Array,
        default: () => []
    },

    products: {
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

const showCreateWarehouseItemModal = ref(false);

const showUpdateWarehouseItemModal = ref(false);
const showDeleteWarehouseItemModal = ref(false);
const selectedWarehouseItem = ref(null);


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        warehouseItemData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateWarehouseItemModal.value = true;
            selectedWarehouseItem.value = data;

            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteWarehouseItemModal.value = true;
            selectedWarehouseItem.value = data;


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};




</script>


<template>

    <Head title="Warehouse Items" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive warehouse items data -->
            <BaseIndex IndexType="Warehouse Items" :data="props.warehouseItems"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'warehouse_name', label: 'Warehouse' },
                    { field: 'product_name', label: 'Product' },
                    { field: 'quantity', label: 'Quantity' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateWarehouseItemModal = true">
                    New Item
                </Button>

            </BaseIndex>

            <CreateWarehouseItem v-if="showCreateWarehouseItemModal" @form-closed="showCreateWarehouseItemModal = false"
                :warehouses="warehouses" :products="products" />

            <UpdateWarehouseItem v-if="showUpdateWarehouseItemModal" :warehouseItem="selectedWarehouseItem"
                :warehouses="warehouses" :products="products"
                @item-form-closed="showUpdateWarehouseItemModal = false" />

            <DeleteWarehouseItem v-if="showDeleteWarehouseItemModal" :warehouseItem="selectedWarehouseItem"
                @item-form-closed="showDeleteWarehouseItemModal = false" />


        </div>
    </AppLayout>
</template>
