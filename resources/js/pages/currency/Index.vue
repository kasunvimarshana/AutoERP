<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Currency Management</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage ISO 4217 currencies and exchange rates for multi-currency operations.</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex gap-6" aria-label="Currency tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="[
              'pb-3 text-sm font-medium border-b-2 transition-colors',
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300',
            ]"
            @click="setTab(tab.key)"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Currencies Tab -->
      <template v-if="activeTab === 'currencies'">
        <div
          v-if="currency.error"
          class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
          role="alert"
        >
          {{ currency.error }}
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Currencies table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Symbol</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Decimal Places</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="currency.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="currency.currencies.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No currencies found.</td>
                </tr>
                <tr
                  v-for="c in currency.currencies"
                  v-else
                  :key="c.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ c.code }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ c.name }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ c.symbol || '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ c.decimal_places ?? 2 }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="c.is_active ? 'active' : 'inactive'" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="currency.meta" @change="currency.fetchCurrencies" />
        </div>
      </template>

      <!-- Exchange Rates Tab -->
      <template v-if="activeTab === 'exchange-rates'">
        <div
          v-if="currency.ratesError"
          class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
          role="alert"
        >
          {{ currency.ratesError }}
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Exchange Rates table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">From</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">To</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rate</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Source</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Effective Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="currency.ratesLoading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="currency.exchangeRates.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No exchange rates found.</td>
                </tr>
                <tr
                  v-for="rate in currency.exchangeRates"
                  v-else
                  :key="rate.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ rate.from_currency_code }}</td>
                  <td class="px-4 py-3 text-sm font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ rate.to_currency_code }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatRate(rate.rate) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ rate.source ?? 'manual' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(rate.effective_date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="currency.ratesMeta" @change="currency.fetchExchangeRates" />
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useCurrencyStore } from '@/stores/currency';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const currency = useCurrencyStore();

const tabs = [
    { key: 'currencies', label: 'Currencies' },
    { key: 'exchange-rates', label: 'Exchange Rates' },
];

const activeTab = ref('currencies');

function setTab(key) {
    activeTab.value = key;
    if (key === 'currencies') currency.fetchCurrencies();
    if (key === 'exchange-rates') currency.fetchExchangeRates();
}

function formatRate(value) {
    if (value === null || value === undefined) return '—';
    return parseFloat(value).toFixed(6);
}

onMounted(() => currency.fetchCurrencies());
</script>
