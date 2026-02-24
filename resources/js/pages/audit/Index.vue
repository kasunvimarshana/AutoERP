<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Log</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Immutable record of all system actions across the platform.</p>
      </div>

      <div
        v-if="audit.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ audit.error }}
      </div>

      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Audit log table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Timestamp</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Action</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Entity</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actor</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">IP Address</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="audit.loading">
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="audit.logs.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No audit log entries found.</td>
              </tr>
              <tr
                v-for="log in audit.logs"
                v-else
                :key="log.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">{{ formatDateTime(log.created_at) }}</td>
                <td class="px-4 py-3">
                  <StatusBadge :status="log.action" />
                </td>
                <td class="px-4 py-3">
                  <p class="text-xs font-mono text-indigo-600 dark:text-indigo-400 truncate max-w-[180px]">{{ shortModelType(log.model_type) }}</p>
                  <p v-if="log.model_id" class="text-xs text-gray-400 dark:text-gray-500 font-mono truncate max-w-[180px]">{{ log.model_id }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ log.user_id ?? '—' }}</td>
                <td class="px-4 py-3 text-xs font-mono text-gray-400 dark:text-gray-500">{{ log.ip_address ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="audit.meta" @change="audit.fetchLogs" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { useAuditStore } from '@/stores/audit';
import { useFormatters } from '@/composables/useFormatters';
const { formatDateTime } = useFormatters();

const audit = useAuditStore();

function shortModelType(modelType) {
    if (!modelType) return '—';
    return modelType.split('\\').pop();
}

onMounted(() => audit.fetchLogs());
</script>
