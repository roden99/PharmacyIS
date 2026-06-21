<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import BaseField from '@/components/BaseField.vue';
import BaseTable from '@/components/BaseTable.vue';
import { Button } from '@/components/ui/button';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import CreateBrand from '@/pages/Brands/CreateBrand.vue';
import axios from 'axios';
import { toast } from 'vue-sonner';



import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    salesAccount: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const showCreateCustomerModal = ref(false);
const isLoading = ref(true);

const selectedBrand = ref(null);
const brandTypesOptions = ref([]);

async function loadBrandTypes(searchQuery = '') {
    try {
        const res = await axios.get('/brands', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        brandTypesOptions.value = res.data.brands.map((b) => ({
            value: String(b.id),
            label: b.brandname,
        }));
    } catch (error) {
        toast.error('Failed to load brands. Please try again.');
    }
}

onMounted(async () => {
    await loadBrandTypes();
    isLoading.value = false;
});

</script>

<template>
    <FormCard :loading="false" size="lg">



        <BaseField legend="Account Details" :description="props.salesAccount.account_name">

            <template #fields>
                <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="1">
                    <div class="grid w-full grid-cols-12 gap-4">
                        <Field class="col-span-6">
                            <Skeleton v-if="isLoading" class="h-4 w-10 mb-1" />

                            <BaseCombobox v-model="selectedBrand" :options="brandTypesOptions"
                                empty-message="Empty Search" width="w-full" @search="loadBrandTypes"
                                :skeleton="isLoading" placeholder="Add Customer" search-placeholder="Add Customer">
                                <template #create="{ close }">
                                    <CreateBrand
                                        @brand-created="(brand) => { brandTypesOptions.push({ value: String(brand.id), label: brand.brandname }); selectedBrand = String(brand.id); }"
                                        @form-closed="close" />
                                </template>
                            </BaseCombobox>
                        </Field>
                    </div>
                </FieldGroup>
            </template>
        </BaseField>


        <BaseTable :columns="[
            { key: 'index', header: '#' },
            { key: 'display_name', header: 'NAME' },
            { key: 'phone', header: 'PHONE' },
        ]" :rows="(props.salesAccount.customers ?? []).map((c, i) => ({ ...c, index: i + 1 }))" />


        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
        </template>
    </FormCard>
</template>
