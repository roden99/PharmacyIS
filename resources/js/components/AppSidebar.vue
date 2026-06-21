<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Boxes, Warehouse, Folder, Settings2Icon, UserRoundCogIcon, Store, UsersRoundIcon, NotebookText, ShoppingCartIcon, BaggageClaim, LayoutGrid, LucideNotebookText, ScanBarcode, Package, UserCheck, UserRoundSearch, UsersRound, ShoppingCart, UserRoundCog, Settings2, Truck, ClipboardList, Ruler, Zap, Pill, Notebook, BriefcaseMedical } from 'lucide-vue-next';
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
    href: '/products',
    icon: Package,

  },

  {

    title: 'Stock Receiving',
    href: '/deliveries',
    icon: Truck,


  },

  {

    title: 'Sales Orders',
    href: '/sales-orders',
    icon: Package,

  },

  {
    title: 'Customer Accounts',
    href: '/customer-accounts',
    icon: UserCheck,
  },




  {
    title: 'Stock Movement',
    icon: BaggageClaim,
    href: '/under-construction',
  },

  {
    title: 'Reports',
    icon: NotebookText,
    href: '/under-construction',
  },

  {
    title: 'Library',
    icon: Settings2,
    children: [
      // {
      //   title: 'Products',
      //   href: '/products',
      //   icon: Package,
      // },
      {
        title: 'Product Units',
        href: '/product-units',
        icon: Ruler,
      },
      {
        title: 'Brands',
        href: '/brands',
        icon: NotebookText,
      },
      // {
      //   title: 'Strengths',
      //   href: '/strengths',
      //   icon: Zap,
      // },
      {
        title: 'Drug Forms',
        href: '/drugforms',
        icon: Pill,
      },
      {
        title: 'Product Types',
        href: '/product-types',
        icon: BriefcaseMedical,
      },
      {
        title: 'Warehouse',
        href: '/warehouses',
        icon: Warehouse,
      },
      {
        title: 'Customers',
        href: '/customers',
        icon: UsersRoundIcon,
      },
      {
        title: 'Suppliers',
        href: '/suppliers',
        icon: Store,
      },
      {
        title: 'Sales Accounts',
        href: '/sales-accounts',
        icon: Notebook,
      },
      {
        title: 'User Management',
        href: '/under-construction',
        icon: UserRoundCog,
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
