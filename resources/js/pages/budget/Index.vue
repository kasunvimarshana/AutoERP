<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Budget Management</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track departmental and project budgets with category-level spending and variance analysis.</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-6" aria-label="Budget tabs">
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

      <!-- Error banner -->
      <div
        v-if="budget.error || budget.varianceError"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ budget.error || budget.varianceError }}
      </div>

      <!-- Budgets tab -->
      <div v-if="activeTab === 'budgets'" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Budgets table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Period</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">From</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">To</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Planned</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="budget.loading">
                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="budget.budgets.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No budgets found.</td>
              </tr>
              <tr
                v-for="b in budget.budgets"
                v-else
                :key="b.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ b.name }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ b.period ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(b.start_date) }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(b.end_date) }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(b.total_planned) }}</td>
                <td class="px-4 py-3"><StatusBadge :status="b.status" /></td>
                <td class="px-4 py-3">
                  <button
                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                    @click="loadVariance(b.id)"
                  >
                    Variance
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="budget.meta" @change="budget.fetchBudgets" />
      </div>

      <!-- Variance tab -->
      <div v-if="activeTab === 'variance'">
        <div v-if="budget.varianceLoading" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 px-4 py-8 text-center text-sm text-gray-400">
          Loading variance report…
        </div>
        <div v-else-if="!budget.varianceReport" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 px-4 py-8 text-center text-sm text-gray-400">
          Select a budget from the Budgets tab to view its variance report.
        </div>
        <div v-else class="space-y-4">
          <!-- Summary header -->
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 px-6 py-4">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ budget.varianceReport.budget_name }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Budget vs Actuals</p>
              </div>
              <StatusBadge :status="budget.varianceReport.overspent ? 'overspent' : 'on_budget'" />
            </div>
            <div class="mt-4 grid grid-cols-3 gap-4">
              <div class="text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Planned</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ formatCurrency(budget.varianceReport.total_planned) }}</p>
              </div>
              <div class="text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Actual</p>
                <p class="mt-1 text-lg font-semibold" :class="budget.varianceReport.overspent ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                  {{ formatCurrency(budget.varianceReport.total_actual) }}
                </p>
              </div>
              <div class="text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Variance</p>
                <p class="mt-1 text-lg font-semibold" :class="budget.varianceReport.overspent ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                  {{ formatVariance(budget.varianceReport.total_variance) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Line-level variance table -->
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Budget variance lines">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Category</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Planned</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actual</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Variance</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Utilisation %</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <tr
                    v-for="line in budget.varianceReport.lines"
                    :key="line.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                    :class="line.overspent ? 'bg-red-50/30 dark:bg-red-900/10' : ''"
                  >
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ line.category }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ line.description ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(line.planned_amount) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium" :class="line.overspent ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'">
                      {{ formatCurrency(line.actual_amount) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-medium" :class="line.overspent ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                      {{ formatVariance(line.variance) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right">
                      <span v-if="line.utilisation_pct !== null" :class="parseFloat(line.utilisation_pct) > 100 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-700 dark:text-gray-300'">
                        {{ line.utilisation_pct }}%
                      </span>
                      <span v-else class="text-gray-400">—</span>
                    </td>
                    <td class="px-4 py-3">
                      <StatusBadge :status="line.overspent ? 'overspent' : 'on_budget'" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
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
import { useBudgetStore } from '@/stores/budget';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatCurrency } = useFormatters();

const budget = useBudgetStore();

const tabs = [
    { key: 'budgets', label: 'Budgets' },
    { key: 'variance', label: 'Variance Analysis' },
];
const activeTab = ref('budgets');

function switchTab(key) {
    activeTab.value = key;
}

function loadVariance(budgetId) {
    budget.fetchVarianceReport(budgetId);
    activeTab.value = 'variance';
}

function formatVariance(value) {
    if (value === null || value === undefined) return '—';
    const num = parseFloat(value);
    const formatted = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Math.abs(num));
    return num >= 0 ? `+${formatted}` : `-${formatted}`;
}

onMounted(() => budget.fetchBudgets());
</script>
