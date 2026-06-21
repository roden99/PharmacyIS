<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';
import SalesAccountCreate from './SalesAccountCreate.vue';
import SalesAccountUpdate from './SalesAccountUpdate.vue';
import SalesAccountDelete from './SalesAccountDelete.vue';

import SalesAccountCustomer from '@/pages/SalesAccounts/SalesAccountCustomer.vue';



const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Sales Account List',
        href: '/sales-accounts',
    },
];



const props = defineProps({
    salesAccounts: {
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

const showCreateSalesAccountModal = ref(false);
const showSalesAccountCustomerModal = ref(false);
const showUpdateSalesAccountModal = ref(false);
const showDeleteSalesAccountModal = ref(false);
const selectedSalesAccount = ref(null);







const handleAction = ({ type, data }) => {

    switch (type) {
        case 'customers':

            showSalesAccountCustomerModal.value = true;
            selectedSalesAccount.value = data;
            break;


        case 'edit':

            showUpdateSalesAccountModal.value = true;
            selectedSalesAccount.value = data;
            break;

        case 'delete':
            showDeleteSalesAccountModal.value = true;
            selectedSalesAccount.value = data;
            break;
        default:

    }

};





</script>


<template>

    <Head title="Sales Accounts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive sales accounts data -->
            <BaseIndex IndexType="SalesAccounts" :data="props.salesAccounts"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'account_name', label: 'Account Name' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateSalesAccountModal = true">
                    New Account
                </Button>

            </BaseIndex>

            <SalesAccountCreate v-if="showCreateSalesAccountModal" @form-closed="showCreateSalesAccountModal = false"
                @sales-account-created="() => { showCreateSalesAccountModal = false; router.reload({ only: ['salesAccounts'] }); }" />

            <SalesAccountUpdate v-if="showUpdateSalesAccountModal" :sales-account="selectedSalesAccount"
                @form-closed="showUpdateSalesAccountModal = false"
                @sales-account-updated="() => { showUpdateSalesAccountModal = false; router.reload({ only: ['salesAccounts'] }); }" />

            <!-- <SalesAccountCustomer v-if="showSalesAccountCustomerModal" :sales-account="selectedSalesAccount"
                @form-closed="showSalesAccountCustomerModal = false" /> -->

            <SalesAccountDelete v-if="showDeleteSalesAccountModal" :sales-account="selectedSalesAccount"
                @form-closed="showDeleteSalesAccountModal = false"
                @sales-account-deleted="() => { showDeleteSalesAccountModal = false; router.reload({ only: ['salesAccounts'] }); }" />

        </div>
    </AppLayout>
</template>