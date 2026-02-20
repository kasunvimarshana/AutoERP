<template>
  <div class="space-y-4">
    <PageHeader title="Point of Sale" subtitle="POS transactions and location summaries">
      <template #actions>
        <div class="flex gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="switchTab(tab.key)"
            :class="activeTab === tab.key
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-100'"
            class="px-4 py-1.5 rounded-lg text-sm font-medium border transition-colors"
          >
            {{ tab.label }}
          </button>
        </div>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <!-- Transactions -->
    <div v-else-if="activeTab === 'transactions'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="transactions.length === 0" icon="🖥️" title="No POS transactions found" />
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref #</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ tx.reference_number }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ tx.location?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-sm"><StatusBadge :status="tx.status" /></td>
            <td class="px-4 py-3 text-sm text-gray-500 capitalize">{{ tx.payment_method ?? '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ tx.total_amount }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ tx.created_at?.substring(0, 10) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- POS Summary by Location -->
    <div v-else-if="activeTab === 'summary'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="summary.length === 0" icon="📊" title="No summary data available" />
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Transactions</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Sales</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in summary" :key="row.location_id ?? row.location" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ row.location ?? row.location_id }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ row.transaction_count }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ row.total_sales }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { posService } from '@/services/pos';
import type { PosSummaryRow } from '@/services/pos';
import type { PosTransaction } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const tabs = [
  { key: 'transactions', label: 'Transactions' },
  { key: 'summary', label: 'Sales Summary' },
];

const activeTab = ref<string>('transactions');
const transactions = ref<PosTransaction[]>([]);
const summary = ref<PosSummaryRow[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

async function loadData(): Promise<void> {
  loading.value = true;
  error.value = null;
  try {
    if (activeTab.value === 'transactions') {
      const { data } = await posService.listTransactions();
      transactions.value = Array.isArray(data) ? data : (data as { data: PosTransaction[] }).data;
    } else {
      const { data } = await posService.getSummary();
      summary.value = Array.isArray(data) ? data : (data as { data: PosSummaryRow[] }).data;
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message ?? 'Failed to load POS data.';
  } finally {
    loading.value = false;
  }
}

function switchTab(key: string): void {
  activeTab.value = key;
  void loadData();
}

onMounted(() => void loadData());
</script>
