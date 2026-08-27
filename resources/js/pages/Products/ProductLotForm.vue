<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import axios from 'axios';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },
    product: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    lot_number: '',
    expiration_date: null,
    quantity: 0,
});

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isCheckingLot = ref(false);
const existingLot = ref(null);
const isBusy = computed(() => props.isProcessing || isCheckingLot.value);

const handleAlertClose = () => {
    isDialogOpen.value = false;
};

const isFormValidated = () => {
    if (!form.lot_number.trim()) {
        toast.error('Please enter a lot number.');
        return false;
    }
    if (!form.expiration_date) {
        toast.error('Please select an expiration date.');
        return false;
    }
    if (form.quantity === '' || Number(form.quantity) < 0) {
        toast.error('Please enter a valid quantity.');
        return false;
    }
    return true;
};

const openConfirmDialog = async () => {
    form.clearErrors();
    if (!isFormValidated()) return;

    isCheckingLot.value = true;
    try {
        const res = await axios.get(`/products/${props.product.id}/lots/all`, {
            headers: { Accept: 'application/json' },
        });
        const lots = res.data.lots ?? [];
        existingLot.value = lots.find(l => l.lot_number === form.lot_number) ?? null;
    } catch {
        existingLot.value = null;
    } finally {
        isCheckingLot.value = false;
    }

    if (existingLot.value) {
        const current = Number(existingLot.value.quantity);
        const adding = Number(form.quantity);
        toast.warning(`Lot "${form.lot_number}" already exists`, {
            description: `Current qty: ${current}. Adding ${adding} will bring it to ${current + adding}. Continue?`,
            action: { label: 'Continue', onClick: () => { isDialogOpen.value = true; } },
        });
        return;
    }

    isDialogOpen.value = true;
};

const handleSubmit = () => {
    try {
        emit('handleSubmit', {
            ...form.data(),
            expiration_date: normalizeDate(form.expiration_date),
        });
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
            <BaseField legend="Add Product Lot" description="Record a new lot/batch for this product">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="2">
                        <div class="grid w-full grid-cols-12 gap-4">

                            <!-- Product info -->
                            <Field class="col-span-12">
                                <div class="rounded-md border bg-muted/40 px-4 py-3">
                                    <span
                                        class="text-xs uppercase tracking-wide text-muted-foreground font-medium">Product</span>
                                    <p class="text-sm font-semibold mt-0.5">
                                        {{ product?.display_name ?? product?.productname ?? '—' }}
                                    </p>
                                </div>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Lot Number: <span class="text-destructive">*</span>
                                </FieldLabel>
                                <Input v-model="form.lot_number" placeholder="e.g. LOT-2026-001" />
                                <p v-if="form.errors.lot_number" class="text-xs text-destructive mt-1">{{
                                    form.errors.lot_number }}</p>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Expiration Date: <span class="text-destructive">*</span>
                                </FieldLabel>
                                <BaseDatePick v-model="form.expiration_date" />
                                <p v-if="form.errors.expiration_date" class="text-xs text-destructive mt-1">{{
                                    form.errors.expiration_date }}</p>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Quantity:</FieldLabel>
                                <Input v-model.number="form.quantity" type="number" min="0" step="1" placeholder="0" />
                                <p v-if="form.errors.quantity" class="text-xs text-destructive mt-1">{{
                                    form.errors.quantity }}</p>
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
