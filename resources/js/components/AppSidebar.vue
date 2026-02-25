<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen,Boxes, Warehouse, Folder, Settings2Icon, UserRoundCogIcon, Store, UsersRoundIcon,NotebookText, ShoppingCartIcon,BaggageClaim, LayoutGrid, LucideNotebookText, ScanBarcode, Package, UserCheck, UserRoundSearch, UsersRound, ShoppingCart, UserRoundCog, Settings2 } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutGrid,
  },


  {
    title: 'Point of Sale',
    icon: ScanBarcode,
    children: [
      {
        title: 'Menu #1',
        href: '/verify-eligibility',
        icon: ScanBarcode,
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
    icon: Boxes,
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
    icon: Warehouse,
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
    icon: ShoppingCart,
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
    icon: BaggageClaim,
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
    icon: UsersRoundIcon,
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
    icon: Store,
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
    icon: NotebookText,
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
    icon: UserRoundCog,
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
    icon: Settings2,
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
