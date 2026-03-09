<script setup>
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';
import { toast } from 'vue-sonner';
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
    initialOptions: {
        type: Array,
        default: () => [],
    },
    endpoint: {
        type: String,
        required: true,
    },
    responseKey: {
        type: String,
        required: true,
    },
    valueKey: {
        type: String,
        default: 'id',
    },
    labelKey: {
        type: String,
        required: true,
    },
    emptyMessage: {
        type: String,
        default: 'No results found',
    },
    width: {
        type: String,
        default: 'w-full',
    },
    colSpan: {
        type: String,
        default: 'col-span-6',
    },
    maxResults: {
        type: Number,
        default: 10,
    },
    debounceMs: {
        type: Number,
        default: 300,
    },
});

const emit = defineEmits(['update:modelValue']);

let abortController;
const options = ref(props.initialOptions.slice(0, props.maxResults).map(item => ({
    value: item[props.valueKey],
    label: item[props.labelKey]
})));

// Watch for changes to initialOptions
watch(() => props.initialOptions, (newOptions) => {
    options.value = newOptions.slice(0, props.maxResults).map(item => ({
        value: item[props.valueKey],
        label: item[props.labelKey]
    }));
}, { deep: true });

const performSearch = async (searchQuery) => {
    // Cancel previous request if still pending
    if (abortController) {
        abortController.abort();
    }

    abortController = new AbortController();

    try {
        const { data } = await axios.get(props.endpoint, {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
            signal: abortController.signal,
        });

        // Update options with search results
        if (searchQuery) {
            options.value = data[props.responseKey].slice(0, props.maxResults).map(item => ({
                value: item[props.valueKey],
                label: item[props.labelKey],
            }));
        }
    } catch (error) {
        if (axios.isCancel(error) || error.code === 'ERR_CANCELED') {
            return;
        }

        const errorMessage = error.response?.data?.message;
        console.error(`Failed to load ${props.label}:`, errorMessage);
        toast.error('ERROR', { description: `Failed to load ${props.label}` });
    }
};

// Debounced search handler
const handleSearch = useDebounceFn(performSearch, props.debounceMs);
</script>

<template>
    <Field :class="colSpan">
        <FieldLabel class="font-normal">{{ label }}</FieldLabel>
        <BaseCombobox :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)"
            :options="options" :empty-message="emptyMessage" :width="width" @search="handleSearch" />
    </Field>
</template>
