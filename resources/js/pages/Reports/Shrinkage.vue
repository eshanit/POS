<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import * as reportsRoutes from '@/routes/reports'

defineOptions({ layout: AppLayout })

const props = defineProps<{ groups: any[]; filters: any }>()

function applyFilters() {
  router.get('/reports/shrinkage', {
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold">Shrinkage & Wastage Report</h1>

    <div class="flex gap-4">
      <div class="space-y-1">
        <label class="text-sm font-medium">Date From</label>
        <Input type="date" :model-value="filters.date_from" @update:model-value="filters.date_from = String($event); applyFilters()" />
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Date To</label>
        <Input type="date" :model-value="filters.date_to" @update:model-value="filters.date_to = String($event); applyFilters()" />
      </div>
      <div class="ml-auto flex items-end">
        <a :href="(typeof window !== 'undefined' ? '/reports/shrinkage/export' + window.location.search : '/reports/shrinkage/export')">
          <Button>Export CSV</Button>
        </a>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <Card v-for="group in groups" :key="group.reason">
        <CardHeader>
          <CardTitle class="text-lg">{{ group.reason }}</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-2xl font-bold">{{ group.total_qty }}</p>
          <p class="text-sm text-muted-foreground">Lost: USD {{ Number(group.total_loss).toFixed(2) }}</p>
        </CardContent>
      </Card>
    </div>

    <div class="rounded-md border overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-muted/50 text-sm text-muted-foreground">
            <th class="text-left px-4 py-3 font-medium">Reason</th>
            <th class="text-right px-4 py-3 font-medium">Total Qty</th>
            <th class="text-right px-4 py-3 font-medium">Total Loss (USD)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="g in groups" :key="g.reason" class="border-t text-sm">
            <td class="px-4 py-3">{{ g.reason }}</td>
            <td class="px-4 py-3 text-right">{{ g.total_qty }}</td>
            <td class="px-4 py-3 text-right">USD {{ Number(g.total_loss).toFixed(2) }}</td>
          </tr>
          <tr v-if="groups.length === 0">
            <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">No deductions found for the selected period.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
