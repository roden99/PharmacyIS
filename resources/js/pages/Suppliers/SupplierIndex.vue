<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';
// import Create from '@/pages/Members/Create.vue';
// import Update from '@/pages/Members/Update.vue';
// import Delete from '@/pages/Members/Delete.vue';



const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Supplier List',
        href: '/suppliers',
    },
];

const props = defineProps({
    suppliers: {
        type: Array,
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

const showCreateMemberModal = ref(false);
const showUpdateMemberModal = ref(false);
const showDeleteMemberModal = ref(false);
const selectedMember = ref(null);


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        memberData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateMemberModal.value = true;
            selectedMember.value = data;

            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteMemberModal.value = true;
            selectedMember.value = data;
            console.log('🗑️ Delete action for:', data);


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};




</script>
<template>

    <Head title="Members" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive suppliers data -->
            <BaseIndex IndexType="Suppliers" :data="props.suppliers"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction">

                <Button variant="default" class="mr-2" @click="showCreateMemberModal = true">
                    New Supplier
                </Button>

            </BaseIndex>

            <!-- <Create v-if="showCreateMemberModal" @member-form-closed="showCreateMemberModal = false" />

            <Update v-if="showUpdateMemberModal" :member="selectedMember"
                @member-form-closed="showUpdateMemberModal = false" />

            <Delete v-if="showDeleteMemberModal" :member="selectedMember"
                @member-form-closed="showDeleteMemberModal = false" /> -->


        </div>
    </AppLayout>
</template>