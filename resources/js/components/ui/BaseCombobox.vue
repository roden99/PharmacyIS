<script setup>
import { ref, computed, watch } from 'vue'
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Button } from '@/components/ui/buttonorig'
import { Input } from '@/components/ui/input'
import { SearchIcon, Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { ScrollArea } from '@/components/ui/scroll-area'

const props = defineProps({
    options: {
        type: Array,
        default: () => []
    },
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Search...'
    },
    searchPlaceholder: {
        type: String,
        default: 'Search...'
    },
    emptyMessage: {
        type: String,
        default: 'No option found.'
    },
    width: {
        type: String,
        default: 'w-[200px]'
    },
    disabled: {
        type: Boolean,
        default: false
    },

    readonly: {
        type: Boolean,
        default: false
    },

    disablepop: {
        type: Boolean,
        default: false
    },

    required: {
        type: Boolean,
        default: false
    },

})

const emit = defineEmits(['update:modelValue', 'search'])

const open = ref(false)
const value = ref(props.modelValue)
const searchQuery = ref('')
let debounceTimeout = null

const selected = computed(() => {
    return props.options.find(f => f.value === value.value)
})

const listHeight = computed(() => {
    if (props.options.length === 0) return { maxHeight: '80px' }
    const itemHeight = 32 // Each item is approximately 32px (py-1.5 + text)
    const calculatedHeight = props.options.length * itemHeight + 8 // +8 for padding
    const maxHeight = 300
    const minHeight = 100

    if (calculatedHeight > maxHeight) return { maxHeight: '300px' }
    if (calculatedHeight < minHeight) return { height: '100px', maxHeight: '100px' }
    return { height: `${calculatedHeight}px`, maxHeight: `${calculatedHeight}px` }
})

function selectOption(val) {
    const newValue = val === value.value ? '' : val
    value.value = newValue
    emit('update:modelValue', newValue)
    open.value = false
}

watch(() => props.modelValue, (newVal) => {
    value.value = newVal
})

watch(searchQuery, (newQuery) => {
    if (debounceTimeout) {
        clearTimeout(debounceTimeout)
    }

    debounceTimeout = setTimeout(() => {
        emit('search', newQuery)
    }, 300)
})
</script>
<template>
    <Popover v-model:open="open" :modal="false">
        <PopoverTrigger as-child>
            <Button type="button" variant="outline" role="combobox" :aria-expanded="open && !props.disablepop"
                :class="cn('justify-between text-base md:text-sm h-9 font-normal', width)" :disabled="props.disabled"
                :aria-required="props.required" @click="props.disablepop && $event.preventDefault()">
                <span :class="cn('truncate block max-w-full text-left', !selected && 'text-muted-foreground')">{{
                    selected?.label
                    || placeholder }}</span>
                <SearchIcon class="h-4 w-4 opacity-50 shrink-0" />
            </Button>
        </PopoverTrigger>

        <PopoverContent v-if="!props.disablepop" :class="cn('p-0', width)">
            <div class="flex items-center border-b px-3">
                <SearchIcon class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                <Input v-model="searchQuery" :placeholder="searchPlaceholder"
                    class="h-10 border-0 focus-visible:ring-0 focus-visible:ring-offset-0" :disabled="props.disabled" />
            </div>
            <ScrollArea :style="listHeight">
                <div v-if="options.length === 0" class="py-6 text-center text-sm">
                    {{ emptyMessage }}
                </div>
                <div v-else class="p-1">
                    <div v-for="opt in options" :key="opt.value" @click="!props.disabled && selectOption(opt.value)"
                        :class="cn(
                            'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground',
                            value === opt.value && 'bg-accent',
                            props.disabled && 'opacity-50 pointer-events-none'
                        )">
                        <Check :class="cn(
                            'mr-2 h-4 w-4',
                            value === opt.value ? 'opacity-100' : 'opacity-0'
                        )" />
                        {{ opt.label }}
                    </div>
                </div>
            </ScrollArea>
        </PopoverContent>
    </Popover>
</template>