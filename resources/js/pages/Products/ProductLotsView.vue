<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { Input } from '@/components/ui/input';
import { useForm, router } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { Layers, Trash2, AlertTriangle, Pencil } from 'lucide-vue-next';
import axios from 'axios';

const { normalizeDate, reverseDate } = useDateFormatter();

const props = defineProps({
    product: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

// ── Lots list ─────────────────────────────────────────────────────────────────
const isLoading = ref(true);
const lots = ref([]);
const isDeleting = ref(false);
const deleteTarget = ref(null);
const showDeleteDialog = ref(false);

const loadLots = async () => {
    try {
        const res = await axios.get(`/products/${props.product.id}/lots/all`, {
            headers: { Accept: 'application/json' },
        });
        lots.value = res.data.lots ?? [];
    } catch {
        toast.error('Failed to load lots.');
    } finally {
        isLoading.value = false;
    }
};

const openDelete = (lot) => {
    deleteTarget.value = lot;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(`/products/${props.product.id}/lots/${deleteTarget.value.id}`, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: async () => {
            toast.success('Lot removed.');
            showDeleteDialog.value = false;
            deleteTarget.value = null;
            await loadLots();
        },
        onError: () => toast.error('Failed to remove lot.'),
        onFinish: () => { isDeleting.value = false; },
    });
};

// ── Add lot form ──────────────────────────────────────────────────────────────
const isProcessing = ref(false);
const isFormDialogOpen = ref(false);

const form = useForm({
    lot_number: '',
    expiration_date: null,
    quantity: 0,
});

const isFormValidated = () => {
    if (!form.lot_number.trim()) { toast.error('Please enter a lot number.'); return false; }
    if (!form.expiration_date) { toast.error('Please select an expiration date.'); return false; }
    if (Number(form.quantity) < 0) { toast.error('Quantity cannot be negative.'); return false; }
    return true;
};

const openFormDialog = () => {
    form.clearErrors();
    if (!isFormValidated()) return;

    const existing = lots.value.find(l => l.lot_number.trim() === form.lot_number.trim());
    if (existing) {
        const current = Number(existing.quantity);
        const adding = Number(form.quantity);
        toast.warning(`Lot "${form.lot_number}" already exists`, {
            description: `Current qty: ${current}. Adding ${adding} will bring it to ${current + adding}. Continue?`,
            action: { label: 'Continue', onClick: () => { isFormDialogOpen.value = true; } },
        });
        return;
    }

    isFormDialogOpen.value = true;
};

const submitLot = () => {
    isProcessing.value = true;
    router.post(`/products/${props.product.id}/lots`, {
        ...form.data(),
        expiration_date: normalizeDate(form.expiration_date),
    }, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: async () => {
            toast.success('Lot added successfully!');
            isFormDialogOpen.value = false;
            form.reset();
            await loadLots();
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to add lot.', { description: errors[firstKey] });
        },
        onFinish: () => { isProcessing.value = false; },
    });
};

const fmt = (val) =>
    Number(val ?? 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

// ── Edit lot ──────────────────────────────────────────────────────────────────
const editTarget = ref(null);
const isEditProcessing = ref(false);
const showEditDialog = ref(false);
const editForm = ref({ lot_number: '', expiration_date: null });

const openEdit = (lot) => {
    editTarget.value = lot;
    editForm.value = { lot_number: lot.lot_number, expiration_date: reverseDate(lot.expiration_raw) };
};

const cancelEdit = () => {
    editTarget.value = null;
    editForm.value = { lot_number: '', expiration_date: null };
};

const openEditDialog = () => {
    if (!editForm.value.lot_number.trim()) { toast.error('Please enter a lot number.'); return; }
    if (!editForm.value.expiration_date) { toast.error('Please select an expiration date.'); return; }
    showEditDialog.value = true;
};

const submitEdit = async () => {
    if (!editTarget.value) return;
    isEditProcessing.value = true;
    try {
        await axios.patch(`/products/${props.product.id}/lots/${editTarget.value.id}`, {
            lot_number: editForm.value.lot_number,
            expiration_date: normalizeDate(editForm.value.expiration_date),
        }, { headers: { Accept: 'application/json' } });
        toast.success('Lot updated successfully!');
        showEditDialog.value = false;
        cancelEdit();
        await loadLots();
    } catch (error) {
        toast.error(error.response?.data?.message ?? 'Failed to update lot.');
    } finally {
        isEditProcessing.value = false;
    }
};

onMounted(loadLots);
</script>

<template>
    <FormCard size="2xl" :loading="isProcessing">
        <div class="mt-4 space-y-4">

            <!-- Header -->
            <div class="flex items-center justify-between gap-4 rounded-md border bg-muted/40 px-4 py-3">
                <div class="flex items-center gap-2 min-w-0">
                    <Layers class="h-4 w-4 text-indigo-500 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium">Product</p>
                        <p class="text-sm font-semibold truncate">
                            {{ product?.display_name ?? product?.productname ?? '—' }}
                        </p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium">Total Lots</p>
                    <p class="text-sm font-bold">{{ lots.length }}</p>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">

                <!-- Left: Lots list -->
                <div class="col-span-7">
                    <BaseField legend="Existing Lots" description="Lot/batch records for this product">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-rows="2" :skeleton-cols="3">
                                <div v-if="!isLoading && lots.length === 0"
                                    class="flex flex-col items-center justify-center py-10 text-muted-foreground gap-2">
                                    <Layers class="h-8 w-8 opacity-30" />
                                    <p class="text-sm">No lots recorded yet.</p>
                                </div>

                                <div v-else class="overflow-y-auto max-h-72 rounded-md border">
                                    <table class="w-full text-xs">
                                        <thead class="sticky top-0 bg-muted/80 z-10">
                                            <tr>
                                                <th class="text-left px-3 py-2 font-semibold">Lot No.</th>
                                                <th class="text-left px-3 py-2 font-semibold">Expiry</th>
                                                <th class="text-right px-3 py-2 font-semibold">Qty</th>
                                                <th class="w-16 px-2 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="lot in lots" :key="lot.id"
                                                class="border-t hover:bg-muted/30 transition-colors"
                                                :class="lot.is_expired ? 'bg-red-50 dark:bg-red-950/20' : ''">
                                                <td class="px-3 py-2 font-medium font-mono">{{ lot.lot_number }}</td>
                                                <td class="px-3 py-2">
                                                    <div class="flex items-center gap-1">
                                                        <span
                                                            :class="lot.is_expired ? 'text-red-600 font-semibold' : ''">
                                                            {{ lot.expiration_date }}
                                                        </span>
                                                        <AlertTriangle v-if="lot.is_expired"
                                                            class="h-3 w-3 text-red-500 shrink-0" />
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono">{{ fmt(lot.quantity) }}</td>
                                                <td class="px-2 py-2 text-center">
                                                    <div class="flex items-center justify-center gap-1.5">
                                                        <button @click="openEdit(lot)"
                                                            class="text-muted-foreground hover:text-primary transition-colors">
                                                            <Pencil class="h-3.5 w-3.5" />
                                                        </button>
                                                        <button @click="openDelete(lot)"
                                                            class="text-muted-foreground hover:text-destructive transition-colors">
                                                            <Trash2 class="h-3.5 w-3.5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Add / Edit lot form -->
                <div class="col-span-5">

                    <!-- Edit mode -->
                    <BaseField v-if="editTarget" legend="Edit Lot" description="Update lot number or expiry date">
                        <template #fields>
                            <FieldGroup :skeleton-rows="1" :skeleton-cols="1">
                                <div class="grid w-full grid-cols-12 gap-3">

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Lot Number: <span
                                                class="text-destructive">*</span></FieldLabel>
                                        <Input v-model="editForm.lot_number" placeholder="e.g. LOT-2026-001" />
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Expiration Date: <span
                                                class="text-destructive">*</span></FieldLabel>
                                        <BaseDatePick v-model="editForm.expiration_date" />
                                    </Field>

                                    <Field class="col-span-12 mt-1">
                                        <div class="flex gap-2">
                                            <BaseButton type="button" transactionType="cancel"
                                                :disabled="isEditProcessing" @click="cancelEdit" class="flex-1" />
                                            <BaseButton type="button" transactionType="update"
                                                :loading="isEditProcessing" :disabled="isEditProcessing"
                                                @click="openEditDialog" class="flex-1" />
                                        </div>
                                    </Field>

                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>

                    <!-- Add mode -->
                    <BaseField v-else legend="Add New Lot" description="Record a new lot for this product">
                        <template #fields>
                            <FieldGroup :skeleton-rows="1" :skeleton-cols="1">
                                <div class="grid w-full grid-cols-12 gap-3">

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Lot Number: <span
                                                class="text-destructive">*</span></FieldLabel>
                                        <Input v-model="form.lot_number" placeholder="e.g. LOT-2026-001" />
                                        <p v-if="form.errors.lot_number" class="text-xs text-destructive mt-0.5">
                                            {{ form.errors.lot_number }}
                                        </p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Expiration Date: <span
                                                class="text-destructive">*</span></FieldLabel>
                                        <BaseDatePick v-model="form.expiration_date" />
                                        <p v-if="form.errors.expiration_date" class="text-xs text-destructive mt-0.5">
                                            {{ form.errors.expiration_date }}
                                        </p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Quantity:</FieldLabel>
                                        <Input v-model.number="form.quantity" type="number" min="0" step="1"
                                            placeholder="0" />
                                        <p v-if="form.errors.quantity" class="text-xs text-destructive mt-0.5">
                                            {{ form.errors.quantity }}
                                        </p>
                                    </Field>

                                    <Field class="col-span-12 mt-1">
                                        <BaseButton type="button" transactionType="create" :loading="isProcessing"
                                            :disabled="isProcessing" @click="openFormDialog" class="w-full" />
                                    </Field>

                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>
        </div>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
        </template>

        <!-- Confirm add lot -->
        <BaseAlertDialog v-model:open="isFormDialogOpen" :loading="isProcessing" transaction-type="create"
            @cancel="isFormDialogOpen = false" @confirm="submitLot" />

        <!-- Confirm edit lot -->
        <BaseAlertDialog v-model:open="showEditDialog" :loading="isEditProcessing" transaction-type="update"
            @cancel="showEditDialog = false" @confirm="submitEdit" />

        <!-- Confirm delete lot -->
        <BaseAlertDialog v-model:open="showDeleteDialog" :loading="isDeleting" transaction-type="delete"
            @cancel="showDeleteDialog = false" @confirm="confirmDelete" />
    </FormCard>
</template>
