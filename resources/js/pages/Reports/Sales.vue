<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import * as salesRoutes from '@/routes/sales'
import * as reportsRoutes from '@/routes/reports'

defineOptions({ layout: AppLayout })

const props = defineProps<{ sales: any; totals: { USD: number; ZIG: number }; filters: any }>()

function applyFilters() {
  router.get(reportsRoutes.sales.url(), {
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    currency: props.filters.currency === 'all' ? '' : props.filters.currency,
    payment_method: props.filters.payment_method === 'all' ? '' : props.filters.payment_method
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold">Sales Report</h1>

    <div class="flex gap-4 flex-wrap">
      <div class="space-y-1">
        <label class="text-sm font-medium">Date From</label>
        <Input type="date" :model-value="filters.date_from" @update:model-value="filters.date_from = String($event); applyFilters()" />
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Date To</label>
        <Input type="date" :model-value="filters.date_to" @update:model-value="filters.date_to = String($event); applyFilters()" />
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Currency</label>
        <Select :model-value="filters.currency" @update:model-value="filters.currency = String($event ?? 'all'); applyFilters()">
          <SelectTrigger class="w-32">
            <SelectValue placeholder="All" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All</SelectItem>
            <SelectItem value="USD">USD</SelectItem>
            <SelectItem value="ZIG">ZIG</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Payment Method</label>
        <Select :model-value="filters.payment_method" @update:model-value="filters.payment_method = String($event ?? 'all'); applyFilters()">
          <SelectTrigger class="w-36">
            <SelectValue placeholder="All" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All</SelectItem>
            <SelectItem value="ecocash">Ecocash</SelectItem>
            <SelectItem value="bank">Bank</SelectItem>
            <SelectItem value="cash">Cash</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <Card>
        <CardHeader>
          <CardTitle class="text-lg">USD Total</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">USD {{ Number(totals.USD).toFixed(2) }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle class="text-lg">ZIG Total</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">ZIG {{ Number(totals.ZIG).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
        </CardContent>
      </Card>
    </div>

    <div class="rounded-md border overflow-hidden">
      <table class="w-full">
        <thead>
          <tr class="bg-muted/50 text-sm text-muted-foreground">
            <th class="text-left px-4 py-3 font-medium">Date</th>
            <th class="text-left px-4 py-3 font-medium">Customer</th>
            <th class="text-left px-4 py-3 font-medium">Currency</th>
            <th class="text-left px-4 py-3 font-medium">Payment Method</th>
            <th class="text-right px-4 py-3 font-medium">Total</th>
            <th class="text-right px-4 py-3 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sale in sales.data" :key="sale.id" class="border-t text-sm">
            <td class="px-4 py-3">{{ sale.date }}</td>
            <td class="px-4 py-3">{{ sale.customer_name }}</td>
            <td class="px-4 py-3">{{ sale.currency }}</td>
            <td class="px-4 py-3 capitalize">{{ sale.payment_method }}</td>
            <td class="px-4 py-3 text-right">{{ Number(sale.total_amount).toFixed(2) }}</td>
            <td class="px-4 py-3 text-right">
              <Button variant="outline" size="sm" as-child>
                <Link :href="salesRoutes.show(sale.id)">View</Link>
              </Button>
            </td>
          </tr>
          <tr v-if="sales.data.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">No sales found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="sales.links && sales.links.length > 3" class="flex items-center gap-1 justify-center">
      <template v-for="link in sales.links" :key="link.label">
        <span v-if="link.url === null" class="px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
        <Link
          v-else
          :href="link.url"
          class="px-3 py-1.5 text-sm rounded-md hover:bg-muted"
          :class="{ 'bg-muted font-medium': link.active }"
          v-html="link.label"
        />
      </template>
    </div>
  </div>
</template>
