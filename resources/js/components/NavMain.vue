<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

import { ref } from 'vue';

const props = defineProps<{
  items: NavItem[];
}>();

const page = usePage();

// Track open state for collapsible menus by title
import { onMounted } from 'vue';
const openMenus = ref<Record<string, boolean>>({});

function toggleMenu(title: string) {
  openMenus.value[title] = !openMenus.value[title];
}

// Open all menus with children by default
onMounted(() => {
  props.items.forEach(item => {
    if (item.children) {
      openMenus.value[item.title] = true;
    }
  });
});
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Platform</SidebarGroupLabel>
    <SidebarMenu>
      <template v-for="item in items" :key="item.title">
        <SidebarMenuItem v-if="!item.children">
          <SidebarMenuButton as-child :is-active="item.href === page.url && item.href !== '/under-construction'"
            :tooltip="item.title">
            <Link :href="item.href || ''">
              <component :is="item.icon" class="w-4 h-4" />
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem v-else>
          <SidebarMenuButton :tooltip="item.title" @click="toggleMenu(item.title)"
            class="flex items-center justify-between cursor-pointer">
            <span class="flex items-center gap-2">
              <component :is="item.icon" class="w-4 h-4 align-middle" />
              <span class="align-middle">{{ item.title }}</span>
            </span>
            <svg :class="{ 'rotate-90': openMenus[item.title] }" class="transition-transform w-3 h-3 ml-2" fill="none"
              stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </SidebarMenuButton>
          <SidebarMenu v-show="openMenus[item.title]" class="pl-6">
            <SidebarMenuItem v-for="child in item.children" :key="child.title">
              <SidebarMenuButton as-child :is-active="child.href === page.url && child.href !== '/under-construction'"
                :tooltip="child.title">
                <Link :href="child.href || ''">
                  <component :is="child.icon" class="w-4 h-4" />
                  <span>{{ child.title }}</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarMenuItem>
      </template>
    </SidebarMenu>
  </SidebarGroup>
</template>
