<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X } from 'lucide-vue-next';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import CarryItemDetailsTable from './CarryItemDetailsTable.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { parseDate } from '@internationalized/date';
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    carryItem: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const isProcessing = ref(false);
const isDialogOpen = ref(false);
const isBusy = computed(() => isLoading.value || isProcessing.value);

// ── Header form ─────────────────────────────────────────────
const form = ref({
    sales_agent_id: props.carryItem.sales_agent_id,
    carry_date: props.carryItem.carry_date ? parseDate(props.carryItem.carry_date) : null,
    reference_number: props.carryItem.reference_number ?? '',
    notes: props.carryItem.notes ?? '',
});

// ── Sales Agent ──────────────────────────────────────────────
const agentOptions = ref([]);
const selectedAgent = ref(props.carryItem.sales_agent_id ? String(props.carryItem.sales_agent_id) : null);

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
    form.value.sales_agent_id = val ? Number(val) : null;
});

// ── Existing items ───────────────────────────────────────────
const existingItems = ref([]);  // { id, product_name, lot_number, quantity, removed }
const removeDetailIds = computed(() =>
    existingItems.value.filter(i => i.removed).map(i => i.id)
);

const toggleRemove = (item) => { item.removed = !item.removed; };

// ── New items ────────────────────────────────────────────────
const productsOptions = ref([]);
const selectedProduct = ref(null);
const selectedLot = ref(null);
const lotOptions = ref([]);
const itemQuantity = ref(1);
const newItems = ref([]);

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
    } catch { /* no lots */ }
});

const addItem = () => {
    if (!selectedProduct.value) { toast.error('Please select a product.'); return; }
    if (!selectedLot.value) { toast.error('Please select a lot number.'); return; }
    const product = productsOptions.value.find(p => p.value === selectedProduct.value);
    const lot = lotOptions.value.find(l => l.value === selectedLot.value);
    newItems.value.push({
        product_id: selectedProduct.value,
        product_name: product?.label ?? selectedProduct.value,
        lot_id: selectedLot.value,
        lot_number: lot ? lot.label.split(' ')[0] : null,
        quantity: Number(itemQuantity.value),
    });
    selectedProduct.value = null;
    selectedLot.value = null;
    lotOptions.value = [];
    itemQuantity.value = 1;
};

const removeNewItem = (index) => { newItems.value.splice(index, 1); };

// ── Init ─────────────────────────────────────────────────────
onMounted(async () => {
    isLoading.value = true;
    const [, , itemsRes] = await Promise.allSettled([
        loadAgents(),
        loadProducts(),
        axios.get(`/carry-items/${props.carryItem.id}`, { headers: { Accept: 'application/json' } }),
    ]);
    if (itemsRes.status === 'fulfilled') {
        existingItems.value = (itemsRes.value.data.items ?? []).map(i => ({
            ...i,
            removed: false,
        }));
    }
    isLoading.value = false;
});

// ── Submit ───────────────────────────────────────────────────
const openConfirmDialog = () => {
    if (!form.value.sales_agent_id) { toast.error('Please select a sales agent.'); return; }
    if (!form.value.carry_date) { toast.error('Please select a carry date.'); return; }
    isDialogOpen.value = true;
};

const handleSubmit = () => {
    isProcessing.value = true;
    router.patch(`/carry-items/${props.carryItem.id}`, {
        ...form.value,
        carry_date: normalizeDate(form.value.carry_date),
        remove_detail_ids: removeDetailIds.value,
        updated_items: existingItems.value
            .filter(i => !i.removed)
            .map(i => ({ detail_id: i.id, quantity: i.quantity })),
        new_items: newItems.value.map(i => ({
            product_id: Number(i.product_id),
            lot_id: i.lot_id ? Number(i.lot_id) : null,
            quantity: i.quantity,
        })),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Success', { description: 'Carry item updated successfully.' });
            emit('form-closed');
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.warning('Failed to update carry item.', { description: first });
        },
        onFinish: () => {
            isProcessing.value = false;
            isDialogOpen.value = false;
        },
    });
};
</script>

<template>
    <FormCard :loading="isLoading || isProcessing" card-title="Edit Carry Item" size="4xl">
        <div class="grid grid-cols-12 gap-6 mt-4">

            <!-- Left: Carry info -->
            <div class="col-span-4 space-y-3">
                <div class="space-y-1">
                    <Label>Sales Agent</Label>
                    <BaseCombobox v-model="selectedAgent" :options="agentOptions" empty-message="No agents found"
                        width="w-full" @search="loadAgents" placeholder="Search agent..." />
                </div>
                <div class="space-y-1">
                    <Label>Carry Date</Label>
                    <BaseDatePick v-model="form.carry_date" class="w-full" />
                </div>
                <div class="space-y-1">
                    <Label>Reference No.</Label>
                    <Input v-model="form.reference_number" placeholder="Optional reference..." />
                </div>
                <div class="space-y-1">
                    <Label>Notes</Label>
                    <Textarea v-model="form.notes" placeholder="Optional notes..." rows="4" />
                </div>
            </div>

            <!-- Right: Items -->
            <div class="col-span-8 flex flex-col gap-4">

                <!-- Existing items -->
                <div>
                    <p class="text-sm font-medium mb-1">Existing Items</p>
                    <div class="rounded-md border overflow-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Product</TableHead>
                                    <TableHead class="w-28">Lot No.</TableHead>
                                    <TableHead class="text-center w-24">Qty</TableHead>
                                    <TableHead class="w-8" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="existingItems.length === 0">
                                    <TableCell colspan="4" class="text-center text-muted-foreground py-3">No items.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="item in existingItems" :key="item.id"
                                    :class="item.removed ? 'opacity-40' : ''">
                                    <TableCell>{{ item.product_name }}</TableCell>
                                    <TableCell class="text-muted-foreground text-sm">{{ item.lot_number }}</TableCell>
                                    <TableCell class="text-center">
                                        <Input v-model.number="item.quantity" type="number" min="0.0001" step="1"
                                            :disabled="item.removed" class="w-24 text-center mx-auto" />
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <button type="button" @click="toggleRemove(item)"
                                            :class="item.removed ? 'text-muted-foreground' : 'text-destructive'"
                                            class="hover:opacity-70">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <p v-if="removeDetailIds.length" class="text-xs text-destructive mt-1">
                        {{ removeDetailIds.length }} item(s) will be removed and returned to inventory.
                    </p>
                </div>

                <!-- Add new items -->
                <div>
                    <p class="text-sm font-medium mb-1">Add Items</p>
                    <div class="grid grid-cols-12 gap-2 items-end">
                        <div class="col-span-5 space-y-1">
                            <Label class="text-xs">Product</Label>
                            <BaseCombobox v-model="selectedProduct" :options="productsOptions"
                                empty-message="No products" width="w-full" @search="loadProducts"
                                placeholder="Search product..." />
                        </div>
                        <div class="col-span-3 space-y-1">
                            <Label class="text-xs">Lot No.</Label>
                            <BaseCombobox v-model="selectedLot" :options="lotOptions" empty-message="No lots"
                                width="w-full" placeholder="Select lot..." />
                        </div>
                        <div class="col-span-2 space-y-1">
                            <Label class="text-xs">Qty</Label>
                            <Input v-model="itemQuantity" type="number" min="1" step="1" placeholder="1" />
                        </div>
                        <div class="col-span-2">
                            <BaseButton type="button" @click="addItem" transactionType="add" :disabled="isBusy" />
                        </div>
                    </div>
                    <CarryItemDetailsTable :items="newItems" @remove="removeNewItem" class="mt-2" />
                </div>

            </div>
        </div>

        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel" />
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog" transactionType="update" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="update"
            @cancel="isDialogOpen = false" @confirm="handleSubmit" />
    </FormCard>
</template>
