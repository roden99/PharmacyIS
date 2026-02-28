<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Boxes, Warehouse, Folder, Settings2Icon, UserRoundCogIcon, Store, UsersRoundIcon, NotebookText, ShoppingCartIcon, BaggageClaim, LayoutGrid, LucideNotebookText, ScanBarcode, Package, UserCheck, UserRoundSearch, UsersRound, ShoppingCart, UserRoundCog, Settings2 } from 'lucide-vue-next';
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
    href: '/under-construction',
    // children: [
    //   {
    //     title: 'Menu #1',
    //     href: '/under-construction',
    //     icon: ScanBarcode,
    //   },
    //   {
    //     title: 'Menu #2',
    //     href: '/under-construction',
    //     icon: LucideNotebookText,
    //   },
    // ],
  },

  {
    title: 'Inventory',
    icon: Boxes,
    children: [
      {
        title: 'Item Lists',
        href: '/under-construction',
        icon: ScanBarcode,
      },

    ],
  },

  {
    title: 'Warehouse',
    icon: Warehouse,
    href: '/under-construction',
  },

  {
    title: 'Purchasing',
    icon: ShoppingCart,
    href: '/under-construction',
  },

  {
    title: 'Stock Movement',
    icon: BaggageClaim,
    href: '/under-construction',
  },

  {
    title: 'Customers',
    icon: UsersRoundIcon,
    children: [
      {
        title: 'List Customers',
        href: '/customers',
        icon: UserRoundSearch,
      },
      // {
      //   title: 'Menu #2',
      //   href: '/under-construction',
      //   icon: LucideNotebookText,
      // },
    ],
  },

  {
    title: 'Suppliers',
    icon: Store,
    children: [
      {
        title: 'Supplier Lists',
        href: '/suppliers',
        icon: UserRoundSearch,
      },
      // {
      //   title: '########',
      //   href: '/under-construction',
      //   icon: LucideNotebookText,
      // },
    ],
  },

  {
    title: 'Reports',
    icon: NotebookText,
    href: '/under-construction',
  },

  {
    title: 'User Management',
    icon: UserRoundCog,
    href: '/under-construction',
  },
  {
    title: 'Settings',
    icon: Settings2,
    href: '/under-construction',
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
