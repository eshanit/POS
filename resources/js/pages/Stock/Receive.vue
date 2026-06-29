<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import InputError from '@/components/InputError.vue'
import stockRoutes from '@/routes/stock'

defineOptions({ layout: AppLayout })

defineProps<{ products: any[] }>()

const form = useForm({
  product_id: '',
  quantity: '',
  unit_cost_usd: '',
  date: new Date().toISOString().split('T')[0],
  reference: '',
  notes: '',
})

const submit = () => {
  form.post(stockRoutes.receive.store.url())
}
</script>

<template>
  <div class="flex flex-col gap-6 p-4">
    <h1 class="text-2xl font-bold tracking-tight">Receive Stock</h1>

    <form @submit.prevent="submit" class="max-w-2xl space-y-6">
      <div class="grid gap-2">
        <Label for="product_id">Product</Label>
        <Select v-model="form.product_id">
          <SelectTrigger>
            <SelectValue placeholder="Select product" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="product in products" :key="product.id" :value="String(product.id)">
              {{ product.name }} ({{ product.sku }})
            </SelectItem>
          </SelectContent>
        </Select>
        <InputError :message="form.errors.product_id" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
          <Label for="quantity">Quantity</Label>
          <Input id="quantity" v-model="form.quantity" type="number" min="1" required />
          <InputError :message="form.errors.quantity" />
        </div>

        <div class="grid gap-2">
          <Label for="unit_cost_usd">Unit Cost (USD)</Label>
          <Input id="unit_cost_usd" v-model="form.unit_cost_usd" type="number" step="0.01" />
          <InputError :message="form.errors.unit_cost_usd" />
        </div>
      </div>

      <div class="grid gap-2">
        <Label for="date">Date</Label>
        <Input id="date" v-model="form.date" type="date" required />
        <InputError :message="form.errors.date" />
      </div>

      <div class="grid gap-2">
        <Label for="reference">Reference</Label>
        <Input id="reference" v-model="form.reference" placeholder="Invoice or PO number" />
        <InputError :message="form.errors.reference" />
      </div>

      <div class="grid gap-2">
        <Label for="notes">Notes</Label>
        <textarea
          id="notes"
          v-model="form.notes"
          class="border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-[80px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
        />
        <InputError :message="form.errors.notes" />
      </div>

      <Button type="submit" :disabled="form.processing">Receive Stock</Button>
    </form>
  </div>
</template>
