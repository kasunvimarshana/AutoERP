<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Helpdesk</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Support tickets, SLA tracking, and knowledge base.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Helpdesk tabs">
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
        v-if="helpdesk.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ helpdesk.error }}
      </div>

      <!-- Tickets tab -->
      <div v-if="activeTab === 'tickets'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Support tickets table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ticket</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reporter</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Priority</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">SLA Due</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="helpdesk.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="helpdesk.tickets.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No tickets found.</td>
                </tr>
                <tr
                  v-for="ticket in helpdesk.tickets"
                  v-else
                  :key="ticket.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">#{{ ticket.ticket_number ?? ticket.id }} — {{ ticket.subject ?? ticket.title }}</p>
                    <p v-if="ticket.category_name" class="text-xs text-gray-500 dark:text-gray-400">{{ ticket.category_name }}</p>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ticket.reporter_name ?? ticket.reported_by ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="ticket.priority" /></td>
                  <td class="px-4 py-3"><StatusBadge :status="ticket.status" /></td>
                  <td class="px-4 py-3 text-sm" :class="slaClass(ticket)">{{ formatDateTime(ticket.sla_due_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="helpdesk.meta" @change="helpdesk.fetchTickets" />
        </div>
      </div>

      <!-- KB Articles tab -->
      <div v-if="activeTab === 'kb_articles'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Knowledge base articles table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Visibility</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Helpful</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Published</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="helpdesk.kbArticlesLoading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="helpdesk.kbArticles.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No knowledge base articles found.</td>
                </tr>
                <tr
                  v-for="article in helpdesk.kbArticles"
                  v-else
                  :key="article.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ article.title }}</p>
                    <p v-if="article.tags?.length" class="text-xs text-gray-500 dark:text-gray-400">{{ article.tags.join(', ') }}</p>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ (article.visibility ?? 'public').replace('_', ' ') }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="article.status" /></td>
                  <td class="px-4 py-3 text-sm text-right">
                    <span class="text-emerald-600 dark:text-emerald-400">{{ article.helpful_count ?? 0 }}</span>
                    <span class="text-gray-400 dark:text-gray-500"> / </span>
                    <span class="text-red-500 dark:text-red-400">{{ article.not_helpful_count ?? 0 }}</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(article.published_at ?? article.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="helpdesk.kbArticlesMeta" @change="helpdesk.fetchKbArticles" />
        </div>
      </div>

      <!-- KB Categories tab -->
      <div v-if="activeTab === 'kb_categories'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="KB categories table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Category</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="helpdesk.kbCategoriesLoading">
                  <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="helpdesk.kbCategories.length === 0">
                  <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No KB categories found.</td>
                </tr>
                <tr
                  v-for="cat in helpdesk.kbCategories"
                  v-else
                  :key="cat.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ cat.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ cat.description ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(cat.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="helpdesk.kbCategoriesMeta" @change="helpdesk.fetchKbCategories" />
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
import { useHelpdeskStore } from '@/stores/helpdesk';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatDateTime } = useFormatters();

const helpdesk = useHelpdeskStore();
const activeTab = ref('tickets');

const tabs = computed(() => [
    { key: 'tickets', label: 'Tickets', count: helpdesk.meta.total || null },
    { key: 'kb_articles', label: 'KB Articles', count: helpdesk.kbArticlesMeta.total || null },
    { key: 'kb_categories', label: 'KB Categories', count: helpdesk.kbCategoriesMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'tickets') helpdesk.fetchTickets();
    else if (key === 'kb_articles') helpdesk.fetchKbArticles();
    else if (key === 'kb_categories') helpdesk.fetchKbCategories();
}

function slaClass(ticket) {
    if (!ticket.sla_due_at) return 'text-gray-500 dark:text-gray-400';
    const due = new Date(ticket.sla_due_at);
    const now = new Date();
    if (ticket.status === 'closed' || ticket.status === 'resolved') return 'text-gray-400 dark:text-gray-500';
    if (due < now) return 'text-red-600 dark:text-red-400 font-medium';
    const hourMs = 3600000;
    if (due - now < 4 * hourMs) return 'text-amber-600 dark:text-amber-400';
    return 'text-gray-500 dark:text-gray-400';
}

onMounted(() => helpdesk.fetchTickets());
</script>
