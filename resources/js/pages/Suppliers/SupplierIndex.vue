<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';
import CreateSupplier from '@/pages/Suppliers/CreateSupplier.vue';

import UpdateSupplier from '@/pages/Suppliers/UpdateSupplier.vue';
import DeleteSupplier from '@/pages/Suppliers/DeleteSupplier.vue';
// import Delete from '@/pages/Members/Delete.vue';



const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Supplier List',
        href: '/suppliers',
    },
];

const props = defineProps({
    suppliers: {
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

// const showCreateSupplierModal = ref(false);
const showUpdateSupplierModal = ref(false);
const showDeleteSupplierModal = ref(false);
const selectedSupplier = ref(null);


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        supplierData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateSupplierModal.value = true;
            selectedSupplier.value = data;

            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteSupplierModal.value = true;
            selectedSupplier.value = data;


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};

const showCreateSupplierModal = ref(false);

// Format suppliers with concatenated representative name
const formattedSuppliers = computed(() => {
    // Handle both array and paginated object (with .data property)
    const suppliersList = Array.isArray(props.suppliers) ? props.suppliers : props.suppliers?.data || [];

    return suppliersList.map(supplier => ({
        ...supplier,
        representative: `${supplier.firstname || ''} ${supplier.middlename || ''} ${supplier.lastname || ''}`.trim()
    }));
});




</script>
<template>

    <Head title="Members" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive suppliers data -->
            <BaseIndex IndexType="Suppliers" :data="formattedSuppliers"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'company', label: 'Company' },
                    { field: 'representative', label: 'Representative' },
                    { field: 'contact_phone', label: 'Contact Phone' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateSupplierModal = true">
                    New Supplier
                </Button>

            </BaseIndex>

            <CreateSupplier v-if="showCreateSupplierModal" @member-form-closed="showCreateSupplierModal = false" />



            <UpdateSupplier v-if="showUpdateSupplierModal" :supplier="selectedSupplier"
                @member-form-closed="showUpdateSupplierModal = false" />

            <DeleteSupplier v-if="showDeleteSupplierModal" :supplier="selectedSupplier"
                @member-form-closed="showDeleteSupplierModal = false" />


        </div>
    </AppLayout>
</template>