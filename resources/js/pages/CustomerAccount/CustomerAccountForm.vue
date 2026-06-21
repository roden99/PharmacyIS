<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import BaseField from '@/components/BaseField.vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import { Badge } from '@/components/ui/badge';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { X } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    cardTitle: {
        type: String,
        default: 'Form',
    },
    transactionType: {
        type: String,
        default: 'create',
    },
});

const emit = defineEmits(['handleSubmit', 'member-form-closed']);

const isSaving = ref(false);
const isLoading = ref(true);
const isBusy = computed(() => isSaving.value);
const isDialogOpen = ref(false);

// Single combobox + selected badges
const selectedCustomer = ref(null);
const customerOptions = ref([]);
const selectedCustomers = ref([]); // [{ value, label }]

async function loadCustomers(searchQuery = '') {
    try {
        const res = await axios.get('/customers', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        customerOptions.value = res.data.customers.map((c) => ({
            value: String(c.id),
            label: c.display_name,
        }));
    } catch {
        toast.error('Failed to load customers.');
    }
}

function onCustomerSelect(value) {
    if (!value) return;
    if (selectedCustomers.value.some((c) => c.value === value)) {
        selectedCustomer.value = null;
        return;
    }
    const option = customerOptions.value.find((o) => o.value === value);
    if (option) {
        selectedCustomers.value.push({ value: option.value, label: option.label });
    }
    selectedCustomer.value = null;
}

function removeCustomer(value) {
    selectedCustomers.value = selectedCustomers.value.filter((c) => c.value !== value);
}

// Sales Account combobox
const selectedSalesAccount = ref(null);
const salesAccountOptions = ref([]);

async function loadSalesAccounts(searchQuery = '') {
    try {
        const res = await axios.get('/sales-accounts', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        salesAccountOptions.value = res.data.salesAccounts.map((a) => ({
            value: String(a.id),
            label: a.account_name,
        }));
    } catch {
        toast.error('Failed to load sales accounts.');
    }
}

const isFormValidated = () => {
    if (!selectedSalesAccount.value) {
        toast.error('Please select a sales account.');
        return false;
    }
    if (selectedCustomers.value.length === 0) {
        toast.error('Please select at least one customer.');
        return false;
    }
    return true;
};

const openConfirmDialog = () => {
    if (!isFormValidated()) return;
    isDialogOpen.value = true;
};

const handleAlertClose = () => {
    isDialogOpen.value = false;
};

const handleSubmit = () => {
    try {
        isSaving.value = true;
        emit('handleSubmit', {
            customer_ids: selectedCustomers.value.map((c) => Number(c.value)),
            sales_account_id: Number(selectedSalesAccount.value),
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
        isSaving.value = false;
    }
};

const closeDialog = () => {
    isDialogOpen.value = false;
    isSaving.value = false;
};

defineExpose({ closeDialog });

onMounted(async () => {
    await Promise.all([loadCustomers(), loadSalesAccounts()]);
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="isBusy" size="md">
        <form @submit.prevent class="space-y-4 mt-4">
            <BaseField legend="Customer Account" description="Assign customers to a sales account">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-rows="2" :skeleton-cols="1">
                        <div class="grid w-full grid-cols-12 gap-4">

                            <Field class="col-span-12 mb-2">
                                <Skeleton v-if="isLoading" class="h-4 w-24 mb-1" />
                                <FieldLabel v-else class="font-normal">Account:</FieldLabel>
                                <BaseCombobox v-model="selectedSalesAccount" :options="salesAccountOptions"
                                    empty-message="No accounts found" width="w-full" @search="loadSalesAccounts"
                                    :skeleton="isLoading" placeholder="Select Account"
                                    search-placeholder="Search account..." />
                            </Field>

                            <Field class="col-span-12">
                                <Skeleton v-if="isLoading" class="h-4 w-28 mb-1" />
                                <FieldLabel v-else class="font-normal">Customers:</FieldLabel>
                                <BaseCombobox v-model="selectedCustomer" :options="customerOptions"
                                    empty-message="No customers found" width="w-full" @search="loadCustomers"
                                    @update:modelValue="onCustomerSelect" :skeleton="isLoading"
                                    placeholder="Select Customer" search-placeholder="Search customer..." />

                                <div v-if="selectedCustomers.length > 0" class="flex flex-wrap gap-2 mt-2">
                                    <Badge v-for="customer in selectedCustomers" :key="customer.value"
                                        variant="secondary" class="flex items-center gap-1 px-2 py-1 text-sm">
                                        {{ customer.label }}
                                        <button type="button" @click="removeCustomer(customer.value)"
                                            class="ml-1 hover:text-destructive">
                                            <X class="w-3 h-3" />
                                        </button>
                                    </Badge>
                                </div>
                            </Field>

                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>

        <template #footer>
            <BaseButton :skeleton="isLoading" type="button" :disabled="isBusy" @click="emit('member-form-closed')"
                transactionType="cancel" />
            <BaseButton type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isBusy" :disabled="isBusy" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :disabled="isBusy" :loading="isBusy"
            :transaction-type="props.transactionType" @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
