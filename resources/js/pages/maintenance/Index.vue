<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Maintenance</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage equipment, maintenance requests, and work orders.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Maintenance tabs">
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
        v-if="maintenance.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ maintenance.error }}
      </div>

      <!-- Equipment tab -->
      <div v-if="activeTab === 'equipment'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Equipment table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Serial Number</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Location</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="maintenance.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="maintenance.equipment.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No equipment found.</td>
                </tr>
                <tr
                  v-for="eq in maintenance.equipment"
                  v-else
                  :key="eq.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ eq.name }}</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ eq.serial_number ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ eq.location ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="eq.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="maintenance.equipmentMeta" @change="maintenance.fetchEquipment" />
        </div>
      </div>

      <!-- Requests tab -->
      <div v-if="activeTab === 'requests'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Maintenance Requests table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Equipment</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Priority</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Requested</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="maintenance.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="maintenance.requests.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No maintenance requests found.</td>
                </tr>
                <tr
                  v-for="req in maintenance.requests"
                  v-else
                  :key="req.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ req.description }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ req.equipment_name ?? req.equipment_id ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="req.priority" /></td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(req.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="maintenance.requestsMeta" @change="maintenance.fetchRequests" />
        </div>
      </div>

      <!-- Orders tab -->
      <div v-if="activeTab === 'orders'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Maintenance Orders table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Equipment</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Cost</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="maintenance.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="maintenance.orders.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No maintenance orders found.</td>
                </tr>
                <tr
                  v-for="order in maintenance.orders"
                  v-else
                  :key="order.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ order.reference ?? order.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ order.equipment_name ?? order.equipment_id ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ order.type ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(totalCost(order)) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="order.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="maintenance.ordersMeta" @change="maintenance.fetchOrders" />
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
import { useMaintenanceStore } from '@/stores/maintenance';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatCurrency } = useFormatters();

const maintenance = useMaintenanceStore();
const activeTab = ref('equipment');

const tabs = computed(() => [
    { key: 'equipment', label: 'Equipment', count: maintenance.equipmentMeta.total || null },
    { key: 'requests', label: 'Requests', count: maintenance.requestsMeta.total || null },
    { key: 'orders', label: 'Work Orders', count: maintenance.ordersMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'equipment') maintenance.fetchEquipment();
    else if (key === 'requests') maintenance.fetchRequests();
    else if (key === 'orders') maintenance.fetchOrders();
}

function totalCost(order) {
    const labor = parseFloat(order.labor_cost ?? 0);
    const parts = parseFloat(order.parts_cost ?? 0);
    return labor + parts;
}

onMounted(() => maintenance.fetchEquipment());
</script>
