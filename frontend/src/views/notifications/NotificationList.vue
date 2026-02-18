<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
        Notifications
      </h1>
      <p class="text-gray-600 dark:text-gray-400">
        View and manage all your notifications
      </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
              Total
            </p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
              {{ statistics?.total || 0 }}
            </p>
          </div>
          <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3">
            <svg
              class="w-8 h-8 text-blue-600 dark:text-blue-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
              />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
              Unread
            </p>
            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2">
              {{ statistics?.unread || 0 }}
            </p>
          </div>
          <div class="bg-orange-100 dark:bg-orange-900 rounded-full p-3">
            <svg
              class="w-8 h-8 text-orange-600 dark:text-orange-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
              />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
              Read
            </p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
              {{ statistics?.read || 0 }}
            </p>
          </div>
          <div class="bg-green-100 dark:bg-green-900 rounded-full p-3">
            <svg
              class="w-8 h-8 text-green-600 dark:text-green-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
      <div class="p-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <!-- Filter Tabs -->
          <div class="flex rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
            <button
              class="px-4 py-2 text-sm font-medium transition-colors"
              :class="filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
              @click="filter = 'all'"
            >
              All
            </button>
            <button
              class="px-4 py-2 text-sm font-medium transition-colors border-l border-gray-300 dark:border-gray-600"
              :class="filter === 'unread' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
              @click="filter = 'unread'"
            >
              Unread
            </button>
            <button
              class="px-4 py-2 text-sm font-medium transition-colors border-l border-gray-300 dark:border-gray-600"
              :class="filter === 'read' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
              @click="filter = 'read'"
            >
              Read
            </button>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <button
            v-if="filter !== 'read' && filteredNotifications.length > 0"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
            @click="handleMarkAllAsRead"
          >
            Mark all as read
          </button>
          <button
            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-sm font-medium"
            @click="refreshNotifications"
          >
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <!-- Loading State -->
      <div
        v-if="isLoading"
        class="flex items-center justify-center py-12"
      >
        <svg
          class="animate-spin h-10 w-10 text-blue-600"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          />
        </svg>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredNotifications.length === 0"
        class="flex flex-col items-center justify-center py-12"
      >
        <svg
          class="w-24 h-24 text-gray-400 mb-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
          />
        </svg>
        <p class="text-gray-500 dark:text-gray-400 text-lg">
          No notifications found
        </p>
      </div>

      <!-- Notifications -->
      <div
        v-else
        class="divide-y divide-gray-200 dark:divide-gray-700"
      >
        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read_at }"
        >
          <div class="flex items-start space-x-4">
            <!-- Icon -->
            <div
              class="flex-shrink-0 mt-1"
              :class="{
                'text-red-500': notification.data.severity === 'critical',
                'text-orange-500': notification.data.severity === 'high',
                'text-yellow-500': notification.data.severity === 'medium',
                'text-blue-500': !notification.data.severity,
                'text-gray-500': notification.data.severity === 'low'
              }"
            >
              <svg
                class="w-8 h-8"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <circle
                  cx="10"
                  cy="10"
                  r="8"
                />
              </svg>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ notification.data.title }}
                  </h3>
                  <p class="text-gray-700 dark:text-gray-300 mt-1">
                    {{ notification.data.message }}
                  </p>
                  <div class="flex items-center space-x-4 mt-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ formatTime(notification.created_at) }}
                    </span>
                    <span
                      v-if="notification.data.severity"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': notification.data.severity === 'critical',
                        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': notification.data.severity === 'high',
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': notification.data.severity === 'medium',
                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': notification.data.severity === 'low'
                      }"
                    >
                      {{ notification.data.severity }}
                    </span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2 ml-4">
                  <button
                    v-if="notification.data.action_url"
                    class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors text-sm font-medium"
                    @click="handleViewAction(notification)"
                  >
                    View
                  </button>
                  <button
                    v-if="!notification.read_at"
                    class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                    title="Mark as read"
                    @click="handleMarkAsRead(notification.id)"
                  >
                    <svg
                      class="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                  </button>
                  <button
                    class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    title="Delete"
                    @click="handleDelete(notification.id)"
                  >
                    <svg
                      class="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notifications'
import { notificationApi } from '@/api/notifications'
import type { NotificationStatistics } from '@/api/notifications'
import { formatDistanceToNow } from 'date-fns'

const router = useRouter()
const notificationStore = useNotificationStore()

const filter = ref<'all' | 'unread' | 'read'>('all')
const statistics = ref<NotificationStatistics | null>(null)

const isLoading = computed(() => notificationStore.isLoading)
const allNotifications = computed(() => notificationStore.notifications)

const filteredNotifications = computed(() => {
  if (filter.value === 'unread') {
    return allNotifications.value.filter(n => !n.read_at)
  } else if (filter.value === 'read') {
    return allNotifications.value.filter(n => n.read_at)
  }
  return allNotifications.value
})

function formatTime(timestamp: string): string {
  try {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
  } catch {
    return timestamp
  }
}

async function refreshNotifications() {
  await notificationStore.fetchNotifications()
  await loadStatistics()
}

async function loadStatistics() {
  try {
    statistics.value = await notificationApi.getStatistics()
  } catch (error) {
    console.error('Failed to load statistics:', error)
  }
}

async function handleMarkAsRead(notificationId: string) {
  await notificationStore.markAsRead(notificationId)
  await loadStatistics()
}

async function handleMarkAllAsRead() {
  await notificationStore.markAllAsRead()
  await loadStatistics()
}

async function handleDelete(notificationId: string) {
  if (confirm('Are you sure you want to delete this notification?')) {
    await notificationStore.deleteNotification(notificationId)
    await loadStatistics()
  }
}

function handleViewAction(notification: any) {
  // Mark as read
  if (!notification.read_at) {
    handleMarkAsRead(notification.id)
  }

  // Navigate to action URL
  if (notification.data.action_url) {
    router.push(notification.data.action_url)
  }
}

onMounted(async () => {
  await refreshNotifications()
})
</script>
