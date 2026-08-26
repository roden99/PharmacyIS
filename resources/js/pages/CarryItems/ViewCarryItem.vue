<script setup>
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

const props = defineProps({
    carryItem: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const items = ref([]);
const returns = ref([]);

onMounted(async () => {
    try {
        const res = await axios.get(`/carry-items/${props.carryItem.id}`, {
            headers: { Accept: 'application/json' },
        });
        items.value = res.data.items ?? [];
        returns.value = res.data.returns ?? [];
    } catch (e) {
        toast.error('Failed to load carry item details.');
        console.error(e);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isLoading" card-title="Carry Item Details" size="3xl">
        <div class="space-y-4 mt-2">
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-muted-foreground">Sales Agent:</span>
                    <span class="ml-2 font-medium">{{ carryItem.sales_agent_name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Carry Date:</span>
                    <span class="ml-2 font-medium">{{ carryItem.carry_date ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Reference No.:</span>
                    <span class="ml-2 font-medium">{{ carryItem.reference_number || '—' }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Notes:</span>
                    <span class="ml-2 font-medium">{{ carryItem.notes || '—' }}</span>
                </div>
            </div>

            <div class="rounded-md border overflow-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Product</TableHead>
                            <TableHead class="w-32">Lot No.</TableHead>
                            <TableHead class="text-center w-28">Quantity</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="!isLoading && items.length === 0">
                            <TableCell colspan="3" class="text-center text-muted-foreground py-4">No items.</TableCell>
                        </TableRow>
                        <TableRow v-for="item in items" :key="item.id">
                            <TableCell>{{ item.product_name }}</TableCell>
                            <TableCell class="text-muted-foreground text-sm">{{ item.lot_number }}</TableCell>
                            <TableCell class="text-center font-medium">{{ item.quantity }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Return history -->
            <div v-if="returns.length > 0">
                <p class="text-sm font-medium mb-1 text-muted-foreground">Return History</p>
                <div class="rounded-md border overflow-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead class="text-center w-28">Qty Returned</TableHead>
                                <TableHead class="w-32">Return Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(r, i) in returns" :key="i">
                                <TableCell>{{ r.product_name }}</TableCell>
                                <TableCell class="text-center font-medium text-emerald-600">{{ r.quantity }}</TableCell>
                                <TableCell class="text-muted-foreground text-sm">{{ r.return_date }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
        </template>
    </FormCard>
</template>
