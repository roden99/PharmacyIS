<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreateCarryItem from '@/pages/CarryItems/CreateCarryItem.vue';
import EditCarryItem from '@/pages/CarryItems/EditCarryItem.vue';
import ReturnCarryItem from '@/pages/CarryItems/ReturnCarryItem.vue';
import ViewCarryItem from '@/pages/CarryItems/ViewCarryItem.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Carry Items', href: '/carry-items' },
];

const props = defineProps({
    carryItems: { required: true },
    columns: { type: Array, required: true },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() => props.columns.filter(col => col.isVisible === true));

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showReturnModal = ref(false);
const showViewModal = ref(false);
const selectedItem = ref(null);

const handleAction = ({ type, data }) => {
    if (type === 'view') {
        selectedItem.value = data;
        showViewModal.value = true;
    } else if (type === 'edit') {
        selectedItem.value = data;
        showEditModal.value = true;
    } else if (type === 'return') {
        selectedItem.value = data;
        showReturnModal.value = true;
    }
};
</script>

<template>

    <Head title="Carry Items" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="CarryItems" :data="carryItems" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction">
                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Carry Items
                </Button>
            </BaseIndex>

            <CreateCarryItem v-if="showCreateModal" @form-closed="showCreateModal = false" />

            <EditCarryItem v-if="showEditModal && selectedItem" :carry-item="selectedItem"
                @form-closed="showEditModal = false" />

            <ViewCarryItem v-if="showViewModal && selectedItem" :carry-item="selectedItem"
                @form-closed="showViewModal = false" />

            <ReturnCarryItem v-if="showReturnModal && selectedItem" :carry-item="selectedItem"
                @form-closed="showReturnModal = false" />
        </div>
    </AppLayout>
</template>
