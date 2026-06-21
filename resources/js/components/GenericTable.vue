<script setup lang="ts">
import { MoreHorizontal, Pencil, Trash2, Eye, Copy, Download, PlusCircle, Users, ShieldCheck, History, UserSearch, TrendingDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

interface Props {
  row?: Record<string, any>
  actions?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  row: () => ({}),
  actions: () => ['view', 'edit', 'delete']
})

const actionConfig: Record<string, { label: string; icon: any; class: string }> = {
  verifyEligibility: { label: 'Verify Eligibility', icon: ShieldCheck, class: 'text-blue-600' },
  view: { label: 'View', icon: Eye, class: 'text-blue-600' },
  edit: { label: 'Edit', icon: Pencil, class: 'text-green-600' },
  delete: { label: 'Delete', icon: Trash2, class: 'text-red-600' },
  copy: { label: 'Copy', icon: Copy, class: 'text-gray-600' },
  download: { label: 'Download', icon: Download, class: 'text-purple-600' },
  add: { label: 'Add', icon: PlusCircle, class: 'text-blue-600' },
  history: { label: 'History', icon: History, class: 'text-yellow-600' },
  clients: { label: 'Clients', icon: Users, class: 'text-indigo-600' },
  customers: { label: 'View Customers', icon: UserSearch, class: 'text-indigo-600' },
  initial: { label: 'Initial Inventory', icon: PlusCircle, class: 'text-blue-600' },
  reorder: { label: 'Reorder Level', icon: TrendingDown, class: 'text-orange-600' }
}

const emit = defineEmits<{
  (e: 'action', payload: { type: string; data: Record<string, any> }): void
}>()

const handleAction = (action: string, row: Record<string, any>) => {
  // console.log(`${action} clicked for:`, row)
  // toast.info(`${action} action triggered`, {
  //   description: `Action: ${actionConfig[action]?.label || action}`
  // })

  // 🚀 Emit to parent so it can open the modal
  emit('action', { type: action, data: row })
}

const isActionDisabled = (action: string, row: Record<string, any>): boolean => {
  if (action === 'initial' && row.is_inventory) return true
  return false
}


</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" class="w-8 h-8 p-0">
        <span class="sr-only">Open menu</span>
        <MoreHorizontal class="w-4 h-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <DropdownMenuLabel>Actions</DropdownMenuLabel>
      <DropdownMenuSeparator />

      <!-- ✅ Use slot if provided for custom actions -->
      <slot name="actions" :row="row" :handleAction="handleAction">
        <!-- ✅ Default: Render actions based on props -->
        <DropdownMenuItem v-for="action in actions" :key="action"
          :class="[actionConfig[action]?.class || 'text-gray-600', isActionDisabled(action, row) ? 'opacity-50 cursor-not-allowed' : '']"
          :disabled="isActionDisabled(action, row)"
          @click="!isActionDisabled(action, row) && handleAction(action, row)">
          <component :is="actionConfig[action]?.icon || Eye" class="mr-2 h-4 w-4" />
          {{ actionConfig[action]?.label || action }}
        </DropdownMenuItem>
      </slot>
    </DropdownMenuContent>
  </DropdownMenu>
</template>