<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
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

const { normalizeDate, reverseDate } = useDateFormatter();

const props = defineProps({
    record: { type: Object, required: true },
    isProcessing: { type: Boolean, default: false },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const { skeletonLayout } = useFieldGroupSkeleton([10, 2]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const isLoading = ref(true);
const rgsInfo = ref(null);
const rgsDate = ref(null);
const notes = ref('');
const returnItems = ref([]);
const lotOptionsMap = ref({}); // product_id → lot option array

const isBusy = computed(() => isLoading.value || props.isProcessing);

const fmt = (v) =>
    Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

onMounted(async () => {
    try {
        const res = await axios.get(`/return-good-stocks/${props.record.id}`, {
            headers: { Accept: 'application/json' },
        });
        rgsInfo.value = res.data.rgs;
        notes.value = res.data.rgs.notes ?? '';
        if (res.data.rgs.rgs_date) rgsDate.value = reverseDate(res.data.rgs.rgs_date.slice(0, 10));

        returnItems.value = (res.data.items ?? []).map(item => ({
            product_id: item.product_id,
            lot_id: item.lot_id ?? null,
            quantity: item.quantity,
            unit_price: item.unit_price,
            product_name: item.product_name,
            lot_number: item.lot_number ?? null,
        }));

        // Load lot options for items with no lot assigned
        const missingLotIds = [...new Set(
            returnItems.value.filter(i => !i.lot_id).map(i => i.product_id)
        )];
        await Promise.all(missingLotIds.map(pid => loadLotsForProduct(pid)));
    } catch {
        toast.error('Failed to load RGS details.');
        emit('form-closed');
    } finally {
        isLoading.value = false;
    }
});

const activeItems = computed(() =>
    returnItems.value.filter(i => Number(i.quantity) > 0)
);

async function loadLotsForProduct(productId) {
    if (lotOptionsMap.value[productId]) return;
    try {
        const res = await axios.get(`/products/${productId}/lots`, {
            headers: { Accept: 'application/json' },
            params: { include_empty: 1 },
        });
        lotOptionsMap.value[productId] = res.data.lots ?? [];
    } catch {
        lotOptionsMap.value[productId] = [];
    }
}

function onLotSelect(item, lotId) {
    item.lot_id = lotId;
    const lot = (lotOptionsMap.value[item.product_id] ?? []).find(l => l.value === lotId);
    if (lot) item.lot_number = lot.lot_number;
}

const handleSubmit = () => {
    if (!rgsDate.value) {
        toast.error('Please select an RGS date.');
        return;
    }
    if (activeItems.value.length === 0) {
        toast.error('Please enter a quantity for at least one item.');
        return;
    }
    for (const item of activeItems.value) {
        if (!item.lot_id) {
            toast.error(`Please select a lot for "${item.product_name}".`);
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
    <FormCard :loading="isBusy" size="3xl" cardTitle="Edit Return Good Stock">
        <div class="mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: header fields -->
                <div class="col-span-4">
                    <BaseField legend="RGS Information" description="Update return details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">
                                    <Field class="col-span-12">
                                        <FieldLabel>Customer / Account</FieldLabel>
                                        <p class="text-sm font-semibold leading-tight mt-0.5">{{ rgsInfo?.customer_name
                                            ?? '—' }}</p>
                                        <p class="text-xs text-muted-foreground">{{ rgsInfo?.account_name ?? '—' }}</p>
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>Reference Invoice No.</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ rgsInfo?.invoice_no ?? '—' }}</p>
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

                <!-- Right: items table -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Returned Items" description="Edit quantities to return"
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
                                                <TableHead class="text-xs text-center w-24">Return Qty</TableHead>
                                                <TableHead class="text-xs text-right w-24">Unit Price</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="returnItems.length === 0 && !isLoading">
                                                <TableCell colspan="4"
                                                    class="text-xs text-center text-muted-foreground py-4">No items.
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-for="(item, index) in returnItems" :key="index">
                                                <TableCell class="text-xs whitespace-normal break-words min-w-0">{{
                                                    item.product_name }}</TableCell>
                                                <TableCell class="text-xs">
                                                    <span v-if="item.lot_number"
                                                        class="inline-flex items-center gap-1 font-mono">
                                                        <Tag class="h-3 w-3 text-amber-500 shrink-0" />
                                                        {{ item.lot_number }}
                                                    </span>
                                                    <BaseCombobox v-else :modelValue="item.lot_id"
                                                        @update:modelValue="(val) => onLotSelect(item, val)"
                                                        :options="lotOptionsMap[item.product_id] ?? []"
                                                        empty-message="No lots found" width="w-40"
                                                        placeholder="Select lot..." :disabled="isBusy" />
                                                </TableCell>
                                                <TableCell class="text-xs text-center">
                                                    <Input v-model.number="item.quantity" type="number" :min="0"
                                                        class="w-20 text-center h-7 text-xs" :disabled="isBusy" />
                                                </TableCell>
                                                <TableCell class="text-xs text-right">{{ fmt(item.unit_price) }}
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
        </div>
        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" :disabled="isBusy" />
            <BaseButton type="button" @click="handleSubmit" transactionType="update" :loading="props.isProcessing"
                :disabled="isBusy" />
        </template>
    </FormCard>
</template>
