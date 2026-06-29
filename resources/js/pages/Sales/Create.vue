<script setup lang="ts">
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Trash2, Plus } from '@lucide/vue'
import * as salesRoutes from '@/routes/sales'

defineOptions({ layout: AppLayout })

const props = defineProps<{ products: any[] }>()

const form = useForm({
  date: new Date().toISOString().split('T')[0],
  customer_name: '',
  currency: 'USD',
  payment_method: 'cash',
  items: [] as Array<{
    product_id: number | null
    quantity: number | string
    unit_price: number | string
    product_search: string
    showDropdown: boolean
  }>
})

function addRow() {
  form.items.push({
    product_id: null,
    quantity: '',
    unit_price: '',
    product_search: '',
    showDropdown: false
  })
}

function removeRow(index: number) {
  form.items.splice(index, 1)
}

function filteredProducts(search: string) {
  if (!search) return []
  const q = search.toLowerCase()
  return props.products.filter(p => p.name.toLowerCase().includes(q))
}

function getProductName(productId: number | null) {
  if (!productId) return ''
  const product = props.products.find(p => p.id === productId)
  return product ? product.name : ''
}

function onSearchInput(item: any) {
  item.product_id = null
  item.showDropdown = true
}

function onSearchBlur(item: any) {
  setTimeout(() => { item.showDropdown = false }, 200)
}

function onSearchFocus(item: any) {
  if (item.product_search) {
    item.showDropdown = true
  }
}

function selectProduct(item: any, product: any) {
  item.product_id = product.id
  item.unit_price = form.currency === 'USD' ? product.price_usd : product.price_zig
  item.product_search = product.name
  item.showDropdown = false
}

watch(() => form.currency, (newCurrency) => {
  form.items.forEach(item => {
    if (item.product_id) {
      const product = props.products.find(p => p.id === item.product_id)
      if (product) {
        item.unit_price = newCurrency === 'USD' ? product.price_usd : product.price_zig
      }
    }
  })
})

const total = computed(() => {
  return form.items.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.unit_price || 0)), 0)
})

function submit() {
  form.post(salesRoutes.store.url())
}
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold">New Sale</h1>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <Label for="date">Date</Label>
        <Input id="date" type="date" v-model="form.date" />
      </div>
      <div class="space-y-2">
        <Label for="customer_name">Customer Name</Label>
        <Input id="customer_name" v-model="form.customer_name" placeholder="Customer name" />
      </div>
      <div class="space-y-2">
        <Label for="currency">Currency</Label>
        <Select v-model="form.currency">
          <SelectTrigger id="currency">
            <SelectValue placeholder="Select currency" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="USD">USD</SelectItem>
            <SelectItem value="ZIG">ZIG</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div class="space-y-2">
        <Label for="payment_method">Payment Method</Label>
        <Select v-model="form.payment_method">
          <SelectTrigger id="payment_method">
            <SelectValue placeholder="Select payment method" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="ecocash">Ecocash</SelectItem>
            <SelectItem value="bank">Bank</SelectItem>
            <SelectItem value="cash">Cash</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Items</h2>
        <Button variant="outline" size="sm" @click="addRow">
          <Plus class="h-4 w-4 mr-1" />
          Add Row
        </Button>
      </div>

      <div v-if="form.items.length === 0" class="text-center py-8 text-muted-foreground">
        No items added yet. Click "Add Row" to start.
      </div>

      <table v-else class="w-full border-collapse">
        <thead>
          <tr class="border-b text-sm text-muted-foreground">
            <th class="text-left py-2 font-medium">Product</th>
            <th class="text-left py-2 font-medium w-24">Quantity</th>
            <th class="text-left py-2 font-medium w-32">Unit Price</th>
            <th class="text-left py-2 font-medium w-24">Total</th>
            <th class="w-10"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in form.items" :key="index" class="border-b">
            <td class="py-2 pr-2">
              <div class="relative">
                <Input
                  :model-value="item.product_search"
                  @update:model-value="item.product_search = String($event); onSearchInput(item)"
                  @focus="onSearchFocus(item)"
                  @blur="onSearchBlur(item)"
                  placeholder="Search product..."
                />
                <div
                  v-if="item.showDropdown && filteredProducts(item.product_search).length > 0"
                  class="absolute z-10 mt-1 w-full bg-popover border rounded-md shadow-lg max-h-48 overflow-y-auto"
                >
                  <div
                    v-for="product in filteredProducts(item.product_search)"
                    :key="product.id"
                    @mousedown.prevent="selectProduct(item, product)"
                    class="px-3 py-2 cursor-pointer hover:bg-muted text-sm"
                  >
                    {{ product.name }}
                  </div>
                </div>
              </div>
            </td>
            <td class="py-2 px-1">
              <Input v-model="item.quantity" type="number" min="0" step="1" />
            </td>
            <td class="py-2 px-1">
              <Input v-model="item.unit_price" type="number" min="0" step="0.01" />
            </td>
            <td class="py-2 px-1 text-sm font-medium">
              {{ (Number(item.quantity || 0) * Number(item.unit_price || 0)).toFixed(2) }}
            </td>
            <td class="py-2">
              <Button variant="ghost" size="icon" @click="removeRow(index)">
                <Trash2 class="h-4 w-4" />
              </Button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="text-right text-lg font-bold">
        Total: {{ total.toFixed(2) }}
      </div>
    </div>

    <div class="flex gap-4">
      <Button @click="submit" :disabled="form.processing">
        Save Sale
      </Button>
    </div>
  </div>
</template>
