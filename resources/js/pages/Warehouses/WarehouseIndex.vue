<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';


import CreateWarehouse from '@/pages/Warehouses/CreateWarehouse.vue';
import UpdateWarehouse from '@/pages/Warehouses/UpdateWarehouse.vue';
import DeleteWarehouse from '@/pages/Warehouses/DeleteWarehouse.vue';


const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Warehouse List',
        href: '/warehouses',
    },
];



const props = defineProps({
    warehouses: {
        // type: Array,
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

const showCreateWarehouseModal = ref(false);

const showUpdateWarehouseModal = ref(false);
const showDeleteWarehouseModal = ref(false);
const selectedWarehouse = ref(null);


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        warehouseData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateWarehouseModal.value = true;
            selectedWarehouse.value = data;

            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteWarehouseModal.value = true;
            selectedWarehouse.value = data;


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};




</script>
<template>

    <Head title="Warehouses" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive warehouses data -->
            <BaseIndex IndexType="Warehouses" :data="props.warehouses"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'warehousename', label: 'Warehouse Name' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateWarehouseModal = true">
                    New Warehouse
                </Button>

            </BaseIndex>

            <CreateWarehouse v-if="showCreateWarehouseModal" @form-closed="showCreateWarehouseModal = false" />

            <UpdateWarehouse v-if="showUpdateWarehouseModal" :warehouse="selectedWarehouse"
                @warehouse-form-closed="showUpdateWarehouseModal = false" />

            <DeleteWarehouse v-if="showDeleteWarehouseModal" :warehouse="selectedWarehouse"
                @warehouse-form-closed="showDeleteWarehouseModal = false" />


        </div>
    </AppLayout>
</template>
