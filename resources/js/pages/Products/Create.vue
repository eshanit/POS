<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import InputError from '@/components/InputError.vue'
import * as productsRoutes from '@/routes/products'

defineOptions({ layout: AppLayout })

const props = defineProps<{ categories: any[], suppliers: any[] }>()

const form = useForm({
  name: '',
  sku: '',
  description: '',
  category_id: '',
  supplier_id: '',
  cost_price: '',
  price_usd: '',
  price_zig: '',
  reorder_level: '',
  expiry_date: '',
})

const submit = () => {
  form.post(productsRoutes.store.url())
}
</script>

<template>
  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold tracking-tight">Add Product</h1>
      <Link :href="productsRoutes.index()">
        <Button variant="outline">Cancel</Button>
      </Link>
    </div>

    <form @submit.prevent="submit" class="max-w-2xl space-y-6">
      <div class="grid gap-2">
        <Label for="name">Name</Label>
        <Input id="name" v-model="form.name" required />
        <InputError :message="form.errors.name" />
      </div>

      <div class="grid gap-2">
        <Label for="sku">SKU</Label>
        <Input id="sku" v-model="form.sku" required />
        <InputError :message="form.errors.sku" />
      </div>

      <div class="grid gap-2">
        <Label for="description">Description</Label>
        <textarea
          id="description"
          v-model="form.description"
          class="border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-[80px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
        />
        <InputError :message="form.errors.description" />
      </div>

      <div class="grid gap-2">
        <Label for="category_id">Category</Label>
        <Select v-model="form.category_id">
          <SelectTrigger>
            <SelectValue placeholder="Select category" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
              {{ cat.name }}
            </SelectItem>
          </SelectContent>
        </Select>
        <InputError :message="form.errors.category_id" />
      </div>

      <div class="grid gap-2">
        <Label for="supplier_id">Supplier</Label>
        <Select v-model="form.supplier_id">
          <SelectTrigger>
            <SelectValue placeholder="Select supplier" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">
              {{ supplier.name }}
            </SelectItem>
          </SelectContent>
        </Select>
        <InputError :message="form.errors.supplier_id" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
          <Label for="cost_price">Cost Price</Label>
          <Input id="cost_price" v-model="form.cost_price" type="number" step="0.01" />
          <InputError :message="form.errors.cost_price" />
        </div>

        <div class="grid gap-2">
          <Label for="reorder_level">Reorder Level</Label>
          <Input id="reorder_level" v-model="form.reorder_level" type="number" />
          <InputError :message="form.errors.reorder_level" />
        </div>
        <div class="grid gap-2">
          <Label for="expiry_date">Expiry Date</Label>
          <Input id="expiry_date" v-model="form.expiry_date" type="date" />
          <InputError :message="form.errors.expiry_date" />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
          <Label for="price_usd">Price USD</Label>
          <Input id="price_usd" v-model="form.price_usd" type="number" step="0.01" />
          <InputError :message="form.errors.price_usd" />
        </div>

        <div class="grid gap-2">
          <Label for="price_zig">Price ZIG</Label>
          <Input id="price_zig" v-model="form.price_zig" type="number" step="0.01" />
          <InputError :message="form.errors.price_zig" />
        </div>
      </div>

      <div class="flex items-center gap-4">
        <Button type="submit" :disabled="form.processing">Save Product</Button>
        <Link :href="productsRoutes.index()">
          <Button variant="outline" type="button">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</template>
