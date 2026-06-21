<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { h, onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';
import { Hospital, Pill, User } from 'lucide-vue-next';
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

const currentType = ref(new URLSearchParams(window.location.search).get('type') || 'all');

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

const enrichedColumns = computed(() =>
    props.columns.map(col => {
        if (col.accessorKey === 'is_drugstore') {
            return {
                ...col,
                cell: ({ row }) => {
                    const val = row.original.is_drugstore;
                    const isDrugstore = val === true || val === 'YES' || val === 1;
                    return h('div', { class: 'flex items-center justify-start' },
                        isDrugstore
                            ? h(Hospital, { class: 'h-4 w-4 text-green-600' })
                            : h(User, { class: 'h-4 w-4 text-blue-500' })
                    );
                },
            };
        }
        return col;
    })
);

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
                :columnDefs="enrichedColumns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'fullname', label: 'Customer Name' },
                    { field: 'email', label: 'Email' },
                    { field: 'phone', label: 'Phone' },
                    { field: 'address', label: 'Address' }
                ]">


                <Button variant="default" class="mr-2" @click="showCreateCustomerModal = true">
                    New Customers
                </Button>

                <div class="flex items-center gap-1 ml-2 border rounded-md p-1">
                    <Button :variant="currentType === 'all' ? 'default' : 'ghost'" size="sm"
                        @click="handleTypeFilter('all')">
                        All
                    </Button>
                    <Button :variant="currentType === 'drugstore' ? 'default' : 'ghost'" size="sm" class="gap-1"
                        @click="handleTypeFilter('drugstore')">
                        <Hospital class="h-4 w-4" /> Drugstore
                    </Button>
                    <Button :variant="currentType === 'person' ? 'default' : 'ghost'" size="sm" class="gap-1"
                        @click="handleTypeFilter('person')">
                        <User class="h-4 w-4" /> Doctor
                    </Button>
                </div>

            </BaseIndex>

            <CreateCustomer v-if="showCreateCustomerModal" @member-form-closed="showCreateCustomerModal = false"
                @customer-created="() => { showCreateCustomerModal = false; router.reload({ only: ['customers'] }); }" />

            <UpdateCustomer v-if="showUpdateCustomerModal" :customer="selectedCustomer"
                @member-form-closed="showUpdateCustomerModal = false"
                @customer-updated="() => { showUpdateCustomerModal = false; router.reload({ only: ['customers'] }); }" />

            <DeleteCustomer v-if="showDeleteCustomerModal" :customer="selectedCustomer"
                @member-form-closed="showDeleteCustomerModal = false"
                @customer-deleted="() => { showDeleteCustomerModal = false; router.reload({ only: ['customers'] }); }" />


        </div>
    </AppLayout>
</template>