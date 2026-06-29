<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import * as productsRoutes from '@/routes/products';
import type { BreadcrumbItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    stats: {
        total_products: number;
        low_stock_count: number;
        today_sales_usd: number;
        today_sales_zig: number;
    };
    low_stock_products: any[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Total Products</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_products }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Low Stock Items</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold" :class="{ 'text-red-600': stats.low_stock_count > 0 }">
                        {{ stats.low_stock_count }}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Today's Sales (USD)</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">${{ stats.today_sales_usd.toFixed(2) }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Today's Sales (ZIG)</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">ZIG {{ stats.today_sales_zig.toFixed(2) }}</div>
                </CardContent>
            </Card>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Low Stock Items</h2>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="productsRoutes.index()">View All Products</Link>
                </Button>
            </div>
            <div v-if="low_stock_products.length === 0" class="py-8 text-center text-muted-foreground">
                No low stock items
            </div>
            <div v-else class="space-y-2">
                <div v-for="product in low_stock_products" :key="product.id"
                    class="flex items-center justify-between rounded-lg border p-3">
                    <div>
                        <div class="font-medium">{{ product.name }}</div>
                        <div class="text-sm text-muted-foreground">{{ product.category?.name }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm">Stock: {{ product.current_quantity }}</span>
                        <Badge variant="destructive">Reorder at {{ product.reorder_level }}</Badge>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
