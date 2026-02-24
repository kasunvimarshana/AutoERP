<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Logistics</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage carriers and delivery orders.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Logistics tabs">
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
            <span
              v-if="tab.count !== null"
              :class="activeTab === tab.key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
              class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold"
            >{{ tab.count }}</span>
          </button>
        </nav>
      </div>

      <div
        v-if="logistics.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ logistics.error }}
      </div>

      <!-- Carriers tab -->
      <div v-if="activeTab === 'carriers'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Carriers table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tracking URL</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="logistics.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="logistics.carriers.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No carriers found.</td>
                </tr>
                <tr
                  v-for="carrier in logistics.carriers"
                  v-else
                  :key="carrier.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ carrier.name }}</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ carrier.code ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ carrier.tracking_url ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="carrier.status ?? 'active'" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="logistics.carriersMeta" @change="logistics.fetchCarriers" />
        </div>
      </div>

      <!-- Delivery Orders tab -->
      <div v-if="activeTab === 'delivery-orders'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Delivery Orders table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Carrier</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Scheduled Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="logistics.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="logistics.deliveryOrders.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No delivery orders found.</td>
                </tr>
                <tr
                  v-for="order in logistics.deliveryOrders"
                  v-else
                  :key="order.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ order.reference ?? order.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ order.carrier_name ?? order.carrier_id ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="order.status" /></td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(order.scheduled_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="logistics.deliveryMeta" @change="logistics.fetchDeliveryOrders" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useLogisticsStore } from '@/stores/logistics';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const logistics = useLogisticsStore();
const activeTab = ref('carriers');

const tabs = computed(() => [
    { key: 'carriers', label: 'Carriers', count: logistics.carriersMeta.total || null },
    { key: 'delivery-orders', label: 'Delivery Orders', count: logistics.deliveryMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'carriers') logistics.fetchCarriers();
    else if (key === 'delivery-orders') logistics.fetchDeliveryOrders();
}

onMounted(() => logistics.fetchCarriers());
</script>
