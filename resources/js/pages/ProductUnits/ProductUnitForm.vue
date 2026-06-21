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
    <FormCard :loading="isProcessing" size="md">
        <form @submit.prevent="Submit" class="space-y-4 mt-4">
            <BaseField legend="Product Unit Information" description="Enter product unit details">
                <template #fields>
                    <FieldGroup>
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Unit Name:</FieldLabel>
                                <Input v-model="form.unit_name" required />
                            </Field>
                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>
        <template #footer>
            <BaseButton type="button" :disabled="isProcessing" @click="emit('form-closed')" transactionType="cancel">
            </BaseButton>
            <BaseButton type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isProcessing" :disabled="isProcessing">
            </BaseButton>
        </template>
        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
