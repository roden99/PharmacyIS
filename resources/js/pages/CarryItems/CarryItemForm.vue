<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel, FieldSeparator } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import { useDateFormatter } from '@/composables/useDateFormatter';
import CarryItemDetailsTable from './CarryItemDetailsTable.vue';
import axios from 'axios';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
    cardTitle: { type: String, default: 'Carry Items' },
    transactionType: { type: String, default: 'create' },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    sales_agent_id: null,
    carry_date: null,
    reference_number: '',
    notes: '',
});

const isLoading = ref(false);
const isDialogOpen = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

const { skeletonLayout } = useFieldGroupSkeleton([6, 12]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const openConfirmDialog = () => {
    if (!form.sales_agent_id) {
        toast.error('Please select a sales agent.');
        return;
    }
    if (!form.carry_date) {
        toast.error('Please select a carry date.');
        return;
    }
    if (carryDetails.value.length === 0) {
        toast.error('Please add at least one item.');
        return;
    }
    isDialogOpen.value = true;
};

const handleAlertClose = () => {
    isDialogOpen.value = false;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        isDialogOpen.value = false;
    }
});

const handleSubmit = () => {
    try {
        emit('handleSubmit', {
            ...form.data(),
            carry_date: normalizeDate(form.carry_date),
            items: carryDetails.value.map(item => ({
                product_id: Number(item.product_id),
                lot_id: item.lot_id ? Number(item.lot_id) : null,
                quantity: item.quantity,
            })),
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

// ── Sales Agent search ──────────────────────────────────────
const agentOptions = ref([]);
const selectedAgent = ref(null);

async function loadAgents(searchQuery = '') {
    try {
        const res = await axios.get('/sales-agents', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        agentOptions.value = res.data.sales_agents.map(a => ({
            value: String(a.id),
            label: a.name,
        }));
    } catch {
        toast.error('Failed to load sales agents.');
    }
}

watch(selectedAgent, (val) => {
    form.sales_agent_id = val ? Number(val) : null;
});

// ── Product search ──────────────────────────────────────────
const productsOptions = ref([]);
const selectedProduct = ref(null);
const selectedLot = ref(null);
const lotOptions = ref([]);
const itemQuantity = ref(1);
const carryDetails = ref([]);

async function loadProducts(searchQuery = '') {
    try {
        const res = await axios.get('/products', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        productsOptions.value = res.data.products.map(p => ({
            value: String(p.id),
            label: p.display_name,
        }));
    } catch {
        toast.error('Failed to load products.');
    }
}

watch(selectedProduct, async (newVal) => {
    selectedLot.value = null;
    lotOptions.value = [];
    if (!newVal) return;
    try {
        const res = await axios.get(`/products/${newVal}/lots`, {
            headers: { Accept: 'application/json' },
        });
        lotOptions.value = (res.data.lots ?? []).map(l => ({
            value: String(l.id),
            label: `${l.lot_number} (exp: ${l.expiration_date})`,
        }));
    } catch {
        // no lots
    }
});

const addItem = () => {
    if (!selectedProduct.value) {
        toast.error('Please select a product.');
        return;
    }
    if (!selectedLot.value) {
        toast.error('Please select a lot number.');
        return;
    }
    const product = productsOptions.value.find(p => p.value === selectedProduct.value);
    const lot = lotOptions.value.find(l => l.value === selectedLot.value);

    carryDetails.value.push({
        product_id: selectedProduct.value,
        product_name: product?.label ?? selectedProduct.value,
        lot_id: selectedLot.value ?? null,
        lot_number: lot ? lot.label.split(' ')[0] : null,
        quantity: Number(itemQuantity.value),
    });

    selectedProduct.value = null;
    selectedLot.value = null;
    lotOptions.value = [];
    itemQuantity.value = 1;
};

const removeItem = (index) => {
    carryDetails.value.splice(index, 1);
};

onMounted(async () => {
    isLoading.value = true;
    await Promise.all([loadAgents(), loadProducts()]);
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="false" size="3xl">
        <form @submit.prevent class="space-y-4 mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: Carry Info -->
                <div class="col-span-4">
                    <BaseField legend="Carry Information" description="Select agent and carry date">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Sales Agent:</FieldLabel>
                                        <BaseCombobox v-model="selectedAgent" :options="agentOptions"
                                            empty-message="No agents found" width="w-full" @search="loadAgents"
                                            placeholder="Search agent..." />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Carry Date:</FieldLabel>
                                        <BaseDatePick v-model="form.carry_date" class="w-32" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Reference No.:</FieldLabel>
                                        <Input v-model="form.reference_number" placeholder="Optional reference..." />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Notes:</FieldLabel>
                                        <Textarea v-model="form.notes" placeholder="Optional notes..." rows="4" />
                                    </Field>
                                </div>
                                <FieldSeparator />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Add Items + Table -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Products to Carry"
                        description="Select products and quantities to assign to the agent"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">
                                <div class="grid w-full grid-cols-12 gap-3">
                                    <Field class="col-span-5">
                                        <FieldLabel class="font-normal">Select Product:</FieldLabel>
                                        <BaseCombobox v-model="selectedProduct" :options="productsOptions"
                                            empty-message="No products found" width="w-full" @search="loadProducts"
                                            placeholder="Search product..." />
                                    </Field>
                                    <Field class="col-span-3">
                                        <FieldLabel class="font-normal">Lot No.</FieldLabel>
                                        <BaseCombobox v-model="selectedLot" :options="lotOptions"
                                            empty-message="No lots" width="w-full" placeholder="Select lot..." />
                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Qty</FieldLabel>
                                        <Input v-model="itemQuantity" type="number" min="1" step="1" placeholder="1" />
                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="invisible">-</FieldLabel>
                                        <BaseButton type="button" @click="addItem" :transactionType="'add'"
                                            :disabled="isBusy" :skeleton="isLoading" />
                                    </Field>
                                </div>

                                <CarryItemDetailsTable :items="carryDetails" @remove="removeItem" class="mt-3 flex-1" />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>
        </form>

        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel" />
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog"
                :transactionType="props.transactionType" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
