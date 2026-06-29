<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import InputError from '@/components/InputError.vue'
import * as suppliersRoutes from '@/routes/suppliers'

defineOptions({ layout: AppLayout })

defineProps<{ suppliers: any[] }>()

const dialogOpen = ref(false)
const editingSupplier = ref<any>(null)
const confirmDialog = ref<InstanceType<typeof ConfirmDialog>>()

const form = useForm({
  name: '',
  contact_person: '',
  phone: '',
  email: '',
  address: '',
})

const openCreate = () => {
  editingSupplier.value = null
  form.reset()
  form.clearErrors()
  dialogOpen.value = true
}

const openEdit = (supplier: any) => {
  editingSupplier.value = supplier
  form.name = supplier.name
  form.contact_person = supplier.contact_person || ''
  form.phone = supplier.phone || ''
  form.email = supplier.email || ''
  form.address = supplier.address || ''
  form.clearErrors()
  dialogOpen.value = true
}

const submit = () => {
  if (editingSupplier.value) {
    form.put(suppliersRoutes.update.url(editingSupplier.value.id), {
      onSuccess: () => { dialogOpen.value = false; form.reset() },
    })
  } else {
    form.post(suppliersRoutes.store.url(), {
      onSuccess: () => { dialogOpen.value = false; form.reset() },
    })
  }
}

const destroy = (supplier: any) => {
  confirmDialog.value?.showConfirm({
    title: 'Delete Supplier',
    description: `Are you sure you want to delete "${supplier.name}"? This action cannot be undone.`,
    actionLabel: 'Delete',
    cancelLabel: 'Cancel',
    variant: 'destructive',
    onConfirm: () => {
      router.delete(suppliersRoutes.destroy.url(supplier.id))
    },
  })
}
</script>

<template>
  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold tracking-tight">Suppliers</h1>
      <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
        <DialogTrigger as-child>
          <Button @click="openCreate">Add Supplier</Button>
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{{ editingSupplier ? 'Edit Supplier' : 'Add Supplier' }}</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="grid gap-2">
              <Input v-model="form.name" placeholder="Company name" required />
              <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
              <Input v-model="form.contact_person" placeholder="Contact person" />
              <InputError :message="form.errors.contact_person" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Input v-model="form.phone" placeholder="Phone" />
                <InputError :message="form.errors.phone" />
              </div>
              <div class="grid gap-2">
                <Input v-model="form.email" type="email" placeholder="Email" />
                <InputError :message="form.errors.email" />
              </div>
            </div>
            <div class="grid gap-2">
              <Input v-model="form.address" placeholder="Address" />
              <InputError :message="form.errors.address" />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
              <Button type="submit" :disabled="form.processing">{{ editingSupplier ? 'Update' : 'Create' }}</Button>
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
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Contact Person</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Phone</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Email</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="supplier in suppliers" :key="supplier.id" class="border-b last:border-0 hover:bg-muted/50">
            <td class="px-4 py-3 font-medium">{{ supplier.name }}</td>
            <td class="px-4 py-3">{{ supplier.contact_person }}</td>
            <td class="px-4 py-3">{{ supplier.phone }}</td>
            <td class="px-4 py-3">{{ supplier.email }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" @click="openEdit(supplier)">Edit</Button>
                <Button variant="destructive" size="sm" @click="destroy(supplier)">Delete</Button>
              </div>
            </td>
          </tr>
          <tr v-if="!suppliers.length">
            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No suppliers yet.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <ConfirmDialog ref="confirmDialog" />
  </div>
</template>
