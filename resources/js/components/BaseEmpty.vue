<script setup>
import { ArrowUpRightIcon, Wrench } from 'lucide-vue-next'
import { Button } from '@/components/ui/buttonorig'
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty'
</script>
<template>
    <Empty class="h-auto py-6">
        <EmptyHeader class="gap-2">
            <EmptyMedia variant="icon" class="h-12 w-12">
                <Wrench class="h-6 w-6" />
            </EmptyMedia>
            <EmptyTitle class="text-2xl font-medium">Module is under development</EmptyTitle>
            <EmptyDescription class="text-base leading-tight">
                This module is currently under development and will be available soon.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>
</template>