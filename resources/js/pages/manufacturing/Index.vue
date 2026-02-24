<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page heading -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manufacturing</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage bills of materials and work orders.</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Manufacturing tabs">
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

      <!-- Error alert -->
      <div
        v-if="mfg.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ mfg.error }}
      </div>

      <!-- Bills of Materials tab -->
      <div v-if="activeTab === 'boms'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Bills of Materials table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Product</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Components</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Version</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="mfg.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="mfg.boms.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No bills of materials found.</td>
                </tr>
                <tr
                  v-for="bom in mfg.boms"
                  v-else
                  :key="bom.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ bom.product_name ?? bom.product_id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ bom.reference ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ bom.quantity ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ bom.lines_count ?? (bom.lines?.length ?? '—') }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ bom.version ?? '1' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="mfg.bomsMeta" @change="mfg.fetchBoms" />
        </div>
      </div>

      <!-- Work Orders tab -->
      <div v-if="activeTab === 'work-orders'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Work Orders table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Product</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Scheduled</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="mfg.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="mfg.workOrders.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No work orders found.</td>
                </tr>
                <tr
                  v-for="wo in mfg.workOrders"
                  v-else
                  :key="wo.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ wo.reference ?? wo.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ wo.product_name ?? wo.product_id }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ wo.quantity_to_produce ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <StatusBadge :status="wo.status" />
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(wo.scheduled_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="mfg.workOrdersMeta" @change="mfg.fetchWorkOrders" />
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
import { useManufacturingStore } from '@/stores/manufacturing';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const mfg = useManufacturingStore();
const activeTab = ref('boms');

const tabs = computed(() => [
    { key: 'boms', label: 'Bills of Materials', count: mfg.bomsMeta.total || null },
    { key: 'work-orders', label: 'Work Orders', count: mfg.workOrdersMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'boms') mfg.fetchBoms();
    else if (key === 'work-orders') mfg.fetchWorkOrders();
}

onMounted(() => mfg.fetchBoms());
</script>
