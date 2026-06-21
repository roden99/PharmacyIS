<script setup>
import { computed } from 'vue';
import { AlertDialog, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Save, Trash2, TriangleAlert, Pencil } from 'lucide-vue-next';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    transactionType: {
        type: String,
        default: 'create'
    }
});

const dialogTitle = computed(() => {
    if (props.transactionType === 'create') return 'Are you sure you want to save?';
    if (props.transactionType === 'update') return 'Are you sure you want to update?';
    if (props.transactionType === 'delete') return 'Are you sure you want to delete?';
    return 'Confirm action?';
});

const dialogDescription = computed(() => {


    if (props.transactionType === 'create') {
        return 'This will permanently save the information.';
    }

    if (props.transactionType === 'delete') {
        return 'This will permanently delete the information.';
    }

    if (props.transactionType === 'update') {
        return 'This will update the information. Please confirm to proceed.';
    }

    return 'This will save the information. Please confirm to proceed.';
});



const showIcon = computed(() => true);

const intent = computed(() => {
    return props.transactionType === 'delete' ? 'delete' : 'save';
});

const emit = defineEmits(['update:open', 'confirm', 'cancel']);

const handleConfirm = () => {
    emit('confirm');

};

const iconComponent = computed(() => {
    if (props.transactionType === 'create') return Save;
    if (props.transactionType === 'delete') return Trash2;
    if (props.transactionType === 'update') return Pencil;
    return TriangleAlert;
});

const iconClass = computed(() => {
    if (props.transactionType === 'create') return 'bg-primary/10 text-primary';
    if (props.transactionType === 'delete') return 'bg-destructive/10 text-destructive dark:bg-destructive/20';
    if (props.transactionType === 'update') return 'bg-green-100 text-green-600 dark:bg-green-200';
    return 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400';
});
</script>

<template>
    <AlertDialog :open="open" @update:open="!props.loading && emit('update:open', $event)">
        <AlertDialogContent class="max-w-sm sm:max-w-sm" @escape-key-down="props.loading && $event.preventDefault()">
            <AlertDialogHeader class="text-center">
                <AlertDialogTitle class="text-center">
                    {{ dialogTitle }}
                </AlertDialogTitle>
                <AlertDialogDescription>
                    <div class="space-y-2">
                        <div class="space-y-3">
                            <div v-if="showIcon" class="mx-auto flex h-10 w-10 items-center justify-center rounded-full"
                                :class="iconClass">
                                <component :is="iconComponent" class="h-5 w-5" />
                            </div>
                            <p class="text-center text-sm text-muted-foreground">{{ dialogDescription }}</p>
                        </div>
                    </div>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>


                <BaseButton :disabled="props.disabled" type="button"
                    @click="props.transactionType === 'delete' ? emit('cancel') : emit('update:open', false)"
                    transactionType="cancel" />

                <BaseButton type="button" @click="handleConfirm" :loading="props.loading" :disabled="props.disabled"
                    :transactionType="props.transactionType" />
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
