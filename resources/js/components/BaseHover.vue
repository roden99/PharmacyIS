<script setup>
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui/hover-card';
import { computed } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    },
    // Legacy support for single value
    value: {
        type: [String, Number],
        default: null,
    },
    label: {
        type: String,
        default: 'Value',
    },
});

const displayItems = computed(() => {
    // If items array is provided, use it
    if (props.items && props.items.length > 0) {
        return props.items;
    }
    // Otherwise, use legacy single value/label
    if (props.value !== null) {
        return [{ label: props.label, value: props.value }];
    }
    return [];
});
</script>

<template>
    <HoverCard>
        <HoverCardTrigger as-child>
            <div class="cursor-pointer">
                <slot></slot>
            </div>
        </HoverCardTrigger>
        <HoverCardContent class="w-auto">
            <div class="flex flex-col space-y-2">
                <div v-for="(item, index) in displayItems" :key="index" class="flex flex-col space-y-1">
                    <p class="text-xs text-muted-foreground">{{ item.label }}</p>
                    <p class="text-sm font-semibold">{{ item.value }}</p>



                </div>
            </div>
        </HoverCardContent>
    </HoverCard>
</template>
