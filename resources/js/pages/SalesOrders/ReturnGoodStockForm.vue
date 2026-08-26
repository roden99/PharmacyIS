<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { Input } from '@/components/ui/input';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import { useDateFormatter } from '@/composables/useDateFormatter';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table';
import { Tag } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

const { reverseDate, normalizeDate } = useDateFormatter();

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    isProcessing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const { skeletonLayout } = useFieldGroupSkeleton([10, 2]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const isLoading = ref(true);
const soItems = ref([]);
const rgsDate = ref('');
const notes = ref('');

const returnItems = ref([]);

const isBusy = computed(() => isLoading.value || props.isProcessing);

const fmt = (value) =>
    Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

onMounted(async () => {
    try {
        const res = await axios.get(`/sales-orders/${props.order.id}`, {
            headers: { Accept: 'application/json' },
        });
        soItems.value = res.data.items ?? [];
        if (soItems.value.length === 0) {
            toast.warning('No items found for this sales order. RGS cannot be processed.');
            emit('form-closed');
            return;
        }
        returnItems.value = soItems.value.map(item => ({
            product_id: item.product_id,
            lot_id: item.lot_id ?? null,
            quantity: 0,
            unit_price: item.unit_price,
            product_name: item.product_name,
            lot_number: item.lot_number ?? null,
            max_qty: item.quantity,
        }));
    } catch {
        toast.error('Failed to load order items.');
        emit('form-closed');
    } finally {
        isLoading.value = false;
    }
});

const activeItems = computed(() =>
    returnItems.value.filter(i => Number(i.quantity) > 0)
);

const handleSubmit = () => {
    if (!rgsDate.value) {
        toast.error('Please select an RGS date.');
        return;
    }
    if (activeItems.value.length === 0) {
        toast.error('Please enter a return quantity for at least one item.');
        return;
    }
    for (const item of activeItems.value) {
        if (!item.lot_id) {
            toast.error(`"${item.product_name}" has no lot number. A lot number is required for all return items.`);
            return;
        }
        if (Number(item.quantity) > Number(item.max_qty)) {
            toast.error(`Return quantity for "${item.product_name}" exceeds original sold quantity (${item.max_qty}).`);
            return;
        }
    }

    emit('handleSubmit', {
        rgs_date: normalizeDate(rgsDate.value),
        notes: notes.value || null,
        items: activeItems.value.map(i => ({
            product_id: i.product_id,
            lot_id: i.lot_id,
            quantity: Number(i.quantity),
        })),
    });
};
</script>

<template>
    <FormCard :loading="isBusy" size="3xl" :title="`Return Good Stock — ${order.invoice_no || 'SO #' + order.id}`">
        <div class="mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: RGS Info -->
                <div class="col-span-4">
                    <BaseField legend="RGS Information" description="Record the return details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">

                                    <Field class="col-span-12">
                                        <FieldLabel>Customer / Account</FieldLabel>
                                        <p class="text-sm font-semibold leading-tight mt-0.5">
                                            {{ order.customer_name ?? '—' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ order.account_name ?? '—' }}</p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel>Original Invoice No.</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ order.invoice_no ?? '—' }}</p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel>RGS Date <span class="text-destructive">*</span></FieldLabel>
                                        <BaseDatePick v-model="rgsDate" :disabled="isBusy" />
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel>Notes</FieldLabel>
                                        <Input v-model="notes" placeholder="Optional notes..." :disabled="isBusy" />
                                    </Field>

                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Items table -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Items to Return" description="Enter the quantity to return per item"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">
                                <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
                                    <Table class="text-xs">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead class="text-xs">Item Name</TableHead>
                                                <TableHead class="text-xs w-28">Lot No.</TableHead>
                                                <TableHead class="text-xs text-center w-20">SO Qty</TableHead>
                                                <TableHead class="text-xs text-center w-24">Return Qty</TableHead>
                                                <TableHead class="text-xs text-right w-24">Unit Price</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="returnItems.length === 0 && !isLoading">
                                                <TableCell colspan="5"
                                                    class="text-xs text-center text-muted-foreground py-4">
                                                    No items found.
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-for="(item, index) in returnItems" :key="index">
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
                                                <TableCell class="text-xs text-center">{{ item.max_qty }}</TableCell>
                                                <TableCell class="text-xs text-center">
                                                    <Input v-model.number="item.quantity" type="number" :min="0"
                                                        :max="item.max_qty" class="w-20 text-center h-7 text-xs"
                                                        :disabled="isBusy" />
                                                </TableCell>
                                                <TableCell class="text-xs text-right">{{ fmt(item.unit_price) }}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>

                                <div v-if="activeItems.length > 0" class="flex justify-end pt-2 pr-1">
                                    <div
                                        class="flex items-center gap-3 rounded-md bg-orange-50 dark:bg-orange-950/30 px-4 py-2 border border-orange-200 dark:border-orange-800">
                                        <span
                                            class="text-xs text-muted-foreground uppercase tracking-wide font-medium">Returning</span>
                                        <span
                                            class="font-mono text-base font-bold text-orange-600 dark:text-orange-400">
                                            {{activeItems.reduce((s, i) => s + Number(i.quantity), 0)}} item(s)
                                        </span>
                                    </div>
                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>
        </div>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" :disabled="isBusy" />
            <BaseButton type="button" @click="handleSubmit" transactionType="save" :disabled="isBusy"
                :loading="props.isProcessing" />
        </template>
    </FormCard>
</template>
