<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Your in-app notification inbox.
          </p>
        </div>
        <button
          v-if="notification.notifications.length"
          type="button"
          class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
          @click="notification.markAllRead()"
        >
          Mark all as read
        </button>
      </div>

      <div
        v-if="notification.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ notification.error }}
      </div>

      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div v-if="notification.loading" class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
          Loading…
        </div>
        <div
          v-else-if="notification.notifications.length === 0"
          class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500"
        >
          No notifications.
        </div>
        <ul v-else role="list" class="divide-y divide-gray-100 dark:divide-gray-800">
          <li
            v-for="n in notification.notifications"
            :key="n.id"
            :class="[
              'flex items-start gap-4 px-5 py-4',
              n.read_at ? 'bg-white dark:bg-gray-900' : 'bg-indigo-50 dark:bg-indigo-900/10',
            ]"
          >
            <!-- Unread dot -->
            <span
              :class="[
                'mt-1.5 w-2 h-2 rounded-full shrink-0',
                n.read_at ? 'bg-gray-200 dark:bg-gray-700' : 'bg-indigo-500',
              ]"
              :aria-label="n.read_at ? 'Read' : 'Unread'"
            />
            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                {{ formatType(n.type) }}
              </p>
              <p
                v-if="n.data?.message"
                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400 line-clamp-2"
              >
                {{ n.data.message }}
              </p>
              <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                {{ formatDate(n.created_at) }}
              </p>
            </div>
            <!-- Actions -->
            <div class="flex items-center gap-2 shrink-0">
              <button
                v-if="!n.read_at"
                type="button"
                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                @click="notification.markRead(n.id)"
              >
                Mark read
              </button>
              <button
                type="button"
                class="text-xs text-red-500 hover:underline"
                aria-label="Delete notification"
                @click="notification.remove(n.id)"
              >
                Delete
              </button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Pagination -->
      <Pagination
        :meta="notification.meta"
        @page-change="notification.fetchNotifications($event)"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { useNotificationStore } from '@/stores/notification';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const notification = useNotificationStore();

onMounted(() => {
    notification.fetchNotifications();
});

function formatType(type) {
    return type?.replace(/[._]/g, ' ') ?? '—';
}
</script>
