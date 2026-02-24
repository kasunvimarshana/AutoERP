<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reporting</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage configurable dashboards and custom reports.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Reporting tabs">
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
        v-if="reporting.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ reporting.error }}
      </div>

      <!-- Dashboards tab -->
      <div v-if="activeTab === 'dashboards'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Reporting Dashboards table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Layout</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Refresh (s)</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Shared</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="reporting.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="reporting.dashboards.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No dashboards found.</td>
                </tr>
                <tr
                  v-for="dashboard in reporting.dashboards"
                  v-else
                  :key="dashboard.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ dashboard.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ dashboard.layout ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ dashboard.refresh_interval ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm">
                    <span :class="dashboard.is_shared ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'">
                      {{ dashboard.is_shared ? 'Yes' : 'No' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(dashboard.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="reporting.dashboardsMeta" @change="reporting.fetchDashboards" />
        </div>
      </div>

      <!-- Reports tab -->
      <div v-if="activeTab === 'reports'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Reports table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Data Source</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="reporting.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="reporting.reports.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No reports found.</td>
                </tr>
                <tr
                  v-for="report in reporting.reports"
                  v-else
                  :key="report.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ report.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ report.report_type ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">{{ report.data_source ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(report.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="reporting.reportsMeta" @change="reporting.fetchReports" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { useReportingStore } from '@/stores/reporting';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const reporting = useReportingStore();
const activeTab = ref('dashboards');

const tabs = computed(() => [
    { key: 'dashboards', label: 'Dashboards', count: reporting.dashboardsMeta.total || null },
    { key: 'reports', label: 'Reports', count: reporting.reportsMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'dashboards') reporting.fetchDashboards();
    else if (key === 'reports') reporting.fetchReports();
}

onMounted(() => reporting.fetchDashboards());
</script>
