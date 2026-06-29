<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import * as productsRoutes from '@/routes/products'

defineOptions({ layout: AppLayout })

const props = defineProps<{ product: any }>()
</script>

<template>
  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">{{ product.name }}</h1>
        <p class="text-muted-foreground">SKU: {{ product.sku }}</p>
      </div>
      <div class="flex gap-2">
        <Link :href="productsRoutes.edit(product.id)">
          <Button variant="outline">Edit</Button>
        </Link>
        <Link :href="productsRoutes.index()">
          <Button variant="outline">Back to Products</Button>
        </Link>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium">Current Stock</CardTitle></CardHeader>
        <CardContent>
          <div class="text-2xl font-bold" :class="{ 'text-red-600': product.current_quantity <= product.reorder_level }">
            {{ product.current_quantity }}
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium">Reorder Level</CardTitle></CardHeader>
        <CardContent><div class="text-2xl font-bold">{{ product.reorder_level }}</div></CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium">Price USD</CardTitle></CardHeader>
        <CardContent><div class="text-2xl font-bold">${{ Number(product.price_usd).toFixed(2) }}</div></CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium">Price ZIG</CardTitle></CardHeader>
        <CardContent><div class="text-2xl font-bold">ZIG {{ Number(product.price_zig).toFixed(2) }}</div></CardContent>
      </Card>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <Card>
        <CardHeader><CardTitle>Details</CardTitle></CardHeader>
        <CardContent class="space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-muted-foreground">Category</span><span>{{ product.category?.name ?? '—' }}</span></div>
          <div class="flex justify-between"><span class="text-muted-foreground">Supplier</span><span>{{ product.supplier?.name ?? '—' }}</span></div>
          <div class="flex justify-between"><span class="text-muted-foreground">Cost Price</span><span>${{ Number(product.cost_price).toFixed(2) }}</span></div>
          <div class="flex justify-between"><span class="text-muted-foreground">Description</span><span class="text-right max-w-xs">{{ product.description ?? '—' }}</span></div>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader><CardTitle>Stock Movements</CardTitle></CardHeader>
      <CardContent>
        <div v-if="product.stock_movements?.length === 0" class="py-4 text-center text-muted-foreground">
          No movements recorded yet.
        </div>
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="border-b text-left text-muted-foreground">
              <th class="pb-2 font-medium">Date</th>
              <th class="pb-2 font-medium">Type</th>
              <th class="pb-2 font-medium">Quantity</th>
              <th class="pb-2 font-medium">Unit Cost</th>
              <th class="pb-2 font-medium">Reference</th>
              <th class="pb-2 font-medium">Recorded By</th>
              <th class="pb-2 font-medium">Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in product.stock_movements" :key="m.id" class="border-b last:border-0">
              <td class="py-2">{{ m.date }}</td>
              <td class="py-2">
                <Badge :variant="m.type === 'in' ? 'default' : m.type === 'out' ? 'destructive' : 'secondary'">
                  {{ m.type }}
                </Badge>
              </td>
              <td class="py-2">{{ m.type === 'in' ? '+' : '-' }}{{ m.quantity }}</td>
              <td class="py-2">{{ m.unit_cost_usd ? '$' + Number(m.unit_cost_usd).toFixed(2) : '—' }}</td>
              <td class="py-2">{{ m.reference ?? '—' }}</td>
              <td class="py-2">{{ m.user?.name ?? '—' }}</td>
              <td class="py-2">{{ m.notes ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </CardContent>
    </Card>
  </div>
</template>
