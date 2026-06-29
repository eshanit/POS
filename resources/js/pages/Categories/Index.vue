<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import InputError from '@/components/InputError.vue'
import * as categoriesRoutes from '@/routes/categories'

defineOptions({ layout: AppLayout })

defineProps<{ categories: any[] }>()

const dialogOpen = ref(false)
const editingCategory = ref<any>(null)
const confirmDialog = ref<InstanceType<typeof ConfirmDialog>>()

const form = useForm({ name: '' })

const openCreate = () => {
  editingCategory.value = null
  form.reset()
  form.clearErrors()
  dialogOpen.value = true
}

const openEdit = (category: any) => {
  editingCategory.value = category
  form.name = category.name
  form.clearErrors()
  dialogOpen.value = true
}

const submit = () => {
  if (editingCategory.value) {
    form.put(categoriesRoutes.update.url(editingCategory.value.id), {
      onSuccess: () => { dialogOpen.value = false; form.reset() },
    })
  } else {
    form.post(categoriesRoutes.store.url(), {
      onSuccess: () => { dialogOpen.value = false; form.reset() },
    })
  }
}

const destroy = (category: any) => {
  confirmDialog.value?.showConfirm({
    title: 'Delete Category',
    description: `Are you sure you want to delete "${category.name}"? This action cannot be undone.`,
    actionLabel: 'Delete',
    cancelLabel: 'Cancel',
    variant: 'destructive',
    onConfirm: () => {
      router.delete(categoriesRoutes.destroy.url(category.id))
    },
  })
}
</script>

<template>
  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold tracking-tight">Categories</h1>
      <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
        <DialogTrigger as-child>
          <Button @click="openCreate">Add Category</Button>
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{{ editingCategory ? 'Edit Category' : 'Add Category' }}</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="grid gap-2">
              <Input v-model="form.name" placeholder="Category name" required />
              <InputError :message="form.errors.name" />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
              <Button type="submit" :disabled="form.processing">{{ editingCategory ? 'Update' : 'Create' }}</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <div class="overflow-x-auto rounded-lg border">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b bg-muted/50">
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Products</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="category in categories" :key="category.id" class="border-b last:border-0 hover:bg-muted/50">
            <td class="px-4 py-3 font-medium">{{ category.name }}</td>
            <td class="px-4 py-3">{{ category.products_count ?? 0 }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" @click="openEdit(category)">Edit</Button>
                <Button variant="destructive" size="sm" @click="destroy(category)">Delete</Button>
              </div>
            </td>
          </tr>
          <tr v-if="!categories.length">
            <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">No categories yet.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <ConfirmDialog ref="confirmDialog" />
  </div>
</template>
