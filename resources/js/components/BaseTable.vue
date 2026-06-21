<script setup>
import { computed, ref } from 'vue';
import {
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { valueUpdater } from '@/lib/utils';

/**
 * columns:  Array<{ key: string, header: string }>
 * rows:     Array<Record<string, any>>
 * pageSize: number (default 10)
 */
const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    pageSize: {
        type: Number,
        default: 10,
    },
});

const searchQuery = ref('');

const columnDefs = computed(() =>
    props.columns.map((col) => ({
        accessorKey: col.key,
        header: col.header,
        cell: ({ row }) => row.getValue(col.key) ?? '—',
    }))
);

const globalFilter = ref('');

const table = useVueTable({
    get data() { return props.rows; },
    get columns() { return columnDefs.value; },
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    globalFilterFn: 'includesString',
    state: {
        get globalFilter() { return globalFilter.value; },
    },
    onGlobalFilterChange: (val) => { globalFilter.value = val; },
    initialState: {
        pagination: { pageSize: props.pageSize },
    },
});

const handleSearch = (e) => {
    globalFilter.value = e.target.value;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar: filter slot -->
        <div v-if="$slots.filter" class="flex items-end gap-2">
            <slot name="filter" />
        </div>

        <!-- Table -->
        <div class="rounded-md border overflow-hidden">
            <div class="overflow-auto max-h-[50vh]">
                <Table class="min-w-full">
                    <TableHeader class="sticky top-0 bg-background z-10 shadow-sm">
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead v-for="header in headerGroup.headers" :key="header.id" class="text-left">
                                {{ header.column.columnDef.header }}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows.length">
                            <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="text-left">
                                    <slot :name="cell.column.id" :row="row.original" :value="cell.getValue()">
                                        {{ cell.getValue() }}
                                    </slot>
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableRow v-else>
                            <TableCell :colspan="props.columns.length" class="h-24 text-center text-muted-foreground">
                                No results.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Pagination + Search -->
        <div class="flex items-center justify-between gap-2 py-2 text-sm text-muted-foreground">
            <span>
                Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }}
                &mdash; {{ table.getFilteredRowModel().rows.length }} record(s)
            </span>
            <div class="flex items-center gap-2">
                <Input class="max-w-xs" placeholder="Search..." :value="globalFilter" @input="handleSearch" />
                <Button variant="outline" size="sm" :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()">
                    Previous
                </Button>
                <Button variant="outline" size="sm" :disabled="!table.getCanNextPage()" @click="table.nextPage()">
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>
