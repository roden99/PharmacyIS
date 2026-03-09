<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';

const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },
    cardTitle: {
        type: String,
        default: 'Form',
    },
    productUnit: {
        type: Object,
        default: null,
    },
    transactionType: {
        type: String,
        default: 'create',
    },
});

const confirmButtonText = computed(() => {
    if (props.transactionType === 'create') return 'Save';
    if (props.transactionType === 'update') return 'Update';
    if (props.transactionType === 'delete') return 'Deactivate';
    return 'Yes';
});

const handleAlertClose = () => {
    isDialogOpen.value = false;
    if (props.transactionType === 'delete') {
        emit('form-closed')
    }
};

const isFormValidated = () => {
    if (!form.unit_name.toString().trim()) {
        toast.error('Fill up the forms properly');
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

const buttonVariants = computed(() => {
    return props.transactionType === 'create' ? 'default' : props.transactionType === 'update' ? 'default' : 'destructive';
});

const form = useForm({
    unit_name: props.productUnit?.unit_name || '',
    unit_code: props.productUnit?.unit_code || '',
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const handleSubmit = () => {
    try {
        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
}

const isDialogOpen = ref(false);

onMounted(() => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
});
</script>

<template>
    <FormCard :loading="isProcessing" :card-title="cardTitle" max-width="max-w-lg">
        <form @submit.prevent="Submit" class="space-y-4">
            <div class="w-full space-y-6">
                <BaseField>
                    <template #fieldGroups>
                        <FieldSet>
                            <FieldGroup class="rounded-lg border p-4">
                                <div class="grid w-full grid-cols-15 gap-4">
                                    <Field class="col-span-15">
                                        <FieldLabel class="font-normal">Unit Name:</FieldLabel>
                                        <Input v-model="form.unit_name" required />
                                    </Field>
                                    <Field class="col-span-15">
                                        <FieldLabel class="font-normal">Unit Code (Optional):</FieldLabel>
                                        <Input v-model="form.unit_code" />
                                    </Field>
                                </div>
                            </FieldGroup>
                        </FieldSet>
                    </template>
                </BaseField>
            </div>

            <div class="flex justify-end space-x-2">
                <BaseButton text="Cancel" variant="outline" color="secondary" type="button"
                    @click="emit('form-closed')">
                </BaseButton>
                <BaseButton :loading="isProcessing" :text="confirmButtonText" variant="default" color="primary"
                    type="button" @click="openConfirmDialog">
                </BaseButton>
            </div>
        </form>
    </FormCard>

    <BaseAlertDialog v-model:open="isDialogOpen">
        <template #alertTitle>
            <template v-if="transactionType === 'create'">
                Are you sure you want to save?
            </template>
            <template v-if="transactionType === 'update'">
                Are you sure you want to update?
            </template>
            <template v-if="transactionType === 'delete'">
                Are you sure you want to deactivate this product unit?
            </template>
        </template>
        <template #alertDescription>
            <h4 class="font-semibold text-sm mb-2">Product Unit Details:</h4>
            <div class="text-sm space-y-1">
                <p><span class="font-medium">Unit Name:</span> {{ form.unit_name || 'N/A' }}</p>
                <p v-if="form.unit_code"><span class="font-medium">Unit Code:</span> {{ form.unit_code }}</p>
            </div>
        </template>

        <template #alertFooter>
            <BaseButton text="Cancel" :disabled="isProcessing" :variant="'outline'" color="secondary" type="button"
                @click="handleAlertClose" />
            <BaseButton :text="confirmButtonText" :variant="buttonVariants" color="primary" type="button"
                @click="handleSubmit" :disabled="isProcessing" :loading="isProcessing" />
        </template>
    </BaseAlertDialog>
</template>
