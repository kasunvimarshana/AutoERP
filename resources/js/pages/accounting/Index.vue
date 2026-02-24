<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Accounting</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Chart of accounts, journal entries, invoices, credit notes, and bank reconciliation.</p>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Accounting tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :class="[
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
              'whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors',
            ]"
            @click="switchTab(tab.key)"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <div
        v-if="accounting.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ accounting.error }}
      </div>

      <!-- Chart of Accounts -->
      <div v-if="activeTab === 'accounts'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Chart of accounts table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Balance</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="accounting.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="accounting.accounts.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No accounts found.</td>
                </tr>
                <tr
                  v-for="account in accounting.accounts"
                  v-else
                  :key="account.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ account.code }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ account.name }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="account.type" /></td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(account.balance) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="accounting.accountsMeta" @change="accounting.fetchAccounts" />
        </div>
      </div>

      <!-- Invoices -->
      <div v-if="activeTab === 'invoices'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Invoices table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Invoice #</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Party</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Due Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="accounting.loading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="accounting.invoices.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No invoices found.</td>
                </tr>
                <tr
                  v-for="invoice in accounting.invoices"
                  v-else
                  :key="invoice.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ invoice.invoice_number ?? invoice.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ invoice.party_name ?? invoice.customer_name ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ invoice.type ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="invoice.status" /></td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(invoice.total_amount ?? invoice.amount) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(invoice.due_date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="accounting.invoicesMeta" @change="accounting.fetchInvoices" />
        </div>
      </div>

      <!-- Credit Notes -->
      <div v-if="activeTab === 'credit_notes'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Credit notes table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">CN #</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Source Invoice</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Currency</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Issued</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="accounting.creditNotesLoading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="accounting.creditNotes.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No credit notes found.</td>
                </tr>
                <tr
                  v-for="cn in accounting.creditNotes"
                  v-else
                  :key="cn.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-purple-600 dark:text-purple-400 font-mono">{{ cn.number }}</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ cn.source_invoice_id ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="cn.status" /></td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-purple-600 dark:text-purple-400">{{ formatCurrency(cn.total) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ cn.currency ?? 'USD' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(cn.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="accounting.creditNotesMeta" @change="accounting.fetchCreditNotes" />
        </div>
      </div>

      <!-- Bank Accounts -->
      <div v-if="activeTab === 'bank'">        <div class="space-y-6">
          <!-- Bank Accounts list -->
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
              <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bank Accounts</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Bank accounts table">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bank</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Account #</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Currency</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <tr v-if="accounting.bankAccountsLoading">
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                  </tr>
                  <tr v-else-if="accounting.bankAccounts.length === 0">
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No bank accounts found.</td>
                  </tr>
                  <tr
                    v-for="ba in accounting.bankAccounts"
                    v-else
                    :key="ba.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                  >
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ ba.name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ba.bank_name }}</td>
                    <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ ba.account_number }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ba.currency }}</td>
                    <td class="px-4 py-3">
                      <StatusBadge :status="ba.is_active ? 'active' : 'inactive'" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <Pagination :meta="accounting.bankAccountsMeta" @change="accounting.fetchBankAccounts" />
          </div>

          <!-- Bank Transactions list -->
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
              <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bank Transactions</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Bank transactions table">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ref #</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <tr v-if="accounting.bankTransactionsLoading">
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                  </tr>
                  <tr v-else-if="accounting.bankTransactions.length === 0">
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No transactions found.</td>
                  </tr>
                  <tr
                    v-for="tx in accounting.bankTransactions"
                    v-else
                    :key="tx.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                  >
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(tx.transaction_date) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white max-w-xs truncate">{{ tx.description }}</td>
                    <td class="px-4 py-3"><StatusBadge :status="tx.type" /></td>
                    <td
                      class="px-4 py-3 text-sm text-right font-medium"
                      :class="tx.type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                    >
                      {{ tx.type === 'debit' ? '-' : '' }}{{ formatCurrency(tx.amount) }}
                    </td>
                    <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ tx.reference_number ?? '—' }}</td>
                    <td class="px-4 py-3"><StatusBadge :status="tx.status" /></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <Pagination :meta="accounting.bankTransactionsMeta" @change="accounting.fetchBankTransactions" />
          </div>
        </div>
      </div>
      <!-- Fiscal Periods -->
      <div v-if="activeTab === 'periods'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Fiscal periods table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Start Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">End Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Closed At</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="accounting.periodsLoading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="accounting.periods.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No fiscal periods found.</td>
                </tr>
                <tr
                  v-for="period in accounting.periods"
                  v-else
                  :key="period.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ period.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(period.start_date) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(period.end_date) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="period.status" /></td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ period.closed_at ? formatDate(period.closed_at) : '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="accounting.periodsMeta" @change="accounting.fetchPeriods" />
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
import { useAccountingStore } from '@/stores/accounting';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatCurrency } = useFormatters();

const accounting = useAccountingStore();
const activeTab = ref('accounts');

const tabs = [
    { key: 'accounts', label: 'Chart of Accounts' },
    { key: 'invoices', label: 'Invoices' },
    { key: 'credit_notes', label: 'Credit Notes' },
    { key: 'bank', label: 'Bank & Reconciliation' },
    { key: 'periods', label: 'Fiscal Periods' },
];

function switchTab(key) {
    activeTab.value = key;
    if (key === 'accounts') accounting.fetchAccounts();
    else if (key === 'invoices') accounting.fetchInvoices();
    else if (key === 'credit_notes') accounting.fetchCreditNotes();
    else if (key === 'bank') {
        accounting.fetchBankAccounts();
        accounting.fetchBankTransactions();
    } else if (key === 'periods' && accounting.periods.length === 0) {
        accounting.fetchPeriods();
    }
}

onMounted(() => accounting.fetchAccounts());
</script>
