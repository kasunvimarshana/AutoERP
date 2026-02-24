<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Purchase</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage vendors, purchase orders, requisitions, and goods receipts.</p>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Purchase tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :class="[
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600',
              'whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors',
            ]"
            @click="switchTab(tab.key)"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <div
        v-if="purchase.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ purchase.error }}
      </div>

      <!-- Purchase Orders tab -->
      <div v-if="activeTab === 'orders'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Purchase orders table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PO Number</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Vendor</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Order Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="purchase.ordersLoading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="purchase.orders.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No purchase orders found.</td>
                </tr>
                <tr
                  v-for="order in purchase.orders"
                  v-else
                  :key="order.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ order.po_number ?? order.reference ?? order.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ order.vendor_name ?? order.vendor?.name ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="order.status" /></td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.total) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(order.order_date ?? order.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="purchase.meta" @change="purchase.fetchOrders" />
        </div>
      </div>

      <!-- Purchase Requisitions tab -->
      <div v-if="activeTab === 'requisitions'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Purchase requisitions table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PR Number</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Department</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Amount</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Required By</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Requested</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="purchase.requisitionsLoading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="purchase.requisitions.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No purchase requisitions found.</td>
                </tr>
                <tr
                  v-for="pr in purchase.requisitions"
                  v-else
                  :key="pr.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-indigo-600 dark:text-indigo-400 font-mono">{{ pr.number }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ pr.department ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="pr.status" /></td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(pr.total_amount) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(pr.required_by) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(pr.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="purchase.requisitionMeta" @change="purchase.fetchRequisitions" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { usePurchaseStore } from '@/stores/purchase';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatCurrency } = useFormatters();

const purchase   = usePurchaseStore();
const activeTab  = ref('orders');

const tabs = [
    { key: 'orders',       label: 'Purchase Orders' },
    { key: 'requisitions', label: 'Requisitions'    },
];

function switchTab(key) {
    activeTab.value = key;
    if (key === 'orders')       purchase.fetchOrders();
    if (key === 'requisitions') purchase.fetchRequisitions();
}

onMounted(() => purchase.fetchOrders());
</script>

