<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import InputError from '@/components/InputError.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import stockRoutes from '@/routes/stock'

defineOptions({ layout: AppLayout })

const props = defineProps<{ products: any[]; recent?: any[] }>()

const form = useForm({
  product_id: '',
  reason: 'sale',
  quantity: '',
  date: new Date().toISOString().split('T')[0],
  reference: '',
  notes: '',
})

const selectedProduct = computed(() => {
  return props.products.find(p => String(p.id) === String(form.product_id)) || null
})

const confirmDialog = ref<InstanceType<typeof ConfirmDialog>>()

const quantityTooHigh = computed(() => {
  if (!selectedProduct.value) return false
  const q = Number(form.quantity)
  if (!q || q <= 0) return false
  return q > Number(selectedProduct.value.current_quantity)
})

const isSubmitDisabled = computed(() => {
  if (form.processing) return true
  if (!form.product_id) return true
  const q = Number(form.quantity)
  if (!q || q <= 0) return true
  if (quantityTooHigh.value) return true
  if (form.reason === 'sale' && !form.reference) return true
  return false
})

const submit = () => {
  confirmDialog.value?.showConfirm({
    title: 'Confirm Stock Deduction',
    description: `Deduct ${form.quantity || ''} of ${selectedProduct.value ? selectedProduct.value.name : 'selected product'}? This action cannot be undone.`,
    actionLabel: 'Deduct',
    cancelLabel: 'Cancel',
    variant: 'destructive',
    onConfirm: () => {
      form.post(stockRoutes.deduct.store.url())
    },
  })
}
</script>

<template>
  <div class="max-w-xl mx-auto space-y-6 p-4">
    <h1 class="text-2xl font-bold">Deduct / Adjust Stock</h1>

    <form @submit.prevent="submit" class="space-y-6">
      <div class="grid gap-2">
        <Label for="product_id">Product</Label>
        <Select v-model="form.product_id">
          <SelectTrigger>
            <SelectValue placeholder="Select product" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="product in props.products" :key="product.id" :value="String(product.id)">
              {{ product.name }} ({{ product.sku }}) — Available: {{ product.current_quantity }}
            </SelectItem>
          </SelectContent>
        </Select>
        <InputError :message="form.errors.product_id" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
          <Label for="reason">Reason</Label>
          <Select v-model="form.reason">
            <SelectTrigger>
              <SelectValue placeholder="Select reason" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="sale">Sales Deduction</SelectItem>
              <SelectItem value="breakage">Breakage / Damage</SelectItem>
              <SelectItem value="expired">Expired Stock</SelectItem>
              <SelectItem value="return_supplier">Return To Supplier</SelectItem>
              <SelectItem value="adjustment">Stock Take Correction</SelectItem>
            </SelectContent>
          </Select>
          <InputError :message="form.errors.reason" />
        </div>

        <div class="grid gap-2">
          <Label for="quantity">Quantity</Label>
          <Input id="quantity" v-model="form.quantity" type="number" min="1" :max="selectedProduct ? selectedProduct.current_quantity : undefined" />
          <p v-if="selectedProduct" class="text-xs text-muted-foreground">Available: {{ selectedProduct.current_quantity }}</p>
          <p v-if="quantityTooHigh" class="text-sm text-red-600">Requested quantity exceeds available stock.</p>
          <InputError :message="form.errors.quantity" />
        </div>
      </div>

      <div class="grid gap-2">
        <Label for="date">Date</Label>
        <Input id="date" v-model="form.date" type="date" required />
        <InputError :message="form.errors.date" />
      </div>

      <div class="grid gap-2">
        <Label for="reference">Reference</Label>
        <Input id="reference" v-model="form.reference" placeholder="Teller slip number or RMA" />
        <InputError :message="form.errors.reference" />
      </div>

      <div class="grid gap-2">
        <Label for="notes">Notes</Label>
        <textarea id="notes" v-model="form.notes" class="border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-[80px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50" />
        <InputError :message="form.errors.notes" />
      </div>

      <Button type="submit" :disabled="isSubmitDisabled">Deduct Stock</Button>
    </form>
    <div class="mt-8">
      <h2 class="text-lg font-semibold">Recent Deductions</h2>
      <div class="overflow-auto mt-2">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-muted/50 text-muted-foreground">
              <th class="text-left px-3 py-2">Date</th>
              <th class="text-left px-3 py-2">Product</th>
              <th class="text-left px-3 py-2">Reason</th>
              <th class="text-right px-3 py-2">Qty</th>
              <th class="text-right px-3 py-2">Unit Cost (USD)</th>
              <th class="text-left px-3 py-2">Reference</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in props.recent" :key="item.id" class="border-t">
              <td class="px-3 py-2">{{ item.date }}</td>
              <td class="px-3 py-2">{{ item.product ? item.product.name + ' (' + item.product.sku + ')' : '-' }}</td>
              <td class="px-3 py-2">{{ item.reason }}</td>
              <td class="px-3 py-2 text-right">{{ item.quantity }}</td>
              <td class="px-3 py-2 text-right">USD {{ Number(item.unit_cost_usd).toFixed(2) }}</td>
              <td class="px-3 py-2">{{ item.reference }}</td>
            </tr>
            <tr v-if="!props.recent || props.recent.length === 0">
              <td colspan="6" class="px-3 py-6 text-center text-muted-foreground">No recent deductions.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <ConfirmDialog ref="confirmDialog" />
</template>
