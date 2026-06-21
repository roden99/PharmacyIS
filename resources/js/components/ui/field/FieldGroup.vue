<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import Skeleton from "@/components/ui/skeleton/Skeleton.vue"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  skeleton?: boolean
  skeletonRows?: number
  skeletonCols?: number
  skeletonLayout?: number[]
}>()
</script>

<template>
  <div data-slot="field-group" :class="cn(
    'group/field-group @container/field-group flex w-full flex-col gap-7 data-[slot=checkbox-group]:gap-3 [&>[data-slot=field-group]]:gap-4',
    props.class,
  )">
    <template v-if="skeleton">
      <template v-if="skeletonLayout">
        <div v-for="(cols, rowIndex) in skeletonLayout" :key="rowIndex" class="flex w-full gap-4">
          <div v-for="col in cols" :key="col" class="flex flex-1 flex-col gap-1">
            <Skeleton class="h-4 w-20 rounded" />
            <Skeleton class="h-9 w-full rounded-md" />
          </div>
        </div>
      </template>
      <template v-else>
        <div v-for="row in (skeletonRows ?? 3)" :key="row" class="flex w-full gap-4">
          <div v-for="col in (skeletonCols ?? 3)" :key="col" class="flex flex-1 flex-col gap-1">
            <Skeleton class="h-4 w-20 rounded" />
            <Skeleton class="h-9 w-full rounded-md" />
          </div>
        </div>
      </template>
    </template>
    <slot v-else />
  </div>
</template>
