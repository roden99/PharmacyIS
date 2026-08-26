<script setup>
import { ref, computed, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Undo2 } from 'lucide-vue-next';
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

const props = defineProps({
    carryItem: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const isProcessing = ref(false);
const rows = ref([]); // { detail_id, product_name, lot_number, available, returnQty }
const selectedIds = ref([]); // tracks selected detail_ids separately for reliable reactivity

onMounted(async () => {
    try {
        const res = await axios.get(`/carry-items/${props.carryItem.id}`, {
            headers: { Accept: 'application/json' },
        });
        rows.value = (res.data.items ?? []).map(item => ({
            detail_id: item.id,
            product_name: item.product_name,
            lot_number: item.lot_number,
            available: item.quantity,
            returnQty: item.quantity,
        }));
    } catch (e) {
        toast.error('Failed to load carry item details.');
        console.error(e);
    } finally {
        isLoading.value = false;
    }
});

const isSelected = (id) => selectedIds.value.includes(id);

const toggleSelect = (id, val) => {
    if (val) {
        if (!selectedIds.value.includes(id)) selectedIds.value = [...selectedIds.value, id];
    } else {
        selectedIds.value = selectedIds.value.filter(x => x !== id);
    }
};

const selectedRows = computed(() => rows.value.filter(r => selectedIds.value.includes(r.detail_id)));

const hasSelection = computed(() => selectedRows.value.length > 0);

const handleSubmit = () => {
    if (!hasSelection.value) {
        toast.warning('Please select at least one item to return.');
        return;
    }

    for (const row of selectedRows.value) {
        if (!row.returnQty || row.returnQty <= 0) {
            toast.warning(`Invalid quantity for "${row.product_name}".`);
            return;
        }
        if (row.returnQty > row.available) {
            toast.warning(`Return quantity for "${row.product_name}" exceeds available (${row.available}).`);
            return;
        }
    }

    isProcessing.value = true;
    router.post(`/carry-items/${props.carryItem.id}/return-items`, {
        returns: selectedRows.value.map(r => ({
            detail_id: r.detail_id,
            quantity: r.returnQty,
        })),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Success', { description: 'Items returned to inventory.' });
            emit('form-closed');
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error('Failed to return items.', { description: first });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <FormCard :loading="isLoading || isProcessing" card-title="Return to Inventory" size="3xl">
        <div class="space-y-2 mt-2 text-sm text-muted-foreground">
            <p>
                <span class="font-medium text-foreground">{{ carryItem.sales_agent_name }}</span>
                &mdash; {{ carryItem.carry_date }}
            </p>
            <p>Select the items to return and adjust quantities as needed.</p>
        </div>

        <div class="rounded-md border overflow-auto mt-3">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10" />
                        <TableHead>Product</TableHead>
                        <TableHead class="w-28">Lot No.</TableHead>
                        <TableHead class="text-center w-24">Available</TableHead>
                        <TableHead class="text-center w-32">Return Qty</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!isLoading && rows.length === 0">
                        <TableCell colspan="5" class="text-center text-muted-foreground py-6">
                            No items in this carry record.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="row in rows" :key="row.detail_id"
                        :class="{ 'bg-muted/40': isSelected(row.detail_id) }">
                        <TableCell class="text-center">
                            <input type="checkbox" :checked="isSelected(row.detail_id)"
                                @change="e => toggleSelect(row.detail_id, e.target.checked)"
                                class="h-4 w-4 cursor-pointer accent-primary" />
                        </TableCell>
                        <TableCell>{{ row.product_name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ row.lot_number }}</TableCell>
                        <TableCell class="text-center">{{ row.available }}</TableCell>
                        <TableCell class="text-center">
                            <Input v-model.number="row.returnQty" type="number" min="0.0001" :max="row.available"
                                step="1" :disabled="!isSelected(row.detail_id)" class="w-24 text-center mx-auto" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
            <Button :disabled="!hasSelection || isProcessing" @click="handleSubmit" class="gap-1">
                <Undo2 class="h-4 w-4" />
                Return Selected
            </Button>
        </template>
    </FormCard>
</template>
