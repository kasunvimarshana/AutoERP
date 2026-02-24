<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Field Service</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage service teams and field service orders.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Field Service tabs">
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
        v-if="fs.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ fs.error }}
      </div>

      <!-- Service Orders tab -->
      <div v-if="activeTab === 'orders'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Service Orders table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Team</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Technician</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Cost</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="fs.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="fs.orders.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No service orders found.</td>
                </tr>
                <tr
                  v-for="order in fs.orders"
                  v-else
                  :key="order.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ order.reference ?? order.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ order.team_name ?? order.service_team_id ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ order.technician_name ?? order.technician_id ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(totalCost(order)) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="order.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="fs.ordersMeta" @change="fs.fetchOrders" />
        </div>
      </div>

      <!-- Service Teams tab -->
      <div v-if="activeTab === 'teams'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Service Teams table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="fs.loading">
                  <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="fs.teams.length === 0">
                  <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No service teams found.</td>
                </tr>
                <tr
                  v-for="team in fs.teams"
                  v-else
                  :key="team.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ team.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ team.description ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="fs.teamsMeta" @change="fs.fetchTeams" />
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
import { useFieldServiceStore } from '@/stores/fieldService';
import { useFormatters } from '@/composables/useFormatters';
const { formatCurrency } = useFormatters();

const fs = useFieldServiceStore();
const activeTab = ref('orders');

const tabs = computed(() => [
    { key: 'orders', label: 'Service Orders', count: fs.ordersMeta.total || null },
    { key: 'teams', label: 'Teams', count: fs.teamsMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'orders') fs.fetchOrders();
    else if (key === 'teams') fs.fetchTeams();
}

function totalCost(order) {
    const labor = parseFloat(order.labor_cost ?? 0);
    const parts = parseFloat(order.parts_cost ?? 0);
    return labor + parts;
}

onMounted(() => fs.fetchOrders());
</script>
