<script setup>
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const productInfo = ref(null);
const history = ref([]);

onMounted(async () => {
    try {
        const res = await axios.get(`/products/${props.product.id}/history`, {
            headers: { Accept: 'application/json' },
        });
        productInfo.value = res.data.product;
        history.value = res.data.history;
    } catch (error) {
        console.error('Failed to load history:', error);
        toast.error('Failed to load product history.');
    } finally {
        isLoading.value = false;
    }
});

const badgeVariant = (type) => {
    if (type === 'INITIAL') return 'secondary';
    if (type === 'IN') return 'default';
    return 'destructive';
};
</script>

<template>
    <FormCard size="4xl" :loading="isLoading">
        <div class="space-y-5 mt-4">

            <!-- Product info header -->
            <div v-if="productInfo" class="rounded-lg border bg-muted/40 px-5 py-4">
                <p class="text-base font-semibold mb-3">{{ productInfo.display_name }}</p>
                <div class="flex flex-wrap gap-6 text-sm text-muted-foreground">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Current Qty</span>
                        <span class="text-foreground font-semibold text-lg">{{ productInfo.product_qty }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Reorder Level</span>
                        <span class="text-foreground font-semibold text-lg">{{ productInfo.reorder_level }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Initial Date</span>
                        <span class="text-foreground font-semibold">{{ productInfo.initial_date ?? 'Not set' }}</span>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex items-center justify-center py-16 text-muted-foreground text-sm">
                Loading history...
            </div>

            <!-- Empty -->
            <div v-else-if="history.length === 0"
                class="flex items-center justify-center py-16 text-muted-foreground text-sm">
                No history available for this product.
            </div>

            <!-- Ledger table -->
            <div v-else class="rounded-md border overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/60 hover:bg-muted/60">
                            <TableHead class="w-24 font-semibold">Type</TableHead>
                            <TableHead class="font-semibold">Party</TableHead>
                            <TableHead class="w-32 font-semibold">Invoice #</TableHead>
                            <TableHead class="text-right w-20 font-semibold text-green-700 dark:text-green-400">IN
                            </TableHead>
                            <TableHead class="text-right w-20 font-semibold text-destructive">OUT</TableHead>
                            <TableHead class="text-right w-24 font-semibold">Balance</TableHead>
                            <TableHead class="text-right w-28 font-semibold">Date</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(entry, index) in history" :key="index" :class="[
                            entry.before_initial ? 'opacity-40' : '',
                            entry.type === 'INITIAL' ? 'bg-muted/30' : '',
                        ]">
                            <TableCell>
                                <Badge :variant="badgeVariant(entry.type)" class="text-sm">
                                    {{ entry.type }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-base max-w-xs truncate">{{ entry.party }}</TableCell>
                            <TableCell class="text-base text-muted-foreground">{{ entry.invoice_no }}</TableCell>
                            <TableCell
                                class="text-right font-mono text-base font-medium text-green-600 dark:text-green-400">
                                {{ (entry.type === 'IN' || entry.type === 'INITIAL') ? entry.qty : '' }}
                            </TableCell>
                            <TableCell class="text-right font-mono text-base font-medium text-destructive">
                                {{ entry.type === 'OUT' ? entry.qty : '' }}
                            </TableCell>
                            <TableCell class="text-right font-mono text-base font-bold">
                                {{ entry.balance }}
                            </TableCell>
                            <TableCell class="text-right text-sm text-muted-foreground whitespace-nowrap">
                                {{ entry.date }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

        </div>

        <template #footer>
            <BaseButton type="button" transactionType="cancel" @click="emit('form-closed')" />
        </template>
    </FormCard>
</template>
