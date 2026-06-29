<script setup lang="ts">
import { onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import * as salesRoutes from '@/routes/sales'

defineOptions({ layout: AppLayout })

const props = defineProps<{ sales: any; filters: { date_from: string; date_to: string; currency: string; payment_method: string; sort_by: string; sort_direction: string } }>()

onMounted(() => {
  const flash = (usePage().props.flash as any)
  if (flash?.success) {
    toast.success(flash.success)
  }
})

function applyFilters() {
  router.get(salesRoutes.index.url(), {
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    currency: props.filters.currency === 'all' ? '' : props.filters.currency,
    payment_method: props.filters.payment_method === 'all' ? '' : props.filters.payment_method,
    sort_by: props.filters.sort_by,
    sort_direction: props.filters.sort_direction,
  }, { preserveState: true, replace: true })
}

const sort = (column: string) => {
  const dir = props.filters.sort_by === column && props.filters.sort_direction === 'asc' ? 'desc' : 'asc'
  router.get(salesRoutes.index.url(), {
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
    currency: props.filters.currency,
    payment_method: props.filters.payment_method,
    sort_by: column,
    sort_direction: dir,
  }, { preserveState: true, replace: true })
}

const sortIndicator = (column: string) => {
  if (props.filters.sort_by !== column) return ''
  return props.filters.sort_direction === 'asc' ? ' \u25B2' : ' \u25BC'
}

const statusVariant: Record<string, string> = {
  draft: 'secondary',
  completed: 'default',
  paid: 'outline'
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Sales</h1>
      <Button as-child>
        <Link :href="salesRoutes.create()">New Sale</Link>
      </Button>
    </div>

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

    <div class="rounded-md border overflow-hidden">
      <table class="w-full">
        <thead>
          <tr class="bg-muted/50 text-sm text-muted-foreground">
            <th class="text-left px-4 py-3 font-medium cursor-pointer hover:text-foreground select-none" @click="sort('date')">Date{{ sortIndicator('date') }}</th>
            <th class="text-left px-4 py-3 font-medium cursor-pointer hover:text-foreground select-none" @click="sort('customer_name')">Customer{{ sortIndicator('customer_name') }}</th>
            <th class="text-left px-4 py-3 font-medium cursor-pointer hover:text-foreground select-none" @click="sort('currency')">Currency{{ sortIndicator('currency') }}</th>
            <th class="text-left px-4 py-3 font-medium cursor-pointer hover:text-foreground select-none" @click="sort('payment_method')">Payment Method{{ sortIndicator('payment_method') }}</th>
            <th class="text-right px-4 py-3 font-medium cursor-pointer hover:text-foreground select-none" @click="sort('total_amount')">Total{{ sortIndicator('total_amount') }}</th>
            <th class="text-left px-4 py-3 font-medium cursor-pointer hover:text-foreground select-none" @click="sort('status')">Status{{ sortIndicator('status') }}</th>
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
            <td class="px-4 py-3">
              <Badge :variant="(statusVariant[sale.status] as any) || 'secondary'">
                {{ sale.status }}
              </Badge>
            </td>
            <td class="px-4 py-3 text-right">
              <Button variant="outline" size="sm" as-child>
                <Link :href="salesRoutes.show(sale.id)">View</Link>
              </Button>
            </td>
          </tr>
          <tr v-if="sales.data.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No sales found.</td>
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
