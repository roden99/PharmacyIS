<script setup>
import { DateFormatter, getLocalTimeZone } from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: {
        type: Object,
        default: null,
    },
    placeholder: {
        type: String,
        default: 'Pick a date',
    },
    locale: {
        type: String,
        default: 'en-PH',
    },
    timeZone: {
        type: String,
        default: 'Asia/Manila',
    },
    minValue: {
        type: Object,
        default: null,
    },
    maxValue: {
        type: Object,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    disablepop: {
        type: Boolean,
        default: false,
    },
    app: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const value = ref(props.modelValue);

watch(
    () => props.modelValue,
    (newVal) => {
        value.value = newVal;
    },
);

watch(value, (newVal) => {
    emit('update:modelValue', newVal);
});

const df = new DateFormatter(props.locale, {
    dateStyle: 'long',
    timeZone: props.timeZone,
});
</script>

<template>
    <div>
        <Popover v-if="!props.disablepop">
            <PopoverTrigger as-child>
                <Button type="button" variant="outline" :disabled="props.disabled" :readonly="props.readonly"
                    :class="cn('w-full justify-start text-left font-normal sm:min-w-[240px]', !value && 'text-muted-foreground')">
                    <CalendarIcon class="mr-2 h-4 w-4 truncate overflow-hidden" />
                    {{ value ? df.format(value.toDate(getLocalTimeZone())) : placeholder }}
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0">
                <Calendar v-model="value" initial-focus :min-value="props.minValue ?? undefined"
                    :max-value="props.maxValue ?? undefined" layout="month-and-year" />
            </PopoverContent>
        </Popover>
        <Button v-else type="button" variant="outline" :disabled="props.disabled" :readonly="props.readonly"
            :class="cn('w-full justify-start text-left font-normal sm:min-w-[240px]', !value && 'text-muted-foreground')">
            <CalendarIcon class="mr-2 h-4 w-4 truncate overflow-hidden" />
            {{ value ? df.format(value.toDate(getLocalTimeZone())) : placeholder }}
        </Button>
    </div>
</template>
