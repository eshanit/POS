<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import * as productsRoutes from '@/routes/products'

defineOptions({ layout: AppLayout })

const { products, filters } = defineProps<{ products: any, filters: { search: string; sort_by: string; sort_direction: string } }>()

const search = ref(filters?.search || '')
const confirmDialog = ref<InstanceType<typeof ConfirmDialog>>()

let debounceTimeout: number | undefined
const debouncedSearch = (val: string | number) => {
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(() => {
    router.get(productsRoutes.index.url(), { search: String(val || ''), sort_by: filters.sort_by, sort_direction: filters.sort_direction }, { preserveState: true, replace: true })
  }, 300)
}

const sort = (column: string) => {
  const dir = filters.sort_by === column && filters.sort_direction === 'asc' ? 'desc' : 'asc'
  router.get(productsRoutes.index.url(), { search: filters.search, sort_by: column, sort_direction: dir }, { preserveState: true, replace: true })
}

const sortIndicator = (column: string) => {
  if (filters.sort_by !== column) return ''
  return filters.sort_direction === 'asc' ? ' \u25B2' : ' \u25BC'
}

const deleteForm = useForm({})

const destroy = (product: any) => {
  confirmDialog.value?.showConfirm({
    title: 'Delete Product',
    description: `Are you sure you want to delete "${product.name}"? This action cannot be undone.`,
    actionLabel: 'Delete',
    cancelLabel: 'Cancel',
    variant: 'destructive',
    onConfirm: () => {
      deleteForm.delete(productsRoutes.destroy.url(product.id))
    },
  })
}
</script>

<template>
  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold tracking-tight">Products</h1>
      <Link :href="productsRoutes.create()">
        <Button>Add Product</Button>
      </Link>
    </div>

    <div class="flex items-center gap-2">
      <Input v-model="search" @update:model-value="debouncedSearch" placeholder="Search products..." class="max-w-sm" />
    </div>

    <div class="overflow-x-auto rounded-lg border">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b bg-muted/50">
            <th class="px-4 py-3 text-left font-medium text-muted-foreground cursor-pointer hover:text-foreground select-none" @click="sort('sku')">SKU{{ sortIndicator('sku') }}</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground cursor-pointer hover:text-foreground select-none" @click="sort('name')">Name{{ sortIndicator('name') }}</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground cursor-pointer hover:text-foreground select-none" @click="sort('category_id')">Category{{ sortIndicator('category_id') }}</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground cursor-pointer hover:text-foreground select-none" @click="sort('supplier_id')">Supplier{{ sortIndicator('supplier_id') }}</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground cursor-pointer hover:text-foreground select-none" @click="sort('current_quantity')">Stock{{ sortIndicator('current_quantity') }}</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground cursor-pointer hover:text-foreground select-none" @click="sort('price_usd')">Price USD{{ sortIndicator('price_usd') }}</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground cursor-pointer hover:text-foreground select-none" @click="sort('price_zig')">Price ZIG{{ sortIndicator('price_zig') }}</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in products.data" :key="product.id" class="border-b last:border-0 hover:bg-muted/50">
            <td class="px-4 py-3 font-mono text-xs">{{ product.sku }}</td>
            <td class="px-4 py-3 font-medium">{{ product.name }}</td>
            <td class="px-4 py-3">{{ product.category?.name }}</td>
            <td class="px-4 py-3">{{ product.supplier?.name }}</td>
            <td class="px-4 py-3">
              <Badge v-if="product.current_quantity <= product.reorder_level" variant="destructive">
                {{ product.current_quantity }}
              </Badge>
              <span v-else>{{ product.current_quantity }}</span>
            </td>
            <td class="px-4 py-3">${{ Number(product.price_usd).toFixed(2) }}</td>
            <td class="px-4 py-3">ZIG {{ Number(product.price_zig).toFixed(2) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <Link :href="productsRoutes.show(product.id)">
                  <Button variant="ghost" size="sm">View</Button>
                </Link>
                <Link :href="productsRoutes.edit(product.id)">
                  <Button variant="outline" size="sm">Edit</Button>
                </Link>
                <Button variant="destructive" size="sm" @click="destroy(product)" :disabled="deleteForm.processing">Delete</Button>
              </div>
            </td>
          </tr>
          <tr v-if="!products.data?.length">
            <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">No products found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="products.links?.length" class="flex items-center justify-center gap-1">
      <component
        :is="link.url ? Link : 'span'"
        v-for="link in products.links"
        :key="link.label"
        :href="link.url || '#'"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-sm transition-colors hover:bg-accent"
        :class="{
          'bg-primary text-primary-foreground': link.active,
          'pointer-events-none opacity-50': !link.url,
        }"
        v-html="link.label"
      />
    </div>

    <ConfirmDialog ref="confirmDialog" />
  </div>
</template>
