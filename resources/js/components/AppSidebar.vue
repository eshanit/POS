<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Package,
    Warehouse,
    ShoppingCart,
    List,
    FileText,
    Tags,
    Truck,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import * as products from '@/routes/products';
import * as stock from '@/routes/stock';
import * as sales from '@/routes/sales';
import * as reports from '@/routes/reports';
import * as categories from '@/routes/categories';
import * as suppliers from '@/routes/suppliers';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Products',
        href: products.index(),
        icon: Package,
    },
    {
        title: 'Categories',
        href: categories.index(),
        icon: Tags,
    },
    {
        title: 'Suppliers',
        href: suppliers.index(),
        icon: Truck,
    },
    {
        title: 'Receive Stock',
        href: stock.receive(),
        icon: Warehouse,
    },
    {
        title: 'Deduct Stock',
        href: '/stock/deduct',
        icon: Warehouse,
    },
    {
        title: 'New Sale',
        href: sales.create(),
        icon: ShoppingCart,
    },
    {
        title: 'Sales List',
        href: sales.index(),
        icon: List,
    },
    {
        title: 'Sales Report',
        href: reports.sales(),
        icon: FileText,
    },
    {
        title: 'Inventory Report',
        href: reports.inventory(),
        icon: Warehouse,
    },
    {
        title: 'Shrinkage Report',
        href: '/reports/shrinkage',
        icon: FileText,
    },
    {
        title: 'Supplier Report',
        href: reports.supplier(),
        icon: Truck,
    },
];

const footerNavItems: NavItem[] = [
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
