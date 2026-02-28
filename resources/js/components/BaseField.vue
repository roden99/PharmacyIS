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
</script>

<template>

    <FieldGroup>
        <slot name="fieldGroups">
            <!-- Default content if no slot is provided -->
            <FieldSet>
                <FieldLegend>
                    <slot name="legend">Default Legend</slot>
                </FieldLegend>
                <FieldDescription>
                    <slot name="description">Default Description</slot>
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
