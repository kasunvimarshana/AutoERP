<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contracts</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage contract lifecycle, parties, and payment terms.</p>
        </div>
      </div>

      <div
        v-if="contract.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ contract.error }}
      </div>

      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Contracts table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Party</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Value</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Start Date</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">End Date</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="contract.loading">
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="contract.contracts.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No contracts found.</td>
              </tr>
              <tr
                v-for="c in contract.contracts"
                v-else
                :key="c.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ c.title }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ c.party_name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(c.total_value) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(c.start_date) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(c.end_date) }}</td>
                <td class="px-4 py-3"><StatusBadge :status="c.status" /></td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="contract.meta" @change="contract.fetchContracts" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useContractStore } from '@/stores/contract';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatCurrency } = useFormatters();

const contract = useContractStore();

onMounted(() => contract.fetchContracts());
</script>
