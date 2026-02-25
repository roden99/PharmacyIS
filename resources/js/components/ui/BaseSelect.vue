<script setup>
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';

defineProps({
  options: {
    type: Array,
    required: true,
    // Expected format: [{ value: 'M', label: 'Male' }, { value: 'F', label: 'Female' }]
  },
  placeholder: {
    type: String,
    default: 'Select an option'
  }
});
</script>


<template>
    <Select>
        <SelectTrigger class="w-full">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>

</template>