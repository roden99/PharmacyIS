<script setup>
import { ref } from 'vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { Field, FieldLabel } from '@/components/ui/field';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
    label: {
        type: String,
        required: true,
    },
    options: {
        type: Array,
        default: () => [],
    },
    emptyMessage: {
        type: String,
        default: 'No results found',
    },
    width: {
        type: String,
        default: 'w-full min-w-[240px] max-w-[320px]',
    },
    colSpan: {
        type: String,
        default: 'col-span-7',
    },
});

const emit = defineEmits(['update:modelValue', 'search']);

const handleSearch = (searchQuery) => {
    emit('search', searchQuery);
};
</script>

<template>
    <Field :class="colSpan">
        <FieldLabel class="font-normal">{{ label }}</FieldLabel>
        <BaseCombobox :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)"
            :options="options" :empty-message="emptyMessage" :width="width" @search="handleSearch" />
    </Field>
</template>
