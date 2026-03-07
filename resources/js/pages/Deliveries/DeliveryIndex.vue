<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';

import CreateDelivery from '@/pages/Deliveries/CreateDelivery.vue';
import UpdateDelivery from '@/pages/Deliveries/UpdateDelivery.vue';
import DeleteDelivery from '@/pages/Deliveries/DeleteDelivery.vue';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Deliveries',
        href: '/deliveries',
    },
];

const props = defineProps({
    deliveries: {
        required: true,
    },

    columns: {
        type: Array,
        required: true,
    },

    suppliers: {
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

const showCreateDeliveryModal = ref(false);
const showUpdateDeliveryModal = ref(false);
const showDeleteDeliveryModal = ref(false);
const selectedDelivery = ref(null);

const handleAction = ({ type, data }) => {
    console.log('🎯 Action Clicked:', {
        actionType: type,
        deliveryData: data,
        timestamp: new Date().toISOString(),
    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateDeliveryModal.value = true;
            selectedDelivery.value = data;
            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;

        case 'delete':
            showDeleteDeliveryModal.value = true;
            selectedDelivery.value = data;
            break;

        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }
};

</script>

<template>

    <Head title="Deliveries" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Deliveries" :data="props.deliveries"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'supplier_name', label: 'Supplier' },
                    { field: 'delivery_date', label: 'Delivery Date' },
                    { field: 'status', label: 'Status' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateDeliveryModal = true">
                    New Delivery
                </Button>

            </BaseIndex>

            <CreateDelivery v-if="showCreateDeliveryModal" @form-closed="showCreateDeliveryModal = false"
                :suppliers="suppliers" />

            <UpdateDelivery v-if="showUpdateDeliveryModal" :delivery="selectedDelivery" :suppliers="suppliers"
                @item-form-closed="showUpdateDeliveryModal = false" />

            <DeleteDelivery v-if="showDeleteDeliveryModal" :delivery="selectedDelivery"
                @item-form-closed="showDeleteDeliveryModal = false" />

        </div>
    </AppLayout>
</template>
