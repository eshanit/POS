<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import * as salesRoutes from '@/routes/sales'

defineOptions({ layout: AppLayout })

defineProps<{ sale: any }>()

const statusVariant: Record<string, string> = {
  draft: 'secondary',
  completed: 'default',
  paid: 'outline'
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Sale #{{ sale.id }}</h1>
      <Button variant="outline" as-child>
        <Link :href="salesRoutes.index()">Back to Sales</Link>
      </Button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-lg border bg-card">
      <div>
        <p class="text-sm text-muted-foreground">Date</p>
        <p class="font-medium">{{ sale.date }}</p>
      </div>
      <div>
        <p class="text-sm text-muted-foreground">Customer</p>
        <p class="font-medium">{{ sale.customer_name }}</p>
      </div>
      <div>
        <p class="text-sm text-muted-foreground">Currency</p>
        <p class="font-medium">{{ sale.currency }}</p>
      </div>
      <div>
        <p class="text-sm text-muted-foreground">Payment Method</p>
        <p class="font-medium capitalize">{{ sale.payment_method }}</p>
      </div>
      <div>
        <p class="text-sm text-muted-foreground">Status</p>
        <Badge :variant="(statusVariant[sale.status] as any) || 'secondary'">{{ sale.status }}</Badge>
      </div>
      <div>
        <p class="text-sm text-muted-foreground">Recorded By</p>
        <p class="font-medium">{{ sale.user?.name || sale.user?.email || 'N/A' }}</p>
      </div>
      <div>
        <p class="text-sm text-muted-foreground">Total</p>
        <p class="font-medium text-lg">{{ Number(sale.total_amount).toFixed(2) }} {{ sale.currency }}</p>
      </div>
    </div>

    <div>
      <h2 class="text-lg font-semibold mb-3">Items</h2>
      <div class="rounded-md border overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="bg-muted/50 text-sm text-muted-foreground">
              <th class="text-left px-4 py-3 font-medium">Product</th>
              <th class="text-right px-4 py-3 font-medium">Quantity</th>
              <th class="text-right px-4 py-3 font-medium">Unit Price</th>
              <th class="text-right px-4 py-3 font-medium">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in sale.items" :key="item.id" class="border-t text-sm">
              <td class="px-4 py-3">{{ item.product?.name || 'N/A' }}</td>
              <td class="px-4 py-3 text-right">{{ item.quantity }}</td>
              <td class="px-4 py-3 text-right">{{ Number(item.unit_price).toFixed(2) }}</td>
              <td class="px-4 py-3 text-right font-medium">{{ (item.quantity * item.unit_price).toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
