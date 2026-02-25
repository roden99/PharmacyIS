<script setup>
import { Loader2 } from 'lucide-vue-next';
import { Button, buttonVariants } from '@/components/ui/buttonorig';
import { ColumnOrdering } from '@tanstack/vue-table';


defineProps({
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
  text: {
    type: String,
    default: 'Save',
  },
  variant: {
    type: String,
    default: 'outline',
  },
  color: {
    type: String,
    default: 'secondary',
  },
})

</script>
<template>

  <Button :variant="variant" :color="color" :type="type" :disabled="disabled" :loading="loading">
    <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
    {{ loading ? 'Please wait' : text }}
  </Button>
</template>