<script setup>
import { Button } from '@/components/ui/buttonorig'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import { computed } from 'vue'

const props = defineProps({
  tabs: {
    type: Array,
    required: true,
    default: () => []
  }
})

const defaultTab = computed(() => {
  return props.tabs.length > 0 ? props.tabs[0].value : ''
})

</script>

<template>
  <Tabs :default-value="defaultTab" class="w-full">
    <TabsList class="grid w-full" :style="{ gridTemplateColumns: `repeat(${tabs.length}, 1fr)` }">
      <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value" :disabled="tab.disabled">
        {{ tab.label }}
      </TabsTrigger>
    </TabsList>

    <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
      <slot :name="`content-${tab.value}`"></slot>
    </TabsContent>
  </Tabs>
</template>
