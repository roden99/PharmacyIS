<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, LucideNotebookText, Package, UserCheck, UserRoundSearch, UsersRound } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutGrid,
  },


  {
    title: 'Point of Sale',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-eligibility',
        icon: UserCheck,
      },
      {
        title: 'Menu #2',
        href: '/claim-eligibility',
        icon: LucideNotebookText,
      },
    ],
  },

  {
    title: 'Inventory',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: 'Menu #2',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },

  {
    title: 'Warehouse',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: 'Menu #2',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },

  {
    title: 'Purchasing',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: 'Menu #2',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },

  {
    title: 'Stock Movement',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: 'Menu #2',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },

  {
    title: 'Customers',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: 'Menu #2',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },

  {
    title: 'Suppliers',
    icon: BookOpen,
    children: [
      {
        title: 'Supplier Lists',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: '########',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },

  {
    title: 'Reports',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: 'Menu #2',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },

  {
    title: 'User Management',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: 'Menu #2',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },
  {
    title: 'Settings',
    icon: BookOpen,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-member',
        icon: UserRoundSearch,
      },
      {
        title: 'Menu #2',
        href: '#',
        icon: LucideNotebookText,
      },
    ],
  },


];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="route('dashboard')">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
