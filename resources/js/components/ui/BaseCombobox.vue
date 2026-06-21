<script setup>
import { ref, computed, watch } from 'vue'
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { SearchIcon, Check, ChevronsUpDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { ScrollArea } from '@/components/ui/scroll-area'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
import { InputGroup, InputGroupAddon, InputGroupInput, InputGroupButton } from '@/components/ui/input-group'

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

    searchDelay: {
        type: Number,
        default: 500,
    },

    skeleton: {
        type: Boolean,
        default: false,
    },

})

const emit = defineEmits(['update:modelValue', 'search', 'create'])

const open = ref(false)
const showCreate = ref(false)
const value = ref(props.modelValue)
const searchQuery = ref('')
let debounceTimeout = null

// Cache the selected label so it persists even when options reload
const selectedLabel = ref(props.options.find(f => f.value === props.modelValue)?.label || '')

const selected = computed(() => {
    return props.options.find(f => f.value === value.value)
})

const listHeight = computed(() => {
    if (props.options.length === 0) return { maxHeight: '80px' }
    const itemHeight = 32 // Each item is approximately 32px (py-1.5 + text)
    const calculatedHeight = props.options.length * itemHeight + 8 // +8 for padding
    const maxHeight = 256
    const minHeight = 100

    if (calculatedHeight > maxHeight) return { height: '256px', maxHeight: '256px' }
    if (calculatedHeight < minHeight) return { height: '100px', maxHeight: '100px' }
    return { height: `${calculatedHeight}px`, maxHeight: `${calculatedHeight}px` }
})

function selectOption(val) {
    // Cancel any pending debounced search so stale results don't overwrite options after selection
    if (debounceTimeout) {
        clearTimeout(debounceTimeout)
        debounceTimeout = null
    }
    searchQuery.value = ''

    const newValue = val === value.value ? '' : val
    if (newValue) {
        const opt = props.options.find(f => f.value === newValue)
        selectedLabel.value = opt?.label || ''
    } else {
        selectedLabel.value = ''
    }
    value.value = newValue
    emit('update:modelValue', newValue)
    open.value = false
}

watch(() => props.modelValue, (newVal) => {
    value.value = newVal
    if (newVal) {
        const opt = props.options.find(f => f.value === newVal)
        if (opt) selectedLabel.value = opt.label
    } else {
        selectedLabel.value = ''
    }
})

// When options reload (e.g. from debounced search), update the cached label if found
watch(() => props.options, (newOptions) => {
    if (value.value) {
        const opt = newOptions.find(f => f.value === value.value)
        if (opt) selectedLabel.value = opt.label
    }
})

watch(searchQuery, (newQuery) => {
    if (debounceTimeout) {
        clearTimeout(debounceTimeout)
    }

    debounceTimeout = setTimeout(() => {
        emit('search', newQuery)
    }, props.searchDelay)
})
</script>
<template>
    <Skeleton v-if="props.skeleton" :class="cn('h-9', width)" />
    <Popover v-else v-model:open="open" :modal="false">
        <PopoverTrigger as-child :disabled="props.disabled">
            <InputGroup :class="cn(width, 'cursor-pointer', props.disabled && 'opacity-50 pointer-events-none')"
                role="combobox" :aria-expanded="open && !props.disablepop" :aria-required="props.required"
                @click="props.disablepop && $event.preventDefault()">
                <InputGroupInput readonly :model-value="selectedLabel || selected?.label || ''"
                    :placeholder="placeholder" class="cursor-pointer" />
                <InputGroupAddon align="inline-end">
                    <ChevronsUpDown class="opacity-50" />
                </InputGroupAddon>
            </InputGroup>
        </PopoverTrigger>

        <PopoverContent v-if="!props.disablepop" :class="cn('p-0', width)">
            <InputGroup class="rounded-none border-0 border-b shadow-none">
                <InputGroupAddon align="inline-start">
                    <SearchIcon />
                </InputGroupAddon>
                <InputGroupInput v-model="searchQuery" :placeholder="searchPlaceholder" :disabled="props.disabled" />
            </InputGroup>
            <ScrollArea :style="listHeight">
                <div v-if="options.length === 0" class="flex flex-col items-center gap-2 py-4 text-center text-sm">

                    <InputGroupButton type="button" variant="default" size="sm"
                        @click="open = false; showCreate = true">
                        Create
                    </InputGroupButton>
                </div>
                <div v-else class="p-1">
                    <div v-for="opt in options" :key="opt.value" @click="!props.disabled && selectOption(opt.value)"
                        :class="cn(
                            'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground',
                            value === opt.value && 'bg-accent',
                            props.disabled && 'opacity-50 pointer-events-none'
                        )">
                        <Check :class="cn('mr-2 h-4 w-4', value === opt.value ? 'opacity-100' : 'opacity-0')" />
                        {{ opt.label }}
                    </div>
                </div>
            </ScrollArea>
        </PopoverContent>
    </Popover>
    <div v-if="showCreate">
        <slot name="create" :close="() => showCreate = false" />
    </div>
</template>