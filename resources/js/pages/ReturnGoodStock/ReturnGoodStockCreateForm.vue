<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import BaseField from '@/components/BaseField.vue';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table';
import { X, Tag } from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import { useDateFormatter } from '@/composables/useDateFormatter';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const { skeletonLayout } = useFieldGroupSkeleton([6, 12, 12, 12]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const isLoading = ref(false);
const isDialogOpen = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

// Header fields
const customerOptions = ref([]);
const selectedCustomer = ref(null);
const accountOptions = ref([]);
const selectedAccount = ref(null);
const invoiceNo = ref('');
const rgsDate = ref(null);
const notes = ref('');

// Item row fields
const productOptions = ref([]);
const selectedProduct = ref(null);
const lotOptions = ref([]);
const selectedLot = ref(null);
const itemQty = ref(1);
const itemUnitPrice = ref(0);

// Line items list
const returnItems = ref([]);

async function loadCustomers(search = '') {
    try {
        const res = await axios.get('/customers', {
            headers: { Accept: 'application/json' },
            params: { search },
        });
        customerOptions.value = res.data.customers.map(c => ({
            value: String(c.id),
            label: c.display_name,
        }));
    } catch {
        toast.error('Failed to load customers.');
    }
}

async function loadAccountsForCustomer(customerId) {
    if (!customerId) { accountOptions.value = []; selectedAccount.value = null; return; }
    try {
        const res = await axios.get(`/customer-accounts/by-customer/${customerId}`, {
            headers: { Accept: 'application/json' },
        });
        accountOptions.value = res.data.accounts;
        selectedAccount.value = accountOptions.value.length === 1 ? accountOptions.value[0].value : null;
    } catch {
        toast.error('Failed to load accounts.');
    }
}

watch(selectedCustomer, (id) => loadAccountsForCustomer(id));

async function loadProducts(search = '') {
    try {
        const res = await axios.get('/products', {
            headers: { Accept: 'application/json' },
            params: { search },
        });
        productOptions.value = res.data.products.map(p => ({
            value: String(p.id),
            label: p.display_name,
        }));
    } catch {
        toast.error('Failed to load products.');
    }
}

watch(selectedProduct, async (id) => {
    selectedLot.value = null;
    lotOptions.value = [];
    if (!id) return;
    try {
        const res = await axios.get(`/products/${id}/lots`, {
            headers: { Accept: 'application/json' },
        });
        lotOptions.value = (res.data.lots ?? []).map(l => ({
            value: String(l.id),
            label: `${l.lot_number} (exp: ${l.expiration_date})`,
            lot_number: l.lot_number,
            expiration_date: l.expiration_date,
        }));
    } catch {
        // no lots available
    }
});

const addItem = () => {
    if (!selectedProduct.value) {
        toast.error('Please select a product.');
        return;
    }
    if (Number(itemQty.value) < 1) {
        toast.error('Quantity must be at least 1.');
        return;
    }

    const product = productOptions.value.find(p => p.value === selectedProduct.value);
    const lot = lotOptions.value.find(l => l.value === selectedLot.value);

    returnItems.value.push({
        product_id: selectedProduct.value,
        product_name: product?.label ?? `Product #${selectedProduct.value}`,
        lot_id: selectedLot.value ?? null,
        lot_number: lot?.lot_number ?? null,
        expiration_date: lot?.expiration_date ?? null,
        quantity: Number(itemQty.value),
        unit_price: Number(itemUnitPrice.value) || 0,
    });

    selectedProduct.value = null;
    selectedLot.value = null;
    lotOptions.value = [];
    itemQty.value = 1;
    itemUnitPrice.value = 0;
};

const removeItem = (index) => {
    returnItems.value.splice(index, 1);
};

const openConfirmDialog = () => {
    if (!selectedCustomer.value) {
        toast.error('Please select a customer.');
        return;
    }
    if (!rgsDate.value) {
        toast.error('Please select an RGS date.');
        return;
    }
    if (returnItems.value.length === 0) {
        toast.error('Please add at least one item.');
        return;
    }
    isDialogOpen.value = true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) isDialogOpen.value = false;
});

const handleSubmit = () => {
    emit('handleSubmit', {
        customer_id: Number(selectedCustomer.value),
        customer_sales_account_id: selectedAccount.value ? Number(selectedAccount.value) : null,
        invoice_no: invoiceNo.value.trim() || null,
        rgs_date: normalizeDate(rgsDate.value),
        notes: notes.value || null,
        items: returnItems.value.map(i => ({
            product_id: Number(i.product_id),
            lot_id: i.lot_id ? Number(i.lot_id) : null,
            quantity: i.quantity,
            unit_price: i.unit_price,
        })),
    });
};

onMounted(async () => {
    isLoading.value = true;
    await Promise.all([loadCustomers(), loadProducts()]);
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="false" size="4xl" cardTitle="New Return Good Stock">
        <form @submit.prevent class="space-y-4 mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: RGS Info -->
                <div class="col-span-4">
                    <BaseField legend="RGS Information" description="Enter return details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">
                                    <Field class="col-span-12">
                                        <FieldLabel>Customer <span class="text-destructive">*</span></FieldLabel>
                                        <BaseCombobox v-model="selectedCustomer" :options="customerOptions"
                                            empty-message="No customers found" width="w-full" @search="loadCustomers"
                                            placeholder="Search customer..." :disabled="isBusy" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>Account</FieldLabel>
                                        <BaseCombobox v-model="selectedAccount" :options="accountOptions"
                                            empty-message="No accounts found" width="w-full"
                                            placeholder="Select account..." :disabled="isBusy || !selectedCustomer" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>Invoice No.</FieldLabel>
                                        <Input v-model="invoiceNo" placeholder="e.g. INV-001" :disabled="isBusy" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>RGS Date <span class="text-destructive">*</span></FieldLabel>
                                        <BaseDatePick v-model="rgsDate" :disabled="isBusy" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>Notes</FieldLabel>
                                        <Textarea v-model="notes" placeholder="Optional notes..." rows="4"
                                            :disabled="isBusy" />
                                    </Field>
                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Add Items -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Return Items" description="Add products with lot and expiration details"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">

                                <!-- Add item row -->
                                <div class="grid w-full grid-cols-12 gap-3">
                                    <Field class="col-span-5">
                                        <FieldLabel class="font-normal">Product:</FieldLabel>
                                        <BaseCombobox v-model="selectedProduct" :options="productOptions"
                                            empty-message="No products found" width="w-full" @search="loadProducts"
                                            placeholder="Search product..." />
                                    </Field>
                                    <Field class="col-span-4">
                                        <FieldLabel class="font-normal">Lot Number:</FieldLabel>
                                        <BaseCombobox v-model="selectedLot" :options="lotOptions"
                                            empty-message="No lots found" width="w-full" placeholder="Select lot..."
                                            :disabled="isBusy || !selectedProduct" />
                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Qty:</FieldLabel>
                                        <Input v-model.number="itemQty" type="number" min="1" step="1" placeholder="1"
                                            :disabled="isBusy" />
                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Unit Price:</FieldLabel>
                                        <Input v-model.number="itemUnitPrice" type="number" min="0" step="0.01"
                                            placeholder="0.00" :disabled="isBusy" />
                                    </Field>
                                    <Field class="col-span-1 flex flex-col">
                                        <FieldLabel class="invisible">-</FieldLabel>
                                        <BaseButton type="button" @click="addItem" transactionType="add"
                                            :disabled="isBusy" :skeleton="isLoading" />
                                    </Field>

                                </div>

                                <!-- Items table -->
                                <div class="overflow-y-auto rounded-md border flex-1 min-h-0 mt-2 max-h-72">
                                    <Table class="text-xs">
                                        <TableHeader class="sticky top-0 z-10">
                                            <TableRow>
                                                <TableHead class="text-xs">Product</TableHead>
                                                <TableHead class="text-xs w-28">Lot No.</TableHead>
                                                <TableHead class="text-xs w-28">Expiry</TableHead>
                                                <TableHead class="text-xs text-center w-16">Qty</TableHead>
                                                <TableHead class="text-xs text-right w-24">Unit Price</TableHead>
                                                <TableHead class="w-8" />
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="returnItems.length === 0">
                                                <TableCell colspan="5"
                                                    class="text-center text-muted-foreground text-xs py-6">
                                                    No items added yet.
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-for="(item, i) in returnItems" :key="i">
                                                <TableCell class="text-xs whitespace-normal break-words min-w-0">
                                                    {{ item.product_name }}
                                                </TableCell>
                                                <TableCell class="text-xs">
                                                    <span v-if="item.lot_number"
                                                        class="inline-flex items-center gap-1 font-mono">
                                                        <Tag class="h-3 w-3 text-amber-500 shrink-0" />
                                                        {{ item.lot_number }}
                                                    </span>
                                                    <span v-else class="text-muted-foreground/40">—</span>
                                                </TableCell>
                                                <TableCell class="text-xs text-muted-foreground">
                                                    {{ item.expiration_date ?? '—' }}
                                                </TableCell>
                                                <TableCell class="text-xs text-center font-medium">
                                                    {{ item.quantity }}
                                                </TableCell>
                                                <TableCell class="text-xs text-right font-mono">
                                                    {{ item.unit_price.toFixed(2) }}
                                                </TableCell>
                                                <TableCell class="text-center">
                                                    <button type="button" @click="removeItem(i)"
                                                        class="text-destructive hover:opacity-70">
                                                        <X class="h-4 w-4" />
                                                    </button>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>

                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>
        </form>

        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />
            <BaseButton type="button" @click="openConfirmDialog" transactionType="create" :loading="isProcessing"
                :disabled="isBusy" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="create"
            @cancel="isDialogOpen = false" @confirm="handleSubmit" />
    </FormCard>
</template>
