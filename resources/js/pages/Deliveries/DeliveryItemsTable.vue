<script setup>
import { X } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['remove']);
</script>

<template>
    <div class="max-h-64 overflow-y-auto rounded-md border">
        <Table>
            <TableCaption>List of delivery items</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead>Item Name</TableHead>
                    <TableHead class="text-center w-24">Qty</TableHead>
                    <TableHead class="text-center w-28">UP</TableHead>
                    <TableHead class="text-center w-28">Amount</TableHead>
                    <TableHead class="w-8" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="items.length === 0">
                    <TableCell colspan="5" class="text-center text-muted-foreground py-4">
                        No items added yet.
                    </TableCell>
                </TableRow>
                <TableRow v-for="(item, index) in items" :key="index">
                    <TableCell class="whitespace-normal break-words min-w-0">{{ item.product_name }}</TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.quantity" type="number" min="1" class="w-20 text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.unit_price" type="number" min="0" step="0.01"
                            class="w-24 text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-right">{{ (item.quantity * Number(item.unit_price)).toFixed(2) }}</TableCell>
                    <TableCell class="text-center">
                        <button type="button" @click="emit('remove', index)" class="text-destructive hover:opacity-70">
                            <X class="h-4 w-4" />
                        </button>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
