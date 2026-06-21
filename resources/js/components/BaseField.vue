<script setup>
import { Button } from '@/components/ui/buttonorig'
import { Checkbox } from '@/components/ui/checkbox'
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
} from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'

const props = defineProps({
    legend: {
        type: String,
        default: 'Default Legend',
    },
    description: {
        type: String,
        default: 'Default Description',
    },
})
</script>

<template>

    <FieldGroup>
        <slot name="fieldGroups">
            <!-- Default content if no slot is provided -->
            <FieldSet>
                <FieldLegend>
                    <slot name="legend">{{ props.legend }}</slot>
                </FieldLegend>
                <FieldDescription>
                    <slot name="description">{{ props.description }}</slot>
                </FieldDescription>
                <FieldGroup>
                    <slot name="fields">
                        <!-- Default fields content -->
                    </slot>
                </FieldGroup>
            </FieldSet>
        </slot>
    </FieldGroup>

</template>
