<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';

const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },
    cardTitle: {
        type: String,
        default: 'Initial Inventory',
    },
    product: {
        type: Object,
        default: null,
    },
    transactionType: {
        type: String,
        default: 'create',
    },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    product_qty: props.product?.product_qty ?? 0,
});

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

const handleAlertClose = () => {
    isDialogOpen.value = false;
    emit('form-closed');
};

const isFormValidated = () => {
    if (form.product_qty === '' || form.product_qty < 0) {
        toast.error('Please enter a valid quantity.');
        return false;
    }
    return true;
};

const openConfirmDialog = () => {
    form.clearErrors();
    if (!isFormValidated()) return false;
    isDialogOpen.value = true;
    return true;
};

const handleSubmit = () => {
    try {
        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(() => {
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="isProcessing" size="md">
        <form @submit.prevent class="space-y-4 mt-4">
            <BaseField legend="Initial Inventory" description="Set the initial inventory quantity for this product">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="1">
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Product:</FieldLabel>
                                <span class="text-sm font-medium">{{ product?.display_name ?? product?.productname ??
                                    '—' }}</span>
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Initial Quantity:</FieldLabel>
                                <Input v-model.number="form.product_qty" type="number" min="0" required />
                            </Field>

                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>

        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog" transactionType="create"
                :loading="isProcessing" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="create"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
