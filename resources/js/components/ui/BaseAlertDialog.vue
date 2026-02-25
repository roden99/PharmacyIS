<script setup>
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:open']);
</script>

<template>
    <AlertDialog :open="open" @update:open="emit('update:open', $event)">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>
                    <slot name="alertTitle">
                        Are you sure??
                    </slot>
                </AlertDialogTitle>
                <AlertDialogDescription>
                    <div class="space-y-2">
                        <!-- ✅ Description Box with Black Font -->
                        <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg shadow-sm">
                            <div class="text-black">
                                <slot name="alertDescription">
                                    <p class="text-black">Please confirm this action.</p>
                                </slot>
                            </div>
                        </div>
                    </div>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>


                <slot name="alertFooter">
                    <AlertDialogCancel>No</AlertDialogCancel>
                    <AlertDialogAction>Yes</AlertDialogAction>
                    <!-- <AlertDialogAction @click="handleSubmit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Yes' }}
                </AlertDialogAction> -->
                </slot>

            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
