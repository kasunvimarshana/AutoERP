<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Communication</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage internal channels and team messaging.</p>
        </div>
      </div>

      <div
        v-if="communication.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ communication.error }}
      </div>

      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Communication Channels table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="communication.loading">
                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="communication.channels.length === 0">
                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No channels found.</td>
              </tr>
              <tr
                v-for="channel in communication.channels"
                v-else
                :key="channel.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ channel.name }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ channel.type ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ channel.description ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(channel.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="communication.meta" @change="communication.fetchChannels" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { useCommunicationStore } from '@/stores/communication';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const communication = useCommunicationStore();

onMounted(() => communication.fetchChannels());
</script>
