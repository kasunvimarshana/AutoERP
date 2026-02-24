<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Integrations</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage outbound webhooks and API key access.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Integration tabs">
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
        v-if="integration.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ integration.error }}
      </div>

      <!-- Webhooks tab -->
      <div v-if="activeTab === 'webhooks'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Webhooks table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">URL</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Events</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="integration.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="integration.webhooks.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No webhooks found.</td>
                </tr>
                <tr
                  v-for="hook in integration.webhooks"
                  v-else
                  :key="hook.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ hook.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs font-mono text-xs">{{ hook.url }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ Array.isArray(hook.events) ? hook.events.join(', ') : (hook.events ?? '—') }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="hook.is_active ? 'active' : 'inactive'" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="integration.webhooksMeta" @change="integration.fetchWebhooks" />
        </div>
      </div>

      <!-- API Keys tab -->
      <div v-if="activeTab === 'api-keys'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="API Keys table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prefix</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Scopes</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Expires</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="integration.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="integration.apiKeys.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No API keys found.</td>
                </tr>
                <tr
                  v-for="key in integration.apiKeys"
                  v-else
                  :key="key.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ key.name }}</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ key.key_prefix ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ Array.isArray(key.scopes) ? key.scopes.join(', ') : (key.scopes ?? '—') }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(key.expires_at) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="key.revoked_at ? 'revoked' : 'active'" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="integration.apiKeysMeta" @change="integration.fetchApiKeys" />
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
import { useIntegrationStore } from '@/stores/integration';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const integration = useIntegrationStore();
const activeTab = ref('webhooks');

const tabs = computed(() => [
    { key: 'webhooks', label: 'Webhooks', count: integration.webhooksMeta.total || null },
    { key: 'api-keys', label: 'API Keys', count: integration.apiKeysMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'webhooks') integration.fetchWebhooks();
    else if (key === 'api-keys') integration.fetchApiKeys();
}

onMounted(() => integration.fetchWebhooks());
</script>
