<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import axios from 'axios';

import CreateReturnToSupplier from '@/pages/ReturnToSupplier/CreateReturnToSupplier.vue';
import EditReturnToSupplier from '@/pages/ReturnToSupplier/EditReturnToSupplier.vue';
import ViewReturnToSupplier from '@/pages/ReturnToSupplier/ViewReturnToSupplier.vue';
import DeleteReturnToSupplier from '@/pages/ReturnToSupplier/DeleteReturnToSupplier.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Return to Supplier', href: '/return-to-suppliers' },
];

const props = defineProps({
    records: { required: true },
    columns: { type: Array, required: true },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() =>
    props.columns.filter(col => col.isVisible === true)
);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const selectedRecord = ref(null);
const isDeletingId = ref(null);

const handleAction = async ({ type, data }) => {
    switch (type) {
        case 'view':
            selectedRecord.value = data;
            showViewModal.value = true;
            break;
        case 'edit':
            selectedRecord.value = data;
            showEditModal.value = true;
            break;
        case 'delete':
            selectedRecord.value = data;
            showDeleteModal.value = true;
            break;
        default:
            break;
    }
};
</script>

<template>

    <Head title="Return to Supplier" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="ReturnToSupplier" :data="records" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction">
                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Return
                </Button>
            </BaseIndex>

            <CreateReturnToSupplier v-if="showCreateModal" @form-closed="showCreateModal = false" />
            <EditReturnToSupplier v-if="showEditModal" :record="selectedRecord" @form-closed="showEditModal = false" />
            <ViewReturnToSupplier v-if="showViewModal" :record="selectedRecord" @form-closed="showViewModal = false" />
            <DeleteReturnToSupplier v-if="showDeleteModal" :record="selectedRecord"
                @form-closed="showDeleteModal = false" />
        </div>
    </AppLayout>
</template>
