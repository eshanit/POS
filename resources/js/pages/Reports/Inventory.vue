<script setup lang="ts">
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import * as reportsRoutes from '@/routes/reports'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  products: any
  stats: {
    total_products: number
    low_stock_count: number
    total_units: number
    total_cost_value: number
    total_potential_rev_usd: number
    total_potential_rev_zig: number
    expiring_soon_count: number
  }
  fastest_movers: any[]
  slowest_movers: any[]
  expiring_soon: any[]
  filters: {
    start_date: string
    end_date: string
    category_id: string | null
    stock_status: string | null
    search: string
    sort_by: string | null
    sort_dir: string | null
  }
  categories: any[]
}>()

const filters = reactive({
  start_date: props.filters.start_date,
  end_date: props.filters.end_date,
  category_id: props.filters.category_id,
  stock_status: props.filters.stock_status,
  search: props.filters.search,
  sort_by: props.filters.sort_by,
  sort_dir: props.filters.sort_dir,
})

const updateFilters = () => {
  router.get(reportsRoutes.inventory.url(), {
    start_date: filters.start_date,
    end_date: filters.end_date,
    category_id: filters.category_id ?? '',
    stock_status: filters.stock_status ?? '',
    search: filters.search,
    sort_by: filters.sort_by ?? '',
    sort_dir: filters.sort_dir ?? '',
  }, { preserveState: true, replace: true })
}

const exportQuery = () => {
  const params = new URLSearchParams({
    start_date: filters.start_date,
    end_date: filters.end_date,
    category_id: filters.category_id ?? '',
    stock_status: filters.stock_status ?? '',
    search: filters.search,
    sort_by: filters.sort_by ?? '',
    sort_dir: filters.sort_dir ?? '',
  })
  window.open(reportsRoutes.inventory.export.url() + '?' + params.toString(), '_blank')
}

const sortColumn = (column: string) => {
  if (filters.sort_by === column) {
    filters.sort_dir = filters.sort_dir === 'desc' ? 'asc' : 'desc'
  } else {
    filters.sort_by = column
    filters.sort_dir = 'asc'
  }
  updateFilters()
}

const sortIndicator = (column: string) => {
  if (filters.sort_by !== column) return ''
  return filters.sort_dir === 'asc' ? ' \u25B2' : ' \u25BC'
}

const statusBadge = (product: any) => {
  if (product.current_quantity <= 0) return { variant: 'destructive' as const, label: 'Out of Stock' }
  if (product.current_quantity <= product.reorder_level) return { variant: 'warning' as const, label: 'Low Stock' }
  return { variant: 'success' as const, label: 'In Stock' }
}

const expiryBadge = (days: number) => {
  if (days <= 7) return { variant: 'destructive' as const, label: `${days} days` }
  return { variant: 'warning' as const, label: `${days} days` }
}
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold">Inventory Report</h1>

    <!-- Filter Bar -->
    <Card>
      <CardContent class="pt-6">
        <div class="flex flex-wrap gap-4 items-end">
          <div class="space-y-1">
            <label class="text-sm font-medium">Date From</label>
            <Input type="date" v-model="filters.start_date" @change="updateFilters()" class="w-40" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Date To</label>
            <Input type="date" v-model="filters.end_date" @change="updateFilters()" class="w-40" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Stock Status</label>
            <Select :model-value="filters.stock_status || 'all'" @update:model-value="filters.stock_status = $event === 'all' ? null : String($event ?? ''); updateFilters()">
              <SelectTrigger class="w-40">
                <SelectValue placeholder="All" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                <SelectItem value="in">In Stock</SelectItem>
                <SelectItem value="low">Low Stock</SelectItem>
                <SelectItem value="out">Out of Stock</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Category</label>
            <Select :model-value="filters.category_id || 'all'" @update:model-value="filters.category_id = $event === 'all' ? null : String($event ?? ''); updateFilters()">
              <SelectTrigger class="w-48">
                <SelectValue placeholder="All Categories" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Categories</SelectItem>
                <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                  {{ cat.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Search</label>
            <Input
              :model-value="filters.search"
              @update:model-value="filters.search = String($event); updateFilters()"
              placeholder="Search by name or SKU..."
              class="w-48"
            />
          </div>
          <div class="flex items-center gap-2 pb-0.5">
            <Button variant="outline" size="sm" @click="updateFilters()">Refresh</Button>
            <Button variant="default" size="sm" @click="exportQuery()">Export CSV</Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium text-muted-foreground">Total Items</CardTitle></CardHeader>
        <CardContent><p class="text-3xl font-bold">{{ stats.total_units }}</p></CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium text-muted-foreground">Low Stock Alerts</CardTitle></CardHeader>
        <CardContent><p class="text-3xl font-bold text-red-600">{{ stats.low_stock_count }}</p></CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium text-muted-foreground">Total Cost (USD)</CardTitle></CardHeader>
        <CardContent><p class="text-3xl font-bold">USD {{ Number(stats.total_cost_value).toFixed(2) }}</p></CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium text-muted-foreground">Potential Revenue</CardTitle></CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">USD {{ Number(stats.total_potential_rev_usd).toFixed(2) }}</p>
          <p class="text-sm text-muted-foreground">ZIG {{ Number(stats.total_potential_rev_zig).toFixed(2) }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2"><CardTitle class="text-sm font-medium text-muted-foreground">Expiring Soon</CardTitle></CardHeader>
        <CardContent><p class="text-3xl font-bold text-yellow-600">{{ stats.expiring_soon_count }}</p></CardContent>
      </Card>
    </div>

    <!-- Movement Analytics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <Card>
        <CardHeader><CardTitle>Top 5 Fastest Movers</CardTitle></CardHeader>
        <CardContent>
          <div v-if="fastest_movers.length === 0" class="py-4 text-center text-muted-foreground">No sales data for this period.</div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b text-muted-foreground">
                <th class="pb-2 text-left font-medium">#</th>
                <th class="pb-2 text-left font-medium">Product</th>
                <th class="pb-2 text-right font-medium">Units Sold</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(m, i) in fastest_movers" :key="m.product_id" class="border-b last:border-0">
                <td class="py-2 text-muted-foreground">{{ i + 1 }}</td>
                <td class="py-2">
                  <span class="font-medium">{{ m.product_name }}</span>
                  <span class="text-xs text-muted-foreground ml-1">{{ m.product_sku }}</span>
                </td>
                <td class="py-2 text-right font-medium">{{ m.total_sold }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Top 5 Slowest Movers</CardTitle></CardHeader>
        <CardContent>
          <div v-if="slowest_movers.length === 0" class="py-4 text-center text-muted-foreground">No data available.</div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b text-muted-foreground">
                <th class="pb-2 text-left font-medium">#</th>
                <th class="pb-2 text-left font-medium">Product</th>
                <th class="pb-2 text-right font-medium">Sold</th>
                <th class="pb-2 text-right font-medium">On Hand</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(m, i) in slowest_movers" :key="m.product_id" class="border-b last:border-0">
                <td class="py-2 text-muted-foreground">{{ i + 1 }}</td>
                <td class="py-2">
                  <span class="font-medium">{{ m.product_name }}</span>
                  <span class="text-xs text-muted-foreground ml-1">{{ m.product_sku }}</span>
                </td>
                <td class="py-2 text-right">{{ m.total_sold }}</td>
                <td class="py-2 text-right font-medium">{{ m.current_quantity }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>

    <!-- Expiry Alerts -->
    <Card v-if="expiring_soon.length > 0">
      <CardHeader><CardTitle>Products Nearing Expiry (Next 30 Days)</CardTitle></CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b text-muted-foreground">
                <th class="pb-2 text-left font-medium">Product</th>
                <th class="pb-2 text-left font-medium">Expiry Date</th>
                <th class="pb-2 text-left font-medium">Days Left</th>
                <th class="pb-2 text-right font-medium">Qty on Hand</th>
                <th class="pb-2 text-right font-medium">Potential Loss (USD)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in expiring_soon" :key="e.id" class="border-b last:border-0">
                <td class="py-2 font-medium">{{ e.name }}</td>
                <td class="py-2">{{ e.expiry_date }}</td>
                <td class="py-2">
                  <Badge :variant="expiryBadge(e.days_until_expiry).variant">
                    {{ expiryBadge(e.days_until_expiry).label }}
                  </Badge>
                </td>
                <td class="py-2 text-right">{{ e.current_quantity }}</td>
                <td class="py-2 text-right font-medium">USD {{ Number(e.potential_loss_usd).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <!-- Valuation Table -->
    <Card>
      <CardHeader><CardTitle>Current Stock Valuation</CardTitle></CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b text-muted-foreground">
                <th class="px-2 py-3 text-left font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('sku')">SKU{{ sortIndicator('sku') }}</th>
                <th class="px-2 py-3 text-left font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('name')">Name{{ sortIndicator('name') }}</th>
                <th class="px-2 py-3 text-left font-medium">Category</th>
                <th class="px-2 py-3 text-right font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('current_quantity')">Qty{{ sortIndicator('current_quantity') }}</th>
                <th class="px-2 py-3 text-right font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('cost_price')">Cost{{ sortIndicator('cost_price') }}</th>
                <th class="px-2 py-3 text-right font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('total_cost')">Total Cost{{ sortIndicator('total_cost') }}</th>
                <th class="px-2 py-3 text-right font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('price_usd')">Sell USD{{ sortIndicator('price_usd') }}</th>
                <th class="px-2 py-3 text-right font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('price_zig')">Sell ZIG{{ sortIndicator('price_zig') }}</th>
                <th class="px-2 py-3 text-right font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('potential_rev_usd')">Rev USD{{ sortIndicator('potential_rev_usd') }}</th>
                <th class="px-2 py-3 text-right font-medium cursor-pointer hover:text-foreground select-none" @click="sortColumn('potential_rev_zig')">Rev ZIG{{ sortIndicator('potential_rev_zig') }}</th>
                <th class="px-2 py-3 text-center font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in products.data" :key="product.id" class="border-b last:border-0 text-sm hover:bg-muted/50">
                <td class="px-2 py-3 font-mono text-xs">{{ product.sku }}</td>
                <td class="px-2 py-3 font-medium">{{ product.name }}</td>
                <td class="px-2 py-3">{{ product.category?.name || '—' }}</td>
                <td class="px-2 py-3 text-right">{{ product.current_quantity }}</td>
                <td class="px-2 py-3 text-right">USD {{ Number(product.cost_price).toFixed(2) }}</td>
                <td class="px-2 py-3 text-right">USD {{ Number(product.current_quantity * product.cost_price).toFixed(2) }}</td>
                <td class="px-2 py-3 text-right">USD {{ Number(product.price_usd).toFixed(2) }}</td>
                <td class="px-2 py-3 text-right">ZIG {{ Number(product.price_zig).toFixed(2) }}</td>
                <td class="px-2 py-3 text-right">USD {{ Number(product.current_quantity * product.price_usd).toFixed(2) }}</td>
                <td class="px-2 py-3 text-right">ZIG {{ Number(product.current_quantity * product.price_zig).toFixed(2) }}</td>
                <td class="px-2 py-3 text-center">
                  <Badge :variant="statusBadge(product).variant">{{ statusBadge(product).label }}</Badge>
                </td>
              </tr>
              <tr v-if="products.data?.length === 0">
                <td colspan="11" class="px-2 py-8 text-center text-muted-foreground">No products found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <!-- Pagination -->
    <div v-if="products.links && products.links.length > 3" class="flex flex-wrap items-center gap-1 justify-center">
      <template v-for="link in products.links" :key="link.label">
        <span v-if="link.url === null" class="px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
        <component :is="link.url ? Link : 'span'" :href="link.url || '#'" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-sm transition-colors hover:bg-accent" :class="{ 'bg-primary text-primary-foreground': link.active, 'pointer-events-none opacity-50': !link.url }" v-html="link.label" />
      </template>
    </div>
  </div>
</template>
