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



    cardTitle: {
        type: String,
        default: 'Form',
    },

    salesAccount: {
        type: Object,
        default: null,
    },

    transactionType: {
        type: String,
        default: 'create',
    },

});


const isSaving = ref(false);
const isLoading = ref(true);
const isBusy = computed(() => isSaving.value);
const isDialogOpen = ref(false);



const form = useForm({
    account_name: props.salesAccount?.account_name || '',
    brand_id: props.salesAccount?.brand_id || null,
});








const handleAlertClose = () => {
    isDialogOpen.value = false;

    if (props.transactionType === 'delete') {
        emit('form-closed')
    }
};


const isFormValidated = () => {
    if (!form.account_name.toString().trim()) {
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






const emit = defineEmits(['handleSubmit', 'form-closed']);


const handleSubmit = () => {
    try {
        isSaving.value = true;
        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
        isSaving.value = false;
    }
}

const closeDialog = () => {
    isDialogOpen.value = false;
    isSaving.value = false;
}

defineExpose({ closeDialog });




onMounted(async () => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
    isLoading.value = false;
});


</script>

<template>
    <FormCard size="md" :loading="isBusy">
        <form @submit.prevent="handleSubmit" class="space-y-4 mt-4">
            <BaseField legend="Account Information" description="Enter account details">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="1">
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Add Account:</FieldLabel>
                                <Input v-model="form.account_name" required />
                            </Field>
                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>
        <template #footer>

            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading">
            </BaseButton>

            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog"
                :transactionType="props.transactionType" :skeleton="isLoading" :loading="isBusy">
            </BaseButton>
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :disabled="isBusy" :loading="isBusy"
            :transaction-type="props.transactionType" @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>

</template>