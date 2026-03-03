<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { isNumberArray } from '@tanstack/vue-table';


import CreateBrand from '@/pages/Brands/CreateBrand.vue';
import UpdateBrand from '@/pages/Brands/UpdateBrand.vue';
import DeleteBrand from '@/pages/Brands/DeleteBrand.vue';


const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Brand List',
        href: '/brands',
    },
];



const props = defineProps({
    brands: {
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

const showCreateBrandModal = ref(false);

const showUpdateBrandModal = ref(false);
const showDeleteBrandModal = ref(false);
const selectedBrand = ref(null);


const handleAction = ({ type, data }) => {

    console.log('🎯 Action Clicked:', {
        actionType: type,
        brandData: data,
        timestamp: new Date().toISOString(),


    });

    switch (type) {
        case 'edit':
            console.log('📄 Edit action for:', data);
            showUpdateBrandModal.value = true;
            selectedBrand.value = data;

            break;

        case 'download':
            console.log('📥 Download action for:', data);
            break;


        case 'delete':
            showDeleteBrandModal.value = true;
            selectedBrand.value = data;


            // handleDelete(data.id);
            break;


        default:
            console.log(`❓ Unknown action "${type}" for:`, data);
    }

};




</script>


<template>

    <Head title="Brands" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Use the reactive brands data -->
            <BaseIndex IndexType="Brands" :data="props.brands"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'brandname', label: 'Brand Name' }
                ]">

                <Button variant="default" class="mr-2" @click="showCreateBrandModal = true">
                    New Brand
                </Button>

            </BaseIndex>

            <CreateBrand v-if="showCreateBrandModal" @form-closed="showCreateBrandModal = false" />



            <UpdateBrand v-if="showUpdateBrandModal" :brand="selectedBrand"
                @brand-form-closed="showUpdateBrandModal = false" />

            <DeleteBrand v-if="showDeleteBrandModal" :brand="selectedBrand"
                @brand-form-closed="showDeleteBrandModal = false" />


        </div>
    </AppLayout>
</template>