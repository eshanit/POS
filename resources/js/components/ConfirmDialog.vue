<script setup lang="ts">
import { ref } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

export interface ConfirmOptions {
  title: string
  description: string
  actionLabel?: string
  cancelLabel?: string
  onConfirm: () => void
  variant?: 'default' | 'destructive'
}

const open = ref(false)
const options = ref<ConfirmOptions>({
  title: '',
  description: '',
  actionLabel: 'Confirm',
  cancelLabel: 'Cancel',
  onConfirm: () => {},
  variant: 'destructive',
})

const showConfirm = (opts: ConfirmOptions) => {
  options.value = {
    actionLabel: 'Confirm',
    cancelLabel: 'Cancel',
    variant: 'destructive',
    ...opts,
  }
  open.value = true
}

const handleConfirm = () => {
  options.value.onConfirm()
  open.value = false
}

const handleCancel = () => {
  open.value = false
}

defineExpose({
  showConfirm,
})
</script>

<template>
  <Dialog :open="open" @update:open="open = $event">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ options.title }}</DialogTitle>
        <DialogDescription>{{ options.description }}</DialogDescription>
      </DialogHeader>
      <div class="flex gap-3 justify-end">
        <Button variant="outline" @click="handleCancel">{{ options.cancelLabel }}</Button>
        <Button
          @click="handleConfirm"
          :variant="options.variant === 'destructive' ? 'destructive' : 'default'"
        >
          {{ options.actionLabel }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>
