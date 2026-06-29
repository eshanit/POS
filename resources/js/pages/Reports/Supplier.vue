<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import * as reportsRoutes from '@/routes/reports'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  suppliers: any[]
  filters: {
    sort_by: string
  }
}>()

const updateSort = () => {
  router.get(reportsRoutes.supplier.url(), {
    sort_by: props.filters.sort_by
  }, { preserveState: true, replace: true })
}

const totalUSD = () => props.suppliers.reduce((sum, s) => sum + Number(s.sales_usd), 0)
const totalZIG = () => props.suppliers.reduce((sum, s) => sum + Number(s.sales_zig), 0)
const totalStock = () => props.suppliers.reduce((sum, s) => sum + Number(s.total_stock), 0)
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold">Supplier Performance Report</h1>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <CardHeader>
          <CardTitle class="text-sm font-medium text-muted-foreground">Total Suppliers</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">{{ suppliers.length }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle class="text-sm font-medium text-muted-foreground">Total Stock Units</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">{{ totalStock() }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle class="text-sm font-medium text-muted-foreground">Sales USD</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">USD {{ Number(totalUSD()).toFixed(2) }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle class="text-sm font-medium text-muted-foreground">Sales ZIG</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">ZIG {{ Number(totalZIG()).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
        </CardContent>
      </Card>
    </div>

    <div class="flex gap-4 flex-wrap">
      <div class="space-y-1">
        <label class="text-sm font-medium">Sort By</label>
        <Select :model-value="filters.sort_by || 'name'" @update:model-value="filters.sort_by = String($event ?? 'name'); updateSort()">
          <SelectTrigger class="w-40">
            <SelectValue placeholder="Sort by..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="name">Name</SelectItem>
            <SelectItem value="sales">Total Sales</SelectItem>
            <SelectItem value="stock">Total Stock</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <div class="rounded-md border overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-muted/50 text-sm text-muted-foreground">
            <th class="text-left px-4 py-3 font-medium">Supplier Name</th>
            <th class="text-left px-4 py-3 font-medium">Contact Person</th>
            <th class="text-left px-4 py-3 font-medium">Email</th>
            <th class="text-center px-4 py-3 font-medium">Products</th>
            <th class="text-right px-4 py-3 font-medium">Total Stock</th>
            <th class="text-right px-4 py-3 font-medium">Sales USD</th>
            <th class="text-right px-4 py-3 font-medium">Sales ZIG</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="supplier in suppliers" :key="supplier.id" class="border-t text-sm">
            <td class="px-4 py-3 font-medium">{{ supplier.name }}</td>
            <td class="px-4 py-3">{{ supplier.contact_person || '-' }}</td>
            <td class="px-4 py-3">{{ supplier.email || '-' }}</td>
            <td class="px-4 py-3 text-center">{{ supplier.total_products }}</td>
            <td class="px-4 py-3 text-right">{{ supplier.total_stock }}</td>
            <td class="px-4 py-3 text-right">USD {{ Number(supplier.sales_usd ?? 0).toFixed(2) }}</td>
            <td class="px-4 py-3 text-right">ZIG {{ Number(supplier.sales_zig ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
          </tr>
          <tr v-if="suppliers.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No suppliers found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
