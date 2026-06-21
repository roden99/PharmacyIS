<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CustomerAccountCreate from './CustomerAccountCreate.vue';
import { ref, computed, h } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import { Hospital, User } from 'lucide-vue-next';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Customer Accounts',
        href: '/customer-accounts',
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
    accounts: {
        type: Array,
        default: () => [],
    },
});

const currentAccount = ref(new URLSearchParams(window.location.search).get('account') || 'all');
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
    router.get(currentUrl.pathname + currentUrl.search);
};

const handleAccountFilter = (value) => {
    currentAccount.value = value;
    const currentUrl = new URL(window.location.href);
    if (value === 'all') {
        currentUrl.searchParams.delete('account');
    } else {
        currentUrl.searchParams.set('account', value);
    }
    currentUrl.searchParams.delete('page');
    router.get(currentUrl.pathname + currentUrl.search);
};

const selectOptions = props.columns.filter(col => col.isParameter === true).map((s) => ({
    value: s.accessorKey,
    label: s.header,
}));

const selectModelValue = ref(
    selectOptions.length > 0 ? selectOptions[0].value : ''
);

const handleAction = ({ type, data }) => {
    // actions can be wired up here
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

const showCreateModal = ref(false);
</script>

<template>

    <Head title="Customer Accounts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

            <BaseIndex IndexType="CustomerAccounts" :data="props.customers"
                :columnDefs="enrichedColumns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'display_name', label: 'Customer' },
                    { field: 'account_name', label: 'Account' },
                    { field: 'phone', label: 'Phone' },
                    { field: 'address', label: 'Address' },
                ]">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Account
                </Button>

                <Select :model-value="currentAccount" @update:model-value="handleAccountFilter">
                    <SelectTrigger class="w-48">
                        <SelectValue placeholder="Filter by Account" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Accounts</SelectItem>
                        <SelectItem v-for="account in props.accounts" :key="account.value" :value="account.value">
                            {{ account.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

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

            <CustomerAccountCreate v-if="showCreateModal" @member-form-closed="showCreateModal = false"
                @customer-account-created="() => { showCreateModal = false; router.reload({ preserveScroll: true }); }" />

        </div>
    </AppLayout>

</template>
