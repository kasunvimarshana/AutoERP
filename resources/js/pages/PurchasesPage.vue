<template>
  <div class="space-y-4">
    <PageHeader title="Purchases" subtitle="Procurement and supplier management">
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

    <!-- Purchase Orders -->
    <div v-else-if="activeTab === 'orders'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="purchases.length === 0" icon="🚚" title="No purchase orders found" />
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref #</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="purchase in purchases" :key="purchase.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ purchase.reference_number }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ purchase.supplier?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-sm"><StatusBadge :status="purchase.status" /></td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ purchase.total_amount }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ purchase.created_at?.substring(0, 10) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Purchase Returns -->
    <div v-else-if="activeTab === 'returns'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="returns.length === 0" icon="↩️" title="No purchase returns found" />
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref #</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="ret in returns" :key="ret.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ ret.reference_number }}</td>
            <td class="px-4 py-3 text-sm"><StatusBadge :status="ret.status" /></td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ ret.total_amount }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ ret.created_at?.substring(0, 10) }}</td>
            <td class="px-4 py-3 text-right">
              <button
                v-if="ret.status !== 'cancelled' && auth.hasPermission('purchase.update')"
                class="text-xs text-red-500 hover:underline"
                :disabled="actionId === ret.id"
                @click="cancelReturn(ret)"
              >Cancel</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { purchaseService } from '@/services/purchases';
import type { PurchaseReturn } from '@/services/purchases';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import type { Purchase } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const auth = useAuthStore();
const notify = useNotificationStore();

const tabs = [
  { key: 'orders', label: 'Purchase Orders' },
  { key: 'returns', label: 'Purchase Returns' },
];

const activeTab = ref<string>('orders');
const purchases = ref<Purchase[]>([]);
const returns = ref<PurchaseReturn[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const actionId = ref<number | null>(null);

async function loadData(): Promise<void> {
  loading.value = true;
  error.value = null;
  try {
    if (activeTab.value === 'orders') {
      const { data } = await purchaseService.list();
      purchases.value = Array.isArray(data) ? data : (data as { data: Purchase[] }).data;
    } else {
      const { data } = await purchaseService.listReturns();
      returns.value = Array.isArray(data) ? data : (data as { data: PurchaseReturn[] }).data;
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message ?? 'Failed to load purchases.';
  } finally {
    loading.value = false;
  }
}

function switchTab(key: string): void {
  activeTab.value = key;
  void loadData();
}

onMounted(() => void loadData());

async function cancelReturn(ret: PurchaseReturn): Promise<void> {
  if (!confirm('Cancel this purchase return?')) return;
  actionId.value = ret.id;
  try {
    await purchaseService.cancelReturn(ret.id);
    notify.success('Purchase return cancelled.');
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to cancel return.');
  } finally {
    actionId.value = null;
  }
}
</script>
