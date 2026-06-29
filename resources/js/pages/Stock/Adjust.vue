<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import stockRoutes from '@/routes/stock'

defineOptions({ layout: AppLayout })

defineProps<{ products: any[] }>()

const form = useForm({
  product_id: '' as number | string,
  quantity: '' as number | string,
  date: new Date().toISOString().split('T')[0],
  notes: ''
})

function submit() {
  form.post(stockRoutes.adjust.store.url())
}
</script>

<template>
  <div class="max-w-lg mx-auto space-y-6">
    <h1 class="text-2xl font-bold">Stock Adjustment</h1>

    <div class="space-y-4">
      <div class="space-y-2">
        <Label for="product_id">Product</Label>
        <Select v-model="form.product_id">
          <SelectTrigger id="product_id">
            <SelectValue placeholder="Select product" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="product in products" :key="product.id" :value="product.id.toString()">
              {{ product.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="space-y-2">
        <Label for="quantity">Quantity</Label>
        <Input id="quantity" v-model="form.quantity" type="number" step="1" placeholder="Use negative values for removals" />
        <p class="text-xs text-muted-foreground">Use negative values to remove stock.</p>
      </div>

      <div class="space-y-2">
        <Label for="date">Date</Label>
        <Input id="date" v-model="form.date" type="date" />
      </div>

      <div class="space-y-2">
        <Label for="notes">Notes</Label>
        <Input id="notes" v-model="form.notes" placeholder="Reason for adjustment" />
      </div>
    </div>

    <Button @click="submit" :disabled="form.processing" class="w-full">
      Save Adjustment
    </Button>
  </div>
</template>
