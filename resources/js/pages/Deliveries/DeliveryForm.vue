<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel, FieldSet, FieldSeparator } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import axios from 'axios';
import CreateSupplier from '../Suppliers/CreateSupplier.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { Badge } from '@/components/ui/badge';
import DeliveryItemsTable from './DeliveryItemsTable.vue';



const { reverseDate, normalizeDate } = useDateFormatter();



const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },

    cardTitle: {
        type: String,
        default: 'Form',
    },

    delivery: {
        type: Object,
        default: null,
    },

    suppliers: {
        type: Array,
        default: () => []
    },

    transactionType: {
        type: String,
        default: 'create',
    },
});




const form = useForm({
    supplier_id: props.delivery?.supplier_id ? String(props.delivery.supplier_id) : '',
    invoice_date: null,
    invoice_no: props.delivery ? props.delivery.invoice_no : '',
    delivery_date: null,
});



const emit = defineEmits(['handleSubmit', 'form-closed']);

const isDialogOpen = ref(false);
const isLoading = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

// Fields: Supplier(6), Delivery No.(3), Terms(3), Invoice Date(6), Received Date(6)
const { skeletonLayout } = useFieldGroupSkeleton([6, 3, 3, 6, 6]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const handleAlertClose = () => {
    isDialogOpen.value = false;
    if (props.transactionType === 'delete') {
        emit('form-closed');
    }
};

const openConfirmDialog = () => {
    isDialogOpen.value = true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        isDialogOpen.value = false;
    }
});

const handleSubmit = () => {
    try {
        if (deliveryItems.value.length === 0) {
            toast.error('Please add at least one item before saving.');
            return;
        }
        emit('handleSubmit', {
            ...form.data(),
            supplier_id: form.supplier_id ? Number(form.supplier_id) : null,
            invoice_date: normalizeDate(form.invoice_date),
            delivery_date: normalizeDate(form.delivery_date),
            items: deliveryItems.value.map(item => ({
                product_id: Number(item.product_id),
                quantity_received: item.quantity,
                unit_price: item.unit_price,
            })),
        });

    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(async () => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
    isLoading.value = true;
    await Promise.all([
        loadSuppliers(),
        loadProducts(),
        props.transactionType === 'update' && props.delivery?.id ? loadDeliveryItems() : Promise.resolve(),
    ]);
    isLoading.value = false;
});

async function loadDeliveryItems() {
    try {
        const res = await axios.get(`/deliveries/${props.delivery.id}`, {
            headers: { Accept: 'application/json' },
        });
        deliveryItems.value = res.data.items;

        // Reset dates from the API (raw YYYY-MM-DD) since the index formats them for display
        const d = res.data.delivery;
        if (d.invoice_date) form.invoice_date = reverseDate(d.invoice_date.slice(0, 10));
        if (d.delivery_date) form.delivery_date = reverseDate(d.delivery_date.slice(0, 10));
    } catch (error) {
        console.error('Failed to load delivery items:', error);
        toast.error('Failed to load delivery items.');
    }
}






const suppliers = ref([]);
const supplierOptions = ref([]);
async function loadSuppliers(searchQuery = '') {
    // isLoading.value = true;
    try {
        const res = await axios.get('/suppliers', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        const supplierArr = Array.isArray(res.data.suppliers) ? res.data.suppliers : [];
        suppliers.value = supplierArr;
        supplierOptions.value = supplierArr.map((supplier) => ({
            value: String(supplier.id),
            label: supplier.company,
        }));
    } catch (error) {
        console.error('Failed to fetch suppliers:', error);
        toast.error('Failed to load suppliers. Please try again.');
    } finally {

    }
}




const selectedProducts = ref(null);
const itemQuantity = ref(1);
const itemPrice = ref(0);

const deliveryItems = ref([]);

const totalAmount = computed(() =>
    deliveryItems.value.reduce((sum, item) =>
        sum + Number(item.quantity) * Number(item.unit_price), 0
    ).toFixed(2)
);

const addItem = () => {
    if (!selectedProducts.value) {
        toast.error('Please select a product.');
        return;
    }
    const product = productsOptions.value.find(p => p.value === selectedProducts.value);
    deliveryItems.value.push({
        product_id: selectedProducts.value,
        product_name: product?.label ?? selectedProducts.value,
        quantity: Number(itemQuantity.value),
        unit_price: Number(itemPrice.value),
    });
    selectedProducts.value = null;
    itemQuantity.value = 1;
    itemPrice.value = 0;
};

const removeItem = (index) => {
    deliveryItems.value.splice(index, 1);
};

const products = ref([]);
const productsOptions = ref([]);
async function loadProducts(searchQuery = '', includeId = null) {
    try {


        const res = await axios.get('/products', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery, include_id: includeId },
        });

        console.log('Products response:', res.data);



        products.value = res.data.products;
        productsOptions.value = products.value.map((product) => ({
            value: String(product.id),
            label: product.display_name,
        }));


    } catch (error) {
        console.error('Failed to fetch products:', error);
        toast.error('Failed to load products. Please try again.');
    }
}




</script>

<template>
    <FormCard :loading="false" size="lg">
        <form @submit.prevent="Submit" class="space-y-4 mt-4">
            <BaseField legend="Delivery Information" description="Enter delivery details">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">


                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Supplier:</FieldLabel>
                                <BaseCombobox v-model="form.supplier_id" :options="supplierOptions"
                                    empty-message="Empty Search" width="w-full" @search="loadSuppliers">
                                    <!-- <template #create="{ close }">
                                        <CreateSupplier
                                            @supplier-created="(supplier) => { supplierOptions.push({ value: String(supplier.id), label: supplier.company }); selectedSupplier = String(supplier.id); }"
                                            @form-closed="close" />
                                    </template> -->
                                </BaseCombobox>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Invoice No.:</FieldLabel>
                                <Input v-model="form.invoice_no" required />
                            </Field>

                        </div>

                        <div class="grid w-full grid-cols-12 gap-4">

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Invoice Date:</FieldLabel>
                                <BaseDatePick v-model="form.invoice_date" class="w-32" />
                            </Field>


                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Received Date:</FieldLabel>
                                <BaseDatePick v-model="form.delivery_date" class="w-32" />
                            </Field>
                        </div>

                        <FieldSeparator />

                    </FieldGroup>

                    <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems">
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <div class="grid grid-cols-12 items-start gap-2">
                                    <Field class="col-span-7">
                                        <FieldLabel class="font-normal">Select Item:</FieldLabel>
                                        <BaseCombobox v-model="selectedProducts" :options="productsOptions"
                                            empty-message="No products found" width="w-full"
                                            @search="(q) => loadProducts(q, selectedProduct)"
                                            placeholder="Search product..." />

                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Qty</FieldLabel>
                                        <Input v-model="itemQuantity" type="number" min="0" placeholder="0" />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">UP</FieldLabel>
                                        <Input v-model="itemPrice" type="number" min="0" step="0.01"
                                            placeholder="0.00" />
                                    </Field>


                                    <Field class="col-span-1">
                                        <FieldLabel class="invisible">-</FieldLabel>
                                        <BaseButton type="button" @click="addItem" :transactionType="'add'"
                                            :disabled="isBusy" :skeleton="isLoading">
                                        </BaseButton>
                                    </Field>
                                </div>



                                <DeliveryItemsTable :items="deliveryItems" @remove="removeItem" />


                            </Field>
                        </div>



                    </FieldGroup>
                </template>
            </BaseField>
        </form>

        <template #footer>
            <div class="mr-auto flex flex-col">
                <span class="text-xs text-muted-foreground uppercase tracking-wide">Total Amount</span>
                <span class="text-lg font-bold">{{ totalAmount }}</span>
            </div>

            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading">
            </BaseButton>

            <BaseButton type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isProcessing" :disabled="isBusy" :skeleton="isLoading">
            </BaseButton>
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
