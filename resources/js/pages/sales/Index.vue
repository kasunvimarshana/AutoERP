<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage quotations, sales orders, customers, and price lists.</p>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-6" aria-label="Sales tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="[
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400',
              'whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors'
            ]"
            @click="switchTab(tab.key)"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <div
        v-if="sales.error || sales.priceListsError"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ sales.error || sales.priceListsError }}
      </div>

      <!-- Orders tab -->
      <div v-if="activeTab === 'orders'" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Sales orders table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Customer</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="sales.loading">
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="sales.orders.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No orders found.</td>
              </tr>
              <tr
                v-for="order in sales.orders"
                v-else
                :key="order.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3 text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ order.reference ?? order.id }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ order.customer_name ?? order.customer?.name ?? '—' }}</td>
                <td class="px-4 py-3"><StatusBadge :status="order.status" /></td>
                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.total) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="sales.meta" @change="sales.fetchOrders" />
      </div>

      <!-- Price Lists tab -->
      <div v-if="activeTab === 'price-lists'" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Price lists table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Currency</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Valid From</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Valid To</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Customer Group</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="sales.priceListsLoading">
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="sales.priceLists.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No price lists found.</td>
              </tr>
              <tr
                v-for="pl in sales.priceLists"
                v-else
                :key="pl.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ pl.name }}</td>
                <td class="px-4 py-3 text-sm font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ pl.currency_code }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(pl.valid_from) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(pl.valid_to) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ pl.customer_group ?? '—' }}</td>
                <td class="px-4 py-3"><StatusBadge :status="pl.is_active ? 'active' : 'inactive'" /></td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="sales.priceListsMeta" @change="sales.fetchPriceLists" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useSalesStore } from '@/stores/sales';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatCurrency } = useFormatters();

const sales = useSalesStore();

const tabs = [
    { key: 'orders', label: 'Sales Orders' },
    { key: 'price-lists', label: 'Price Lists' },
];
const activeTab = ref('orders');

function switchTab(key) {
    activeTab.value = key;
    if (key === 'price-lists' && sales.priceLists.length === 0) {
        sales.fetchPriceLists();
    }
}

onMounted(() => sales.fetchOrders());
</script>
