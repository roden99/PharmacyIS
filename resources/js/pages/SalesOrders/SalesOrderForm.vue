<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel, FieldSeparator } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import { useDateFormatter } from '@/composables/useDateFormatter';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import SalesOrderItemsTable from './SalesOrderItemsTable.vue';
import axios from 'axios';

const { reverseDate, normalizeDate } = useDateFormatter();

const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },
    cardTitle: {
        type: String,
        default: 'Form',
    },
    order: {
        type: Object,
        default: null,
    },
    transactionType: {
        type: String,
        default: 'create',
    },
});

const form = useForm({
    customer_sales_account_id: props.order?.customer_sales_account_id
        ? String(props.order.customer_sales_account_id)
        : '',
    invoice_no: props.order?.invoice_no ?? '',
    invoice_date: null,
    delivery_date: null,
    discount_percentage: props.order?.discount_percentage ?? 0,
    terms: props.order?.terms ?? '',
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const isDialogOpen = ref(false);
const isLoading = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

// Header: account+discount(row1) | invoice_no+invoice_date+delivery_date(row2)
const { skeletonLayout } = useFieldGroupSkeleton([10, 2, 4, 4, 4]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const handleAlertClose = () => {
    isDialogOpen.value = false;
    if (props.transactionType === 'delete') {
        emit('form-closed');
    }
};

const openConfirmDialog = () => {
    isDialogOpen.value = true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        isDialogOpen.value = false;
    }
});

const handleSubmit = () => {
    try {
        if (orderItems.value.length === 0) {
            toast.error('Please add at least one item before saving.');
            return;
        }
        emit('handleSubmit', {
            ...form.data(),
            customer_sales_account_id: form.customer_sales_account_id
                ? Number(form.customer_sales_account_id)
                : null,
            invoice_date: normalizeDate(form.invoice_date),
            delivery_date: normalizeDate(form.delivery_date),
            items: orderItems.value.map(item => ({
                product_id: Number(item.product_id),
                quantity: item.quantity,
                unit_price: item.unit_price,
                discount_percentage: item.discount_percentage ?? 0,
            })),
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(async () => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
    isLoading.value = true;
    await Promise.all([
        loadCustomerAccounts(),
        loadProducts(),
        props.transactionType === 'update' && props.order?.id
            ? loadOrderItems()
            : Promise.resolve(),
    ]);
    isLoading.value = false;
});

// ─── Customer accounts combobox ───────────────────────────────────────────────

const accountOptions = ref([]);

async function loadCustomerAccounts(searchQuery = '', includeId = null) {
    try {
        const res = await axios.get('/customer-accounts', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery, include_id: includeId },
        });
        accountOptions.value = Array.isArray(res.data.accounts) ? res.data.accounts : [];
    } catch (error) {
        console.error('Failed to fetch customer accounts:', error);
        toast.error('Failed to load customer accounts. Please try again.');
    }
}

// Auto-apply discount when account is selected
watch(() => form.customer_sales_account_id, (newId) => {
    if (!newId) return;
    const account = accountOptions.value.find(a => a.value === newId);
    if (account && account.discount_percentage != null) {
        form.discount_percentage = account.discount_percentage;
    }
});

// ─── Load existing items for update ──────────────────────────────────────────

async function loadOrderItems() {
    try {
        const res = await axios.get(`/sales-orders/${props.order.id}`, {
            headers: { Accept: 'application/json' },
        });
        orderItems.value = res.data.items.map(item => ({
            ...item,
            discount_percentage: Number(item.discount_percentage) || 0,
        }));

        const d = res.data.order;
        if (d.invoice_date) form.invoice_date = reverseDate(d.invoice_date.slice(0, 10));
        if (d.delivery_date) form.delivery_date = reverseDate(d.delivery_date.slice(0, 10));
        form.invoice_no = d.invoice_no ?? '';

        if (d.customer_sales_account_id) {
            await loadCustomerAccounts('', d.customer_sales_account_id);
            form.customer_sales_account_id = String(d.customer_sales_account_id);
        }
        form.terms = d.terms ?? '';
    } catch (error) {
        console.error('Failed to load order items:', error);
        toast.error('Failed to load order items.');
    }
}

// ─── Items management ─────────────────────────────────────────────────────────

const selectedProduct = ref(null);
const itemQuantity = ref(1);
const itemPrice = ref(0);
const itemDiscount = ref(0);

// Pre-fill item discount from header discount when a product is picked
watch(selectedProduct, (newVal) => {
    if (newVal) {
        itemDiscount.value = Number(form.discount_percentage) || 0;
    }
});

const orderItems = ref([]);

const totalAmount = computed(() => {
    const raw = orderItems.value.reduce((sum, item) => {
        const disc = Number(item.discount_percentage) || 0;
        return sum + Number(item.quantity) * Number(item.unit_price) * (1 - disc / 100);
    }, 0);
    return raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

const addItem = () => {
    if (!selectedProduct.value) {
        toast.error('Please select a product.');
        return;
    }
    const product = productsOptions.value.find(p => p.value === selectedProduct.value);
    orderItems.value.push({
        product_id: selectedProduct.value,
        product_name: product?.label ?? selectedProduct.value,
        quantity: Number(itemQuantity.value),
        unit_price: Number(itemPrice.value),
        discount_percentage: Number(itemDiscount.value) || Number(form.discount_percentage) || 0,
    });
    selectedProduct.value = null;
    itemQuantity.value = 1;
    itemPrice.value = 0;
    itemDiscount.value = 0;
};

const removeItem = (index) => {
    orderItems.value.splice(index, 1);
};

// ─── Products combobox ────────────────────────────────────────────────────────

const productsOptions = ref([]);

async function loadProducts(searchQuery = '') {
    try {
        const res = await axios.get('/products', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        productsOptions.value = (res.data.products ?? []).map(product => ({
            value: String(product.id),
            label: product.display_name,
        }));
    } catch (error) {
        console.error('Failed to fetch products:', error);
        toast.error('Failed to load products. Please try again.');
    }
}
</script>

<template>
    <FormCard :loading="false" size="lg">
        <form @submit.prevent class="space-y-4 mt-4">
            <BaseField legend="Sales Order Information" description="Enter order details">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">

                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-8">
                                <FieldLabel class="font-normal">Customer / Account:</FieldLabel>
                                <BaseCombobox v-model="form.customer_sales_account_id" :options="accountOptions"
                                    empty-message="Search customer or account" width="w-full"
                                    @search="loadCustomerAccounts" />
                            </Field>

                            <!-- <Field class="col-span-2">
                                <FieldLabel class="font-normal">Discount (%):</FieldLabel>
                                <Input v-model="form.discount_percentage" type="number" min="0" max="100" step="0.01"
                                    placeholder="0.00" />
                            </Field> -->

                            <Field class="col-span-4">
                                <FieldLabel class="font-normal">Terms(Days):</FieldLabel>
                                <Input v-model="form.terms" placeholder="e.g. 30" />
                            </Field>

                        </div>

                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-4">
                                <FieldLabel class="font-normal">Invoice No.:</FieldLabel>
                                <Input v-model="form.invoice_no" placeholder="e.g. INV-001" />
                            </Field>

                            <Field class="col-span-4">
                                <FieldLabel class="font-normal">Invoice Date:</FieldLabel>
                                <BaseDatePick v-model="form.invoice_date" class="w-32" />
                            </Field>

                            <Field class="col-span-4">
                                <FieldLabel class="font-normal">Delivery Date:</FieldLabel>
                                <BaseDatePick v-model="form.delivery_date" class="w-32" />
                            </Field>

                        </div>


                        <FieldSeparator />

                    </FieldGroup>

                    <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems">
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <div class="grid grid-cols-12 items-start gap-2">
                                    <Field class="col-span-5">
                                        <FieldLabel class="font-normal">Select Item:</FieldLabel>
                                        <BaseCombobox v-model="selectedProduct" :options="productsOptions"
                                            empty-message="No products found" width="w-full" @search="loadProducts"
                                            placeholder="Search product..." />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Qty</FieldLabel>
                                        <Input v-model="itemQuantity" type="number" min="1" placeholder="0" />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">UP</FieldLabel>
                                        <Input v-model="itemPrice" type="number" min="0" step="0.01"
                                            placeholder="0.00" />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Disc %</FieldLabel>
                                        <Input v-model="itemDiscount" type="number" min="0" max="100" step="0.01"
                                            placeholder="0.00" />
                                    </Field>

                                    <Field class="col-span-1">
                                        <FieldLabel class="invisible">-</FieldLabel>
                                        <BaseButton type="button" @click="addItem" :transactionType="'add'"
                                            :disabled="isBusy" :skeleton="isLoading" />
                                    </Field>
                                </div>

                                <SalesOrderItemsTable :items="orderItems" @remove="removeItem" />
                            </Field>
                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>

        <template #footer>
            <div class="mr-auto flex flex-col">
                <span class="text-xs text-muted-foreground uppercase tracking-wide">Total Amount</span>
                <span class="text-lg font-bold">{{ totalAmount }}</span>
            </div>

            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />

            <BaseButton type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isProcessing" :disabled="isBusy" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
