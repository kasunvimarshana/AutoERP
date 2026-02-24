<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page heading -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">CRM</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage leads, opportunities, and contacts.</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="CRM tabs">
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
        v-if="crm.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ crm.error }}
      </div>

      <!-- Leads tab -->
      <div v-if="activeTab === 'leads'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Leads table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Company</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Source</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="crm.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="crm.leads.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No leads found.</td>
                </tr>
                <tr
                  v-for="lead in crm.leads"
                  v-else
                  :key="lead.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ lead.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ lead.email ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ lead.company ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <StatusBadge :status="lead.status" />
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ lead.source ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="crm.leadsMeta" @change="crm.fetchLeads" />
        </div>
      </div>

      <!-- Opportunities tab -->
      <div v-if="activeTab === 'opportunities'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Opportunities table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Account</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stage</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Value</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Close Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="crm.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="crm.opportunities.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No opportunities found.</td>
                </tr>
                <tr
                  v-for="opp in crm.opportunities"
                  v-else
                  :key="opp.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ opp.title }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ opp.account_name ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <StatusBadge :status="opp.stage" />
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(opp.value) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ opp.close_date ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="crm.opportunitiesMeta" @change="crm.fetchOpportunities" />
        </div>
      </div>

      <!-- Contacts tab -->
      <div v-if="activeTab === 'contacts'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Contacts table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Phone</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Company</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="crm.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="crm.contacts.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No contacts found.</td>
                </tr>
                <tr
                  v-for="contact in crm.contacts"
                  v-else
                  :key="contact.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ contact.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ contact.email ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ contact.phone ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ contact.company ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
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
import { useCrmStore } from '@/stores/crm';
import { useFormatters } from '@/composables/useFormatters';
const { formatCurrency } = useFormatters();

const crm = useCrmStore();
const activeTab = ref('leads');

const tabs = computed(() => [
    { key: 'leads', label: 'Leads', count: crm.leadsMeta.total || null },
    { key: 'opportunities', label: 'Opportunities', count: crm.opportunitiesMeta.total || null },
    { key: 'contacts', label: 'Contacts', count: null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'leads') crm.fetchLeads();
    else if (key === 'opportunities') crm.fetchOpportunities();
    else if (key === 'contacts') crm.fetchContacts();
}

onMounted(() => crm.fetchLeads());
</script>
