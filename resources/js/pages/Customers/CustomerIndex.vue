<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';
import CreateCustomer from '@/pages/Customers/CreateCustomer.vue';
import UpdateCustomer from '@/pages/Customers/UpdateCustomer.vue';
import DeleteCustomer from '@/pages/Customers/DeleteCustomer.vue';



const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Customer List',
        href: '/customers',
    },
];

const props = defineProps({
    customers: {
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

const showCreateCustomerModal = ref(false);
const showUpdateCustomerModal = ref(false);
const showDeleteCustomerModal = ref(false);
const selectedCustomer = ref(null);


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        customerData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateCustomerModal.value = true;
            selectedCustomer.value = data;

            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteCustomerModal.value = true;
            selectedCustomer.value = data;


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};

// Format customers with concatenated full name
const formattedCustomers = computed(() => {
    // Handle both array and paginated object (with .data property)
    const isArray = Array.isArray(props.customers);
    const customersList = isArray ? props.customers : props.customers?.data || [];

    const formatted = customersList.map(customer => ({
        ...customer,
        fullname: `${customer.first_name || ''} ${customer.middle_name || ''} ${customer.last_name || ''}`.trim()
    }));

    // If paginated, preserve pagination structure
    if (!isArray && props.customers?.data) {
        return {
            ...props.customers,
            data: formatted
        };
    }

    return formatted;
});




</script>
<template>

    <Head title="Customers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive customers data -->
            <BaseIndex IndexType="Customers" :data="formattedCustomers"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'fullname', label: 'Customer Name' },
                    { field: 'email', label: 'Email' },
                    { field: 'phone', label: 'Phone' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateCustomerModal = true">
                    New Customer
                </Button>

            </BaseIndex>

            <CreateCustomer v-if="showCreateCustomerModal" @member-form-closed="showCreateCustomerModal = false" />

            <UpdateCustomer v-if="showUpdateCustomerModal" :customer="selectedCustomer"
                @member-form-closed="showUpdateCustomerModal = false" />

            <DeleteCustomer v-if="showDeleteCustomerModal" :customer="selectedCustomer"
                @member-form-closed="showDeleteCustomerModal = false" />


        </div>
    </AppLayout>
</template>