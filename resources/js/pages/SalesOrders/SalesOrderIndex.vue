<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreateSalesOrder from '@/pages/SalesOrders/CreateSalesOrder.vue';
import UpdateSalesOrder from '@/pages/SalesOrders/UpdateSalesOrder.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Sales Orders', href: '/sales-orders' },
];

const props = defineProps({
    orders: {
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(
    selectOptions.length > 0 ? selectOptions[0].value : ''
);

const showCreateModal = ref(false);
const showUpdateModal = ref(false);
const selectedOrder = ref(null);

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'edit':
            selectedOrder.value = data;
            showUpdateModal.value = true;
            break;
        default:
            break;
    }
};
</script>

<template>

    <Head title="Sales Orders" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Sales Orders" :data="props.orders"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'account_name', label: 'Account' },
                    { field: 'customer_name', label: 'Customer' },
                ]">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Sales Order
                </Button>

            </BaseIndex>

            <CreateSalesOrder v-if="showCreateModal" @form-closed="showCreateModal = false" />

            <UpdateSalesOrder v-if="showUpdateModal" :order="selectedOrder"
                @item-form-closed="showUpdateModal = false" />
        </div>
    </AppLayout>
</template>
