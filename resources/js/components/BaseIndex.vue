<script setup>
import GenericTable from '@/components/GenericTable.vue';
import { Button } from '@/components/ui/button';
import { FlexRender } from '@tanstack/vue-table';
import { CircleDot, Circle, CircleAlert } from 'lucide-vue-next';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious
} from '@/components/ui/pagination';



// Helper function to convert value to uppercase string
function toupper(val) {
    return typeof val === 'string' ? val.toUpperCase() : val;
}

import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

import {
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';

import { ChevronDown } from 'lucide-vue-next';
import { computed, h, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

import { Input } from '@/components/ui/input';
import { valueUpdater } from '@/lib/utils';

const props = defineProps({
    IndexType: {
        type: String,
        required: true,
    },

    columnDefs: {
        type: Array,
        required: true,
    },

    selectOptions: {
        type: Array,
        default: () => [],
    },
    selectModelValue: {
        type: String,
        default: '',
    },

    //To be used by the table library
    data: { type: [Array, Object], required: true },

    // ✅ ADD: Custom actions prop (optional)
    customActions: {
        type: Array,
        default: () => []
    },

    // ✅ ADD: Server-side pagination props
    paginationData: {
        type: Object,
        default: null
    }
});

// ✅ ADD: Define actions based on IndexType
const getActionsForIndex = (indexType) => {
    const actionMap = {
        'Members': ['verifyEligibility', 'edit', 'delete'],
        'Products': ['view', 'edit', 'copy'],
        'Orders': ['view', 'download', 'add'],
        'Patients': ['view', 'edit', 'delete'],
        'Users': ['view', 'edit', 'delete'],
        'Categories': ['edit', 'copy', 'delete'],
        'ClaimEligibility': ['view', 'edit', 'delete'],
        'Accreditations': ['view', 'download', 'delete'],
        // Add more index types as needed
    };

    return actionMap[indexType] || ['view', 'edit', 'delete']; // Default actions
};

// ✅ Determine which actions to use
const tableActions = computed(() => {
    // Use custom actions if provided, otherwise use index-based actions
    return props.customActions.length > 0
        ? props.customActions
        : getActionsForIndex(props.IndexType);
});


const emit = defineEmits(['update:selectModelValue', 'action']);

// ✅ Computed properties to handle both Array and paginated Object data
const tableData = computed(() => {
    // If data is an object with 'data' property (paginated), use data.data
    if (props.data && typeof props.data === 'object' && props.data.data) {
        return props.data.data;
    }
    // Otherwise, assume it's a simple array
    return Array.isArray(props.data) ? props.data : [];
});

const paginationInfo = computed(() => {
    // Use paginationData prop if provided, otherwise check if data is paginated object
    if (props.paginationData) {
        return props.paginationData;
    }
    if (props.data && typeof props.data === 'object' && props.data.current_page) {
        return props.data;
    }
    return null;
});

const hasServerPagination = computed(() => {
    return paginationInfo.value !== null;
});

// Server-side pagination methods
const goToPage = (page) => {
    if (hasServerPagination.value && page >= 1 && page <= paginationInfo.value.last_page) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('page', page);
        router.get(currentUrl.pathname + currentUrl.search);
    }
};

const previousPage = () => {
    if (hasServerPagination.value && paginationInfo.value.current_page > 1) {
        goToPage(paginationInfo.value.current_page - 1);
    }
};

const nextPage = () => {
    if (hasServerPagination.value && paginationInfo.value.current_page < paginationInfo.value.last_page) {
        goToPage(paginationInfo.value.current_page + 1);
    }
};

const firstPage = () => {
    if (hasServerPagination.value) {
        goToPage(1);
    }
};

const lastPage = () => {
    if (hasServerPagination.value) {
        goToPage(paginationInfo.value.last_page);
    }
};

// Generate page numbers for pagination
const visiblePageNumbers = computed(() => {
    if (!hasServerPagination.value) return [];

    const current = paginationInfo.value.current_page;
    const last = paginationInfo.value.last_page;
    const pages = [];

    // Show up to 5 pages at a time
    let start = Math.max(1, current - 2);
    let end = Math.min(last, current + 2);

    // Adjust if we're near the beginning or end
    if (end - start < 4) {
        if (start === 1) {
            end = Math.min(last, start + 4);
        } else if (end === last) {
            start = Math.max(1, end - 4);
        }
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

// Server-side search functionality
const searchQuery = ref('');
let searchTimeout = null;
const performServerSearch = (searchTerm) => {
    const currentUrl = new URL(window.location.href);

    // Attach search term
    if (searchTerm && searchTerm.trim()) {
        currentUrl.searchParams.set('search', searchTerm.trim());
    } else {
        currentUrl.searchParams.delete('search');
    }

    // Attach column
    if (selectValue.value) {
        currentUrl.searchParams.set('column', selectValue.value);
    } else {
        currentUrl.searchParams.delete('column');
    }

    // Reset to page 1 on new search
    currentUrl.searchParams.delete('page');

    router.get(currentUrl.pathname + currentUrl.search, {}, { preserveState: true });
};


const handleServerSearch = (value) => {
    searchQuery.value = value;

    // Clear previous timeout
    clearTimeout(searchTimeout);

    // Only search if more than 3 characters or if clearing search
    if (value.length >= 3 || value.length === 0) {
        // Debounce search requests
        searchTimeout = setTimeout(() => {
            performServerSearch(value);
        }, 500); // 500ms delay
    }
};

const clearSearch = () => {
    searchQuery.value = '';
    performServerSearch('');
};

const selectValue = ref(props.selectModelValue ||
    (props.selectOptions.length > 0 ? props.selectOptions[0].value : ''));


watch(selectValue, (val) => {
    emit('update:selectModelValue', val);
});



const columns = [
    ...props.columnDefs
        .map(col => ({
            header: col.header ?? col.accessorKey,
            cell: col.cell ?? (({ row }) => row.getValue(col.accessorKey)),
            accessorKey: col.accessorKey,
        })),


    {
        id: 'actions',
        header: 'Actions',
        enableHiding: false,
        cell: ({ row }) => {
            // ✅ Pass the computed actions to GenericTable
            return h(
                'div',
                { class: 'relative' },
                h(GenericTable, {
                    row: row.original,
                    actions: tableActions.value, // Pass the computed actions
                    onExpand: row.toggleExpanded,
                    onAction: (actionData) => emit('action', actionData), // Forward action events
                }),
            );
        },
    },
];

// You can log props.data here if needed
onMounted(() => {
    // console.log('BaseIndex data:', props.data);
    // console.log('BaseIndex columns:', props.columnDefs);
    // console.log('Mapped columns:', columns);

    // Initialize search query from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam) {
        searchQuery.value = searchParam;
    }
});

const sorting = ref([]);
const columnFilters = ref([]);
const columnVisibility = ref({});
const rowSelection = ref({});
const expanded = ref({});

const table = useVueTable({
    get data() {
        return tableData.value;
    },
    columns: columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
    onExpandedChange: (updaterOrValue) => valueUpdater(updaterOrValue, expanded),
    initialState: {
        pagination: {
            pageSize: hasServerPagination.value ? tableData.value.length : 100,
        },
    },
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
        get expanded() {
            return expanded.value;
        },
    },
});


const hasFilteredMembers = computed(() => {
    const memberFilter = table.getColumn('member')?.getFilterValue();
    if (!memberFilter) return true; // Show all if no filter

    const filteredRows = table.getFilteredRowModel().rows;
    return filteredRows.length > 0;
});

watch(selectValue, (val, oldVal) => {
    emit('update:selectModelValue', val);

    // Reset the filter value for the previous column
    if (oldVal && table.getColumn(oldVal)) {
        table.getColumn(oldVal).setFilterValue('');
    }
});

</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-1 rounded-xl p-1">
        <div class="ml-1 w-full">
            <!-- <span class="text-base font-semibold">{{ props.IndexType }}</span> -->
            <div class="flex items-center gap-2 py-2">
                <slot>
                    <Button variant="default" class="mr-2">Create</Button>
                </slot>

                <div class="ml-auto flex items-center gap-2">
                    <Select v-model="selectValue">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="Select an option" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="option in props.selectOptions" :key="option.value"
                                    :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>

                    <Input class="max-w-sm" placeholder="Search..." v-model="searchQuery"
                        @input="handleServerSearch($event.target.value)" />

                    <!-- <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline"> Columns
                                <ChevronDown class="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuCheckboxItem
                                v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                                :key="column.id" class="capitalize" :model-value="column.getIsVisible()"
                                @update:model-value="
                                    (value) => {
                                        column.toggleVisibility(!!value);
                                    }
                                ">
                                {{ column.id }}
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu> -->
                </div>
            </div>


            <div class="rounded-md border overflow-hidden max-h-[75vh] w-full">
                <div class="overflow-auto max-h-[75vh]">
                    <Table class="min-w-full">
                        <TableHeader class="sticky top-0 bg-background z-20 shadow-sm">
                            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <TableHead v-for="header in headerGroup.headers" :key="header.id"
                                    class="border-b text-left">
                                    <FlexRender v-if="typeof header.column.columnDef.header === 'function'"
                                        :render="header.column.columnDef.header" :props="header.getContext()" />
                                    <span v-else>
                                        {{ header.column.columnDef.header }}
                                    </span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="table.getRowModel().rows?.length">
                                <template v-for="row in table.getRowModel().rows" :key="row.id">
                                    <TableRow :data-state="row.getIsSelected() && 'selected'">
                                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id"
                                            class="capitalize text-left">

                                            <!-- 
                                      {{ toupper(cell.getValue()) }} -->

                                            <!-- <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" /> -->

                                            <!-- Check if column is gender -->
                                            <template v-if="cell.column.id === 'sex'">
                                                <div class="flex items-center gap-2">
                                                    <Circle v-if="cell.getValue() === 'M'"
                                                        class="h-4 w-4 text-blue-500" />
                                                    <CircleDot v-else class="h-4 w-4 text-pink-500" />

                                                </div>
                                            </template>

                                            <!-- For other columns, use FlexRender -->
                                            <template v-else>
                                                <FlexRender :render="cell.column.columnDef.cell"
                                                    :props="cell.getContext()" />
                                            </template>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="row.getIsExpanded()">
                                        <TableCell :colspan="row.getAllCells().length">
                                            {{ JSON.stringify(row.original) }}
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </template>

                            <TableRow v-else>
                                <TableCell :colspan="columns.length" class="h-24 text-center"> No results. </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Server-side pagination -->
            <div v-if="hasServerPagination" class="mt-4 space-y-4">
                <!-- Pagination Info -->
                <!-- <div class="text-sm text-muted-foreground text-center">
                    Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} of {{ paginationInfo.total }} records
                    (Page {{
                        paginationInfo.current_page }} of {{ paginationInfo.last_page }})
                </div> -->

                <!-- Pagination Controls -->
                <div class="flex justify-center">
                    <Pagination :total="paginationInfo.last_page" :sibling-count="1" :show-edges="true"
                        :default-page="paginationInfo.current_page" @update:page="goToPage">
                        <PaginationContent>
                            <!-- First Page -->
                            <PaginationFirst v-if="paginationInfo.current_page > 3" @click="firstPage" />

                            <!-- Previous Page -->
                            <PaginationPrevious :disabled="paginationInfo.current_page <= 1" @click="previousPage" />

                            <!-- Page Numbers -->
                            <template v-for="pageNum in visiblePageNumbers" :key="pageNum">
                                <PaginationItem :value="pageNum" :is-active="pageNum === paginationInfo.current_page"
                                    @click="goToPage(pageNum)">
                                    {{ pageNum }}
                                </PaginationItem>
                            </template>

                            <!-- Ellipsis -->
                            <PaginationEllipsis
                                v-if="paginationInfo.last_page > 5 && paginationInfo.current_page < paginationInfo.last_page - 2" />

                            <!-- Next Page -->
                            <PaginationNext :disabled="paginationInfo.current_page >= paginationInfo.last_page"
                                @click="nextPage" />

                            <!-- Last Page -->
                            <PaginationLast v-if="paginationInfo.current_page < paginationInfo.last_page - 2"
                                @click="lastPage" />
                        </PaginationContent>
                    </Pagination>
                </div>
            </div>

            <!-- Client-side pagination (fallback) -->
            <div v-else class="flex justify-center space-x-2 py-4">
                <Button variant="outline" size="sm" :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"> Previous
                </Button>
                <Button variant="outline" size="sm" :disabled="!table.getCanNextPage()" @click="table.nextPage()"> Next
                </Button>
            </div>
        </div>
    </div>
</template>