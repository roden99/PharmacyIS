<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';


import CreateDrugForm from '@/pages/DrugForms/CreateDrugForm.vue';
import UpdateDrugForm from '@/pages/DrugForms/UpdateDrugForm.vue';
import DeleteDrugForm from '@/pages/DrugForms/DeleteDrugForm.vue';


const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Drug Form List',
        href: '/drugforms',
    },
];



const props = defineProps({
    drugforms: {
        // type: Array,
        required: true,
    },

    columns: {
        type: Array,
        required: true,
    },

});


const selectOptions = props.columns.filter(col => col.isParameter === true).map((s) => ({
    value: s.accessorKey,
    label: s.header,
}))
const selectModelValue = ref(
    selectOptions.length > 0 ? selectOptions[0].value : ''
);

const showCreateDrugFormModal = ref(false);

const showUpdateDrugFormModal = ref(false);
const showDeleteDrugFormModal = ref(false);
const selectedDrugForm = ref(null);


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        drugformData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateDrugFormModal.value = true;
            selectedDrugForm.value = data;

            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteDrugFormModal.value = true;
            selectedDrugForm.value = data;


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};




</script>


<template>

    <Head title="Drug Forms" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive drugforms data -->
            <BaseIndex IndexType="DrugForms" :data="props.drugforms"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'drugformname', label: 'Drug Form Name' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateDrugFormModal = true">
                    New Drug Form
                </Button>

            </BaseIndex>

            <CreateDrugForm v-if="showCreateDrugFormModal" @form-closed="showCreateDrugFormModal = false"
                @drugform-created="() => { showCreateDrugFormModal = false; router.reload({ only: ['drugforms'] }); }" />


            <UpdateDrugForm v-if="showUpdateDrugFormModal" :drugform="selectedDrugForm"
                @drugform-form-closed="showUpdateDrugFormModal = false" />

            <DeleteDrugForm v-if="showDeleteDrugFormModal" :drugform="selectedDrugForm"
                @drugform-form-closed="showDeleteDrugFormModal = false" />


        </div>
    </AppLayout>
</template>
