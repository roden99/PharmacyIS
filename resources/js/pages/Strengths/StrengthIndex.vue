<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';


import CreateStrength from '@/pages/Strengths/CreateStrength.vue';
import UpdateStrength from '@/pages/Strengths/UpdateStrength.vue';
import DeleteStrength from '@/pages/Strengths/DeleteStrength.vue';


const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Strength List',
        href: '/strengths',
    },
];



const props = defineProps({
    strengths: {
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

const showCreateStrengthModal = ref(false);

const showUpdateStrengthModal = ref(false);
const showDeleteStrengthModal = ref(false);
const selectedStrength = ref(null);


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        strengthData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateStrengthModal.value = true;
            selectedStrength.value = data;

            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteStrengthModal.value = true;
            selectedStrength.value = data;


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};




</script>


<template>

    <Head title="Strengths" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive strengths data -->
            <BaseIndex IndexType="Strengths" :data="props.strengths"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'strengthname', label: 'Strength Name' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateStrengthModal = true">
                    New Strength
                </Button>

            </BaseIndex>

            <CreateStrength v-if="showCreateStrengthModal" @form-closed="showCreateStrengthModal = false"
                @strength-created="() => { showCreateStrengthModal = false; router.reload({ only: ['strengths'] }); }" />


            <UpdateStrength v-if="showUpdateStrengthModal" :strength="selectedStrength"
                @strength-form-closed="showUpdateStrengthModal = false" />

            <DeleteStrength v-if="showDeleteStrengthModal" :strength="selectedStrength"
                @strength-form-closed="showDeleteStrengthModal = false" />


        </div>
    </AppLayout>
</template>
