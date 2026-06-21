<script setup>
import { Loader2, Save, Pencil, Trash2, X, ShieldCheck, RotateCcw, Printer, Plus, PackagePlus } from 'lucide-vue-next';
import { Button } from '@/components/ui/buttonorig';
import { computed } from 'vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';


const props = defineProps({
  transactionType: {
    type: String,
    default: 'create',
  },
  type: {
    type: String,
    default: 'button',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  skeleton: {
    type: Boolean,
    default: false,
  },
});

const buttonText = computed(() => {
  if (props.transactionType === 'create') return 'Save';
  if (props.transactionType === 'add') return '';
  if (props.transactionType === 'print') return 'Print';
  if (props.transactionType === 'update') return 'Update';
  if (props.transactionType === 'delete') return 'Delete';
  if (props.transactionType === 'cancel') return 'Cancel';
  if (props.transactionType === 'verify') return 'Verify';
  if (props.transactionType === 'clear') return 'Clear';
  return 'Submit';
});

const buttonVariant = computed(() => {
  if (props.transactionType === 'delete') return 'destructive';
  if (props.transactionType === 'cancel') return 'secondary';
  if (props.transactionType === 'verify') return 'secondary';
  if (props.transactionType === 'clear') return 'secondary';
  if (props.transactionType === 'print') return 'secondary';
  return 'default';
});

const buttonColor = computed(() => {
  if (props.transactionType === 'delete') return 'destructive';
  if (props.transactionType === 'cancel') return 'secondary';
  if (props.transactionType === 'verify') return 'secondary';
  if (props.transactionType === 'clear') return 'secondary';
  if (props.transactionType === 'print') return 'secondary';

  return 'primary';
});

const buttonIcon = computed(() => {
  if (props.transactionType === 'create') return Save;
  if (props.transactionType === 'add') return PackagePlus;
  if (props.transactionType === 'update') return Pencil;
  if (props.transactionType === 'delete') return Trash2;
  if (props.transactionType === 'cancel') return X;
  if (props.transactionType === 'verify') return ShieldCheck;
  if (props.transactionType === 'clear') return RotateCcw;
  if (props.transactionType === 'print') return Printer;
  return null;
});

</script>

<template>
  <Skeleton v-if="skeleton" class="h-9 w-20 rounded-md" />
  <Button v-else :variant="buttonVariant" :color="buttonColor" :type="type" :disabled="disabled" :loading="loading">
    <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
    <component :is="buttonIcon" v-else-if="buttonIcon" class="mr-2 h-4 w-4" />
    {{ loading ? 'Please wait' : buttonText }}
  </Button>
</template>