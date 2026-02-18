<template>
  <div class="relative">
    <!-- Notification Bell Button -->
    <button
      class="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      :class="{ 'animate-pulse': hasNewNotifications }"
      @click="toggleDropdown"
    >
      <svg
        class="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>
      <!-- Unread Count Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 z-50 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Notifications
          </h3>
          <div class="flex items-center space-x-2">
            <button
              v-if="unreadCount > 0"
              class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
              @click="handleMarkAllAsRead"
            >
              Mark all as read
            </button>
            <button
              class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
              @click="goToNotificationsPage"
            >
              View all
            </button>
          </div>
        </div>

        <!-- Notification List -->
        <div class="max-h-96 overflow-y-auto">
          <!-- Loading State -->
          <div
            v-if="isLoading"
            class="flex items-center justify-center py-8"
          >
            <svg
              class="animate-spin h-8 w-8 text-blue-600"
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
            v-else-if="notifications.length === 0"
            class="flex flex-col items-center justify-center py-8 px-4 text-center"
          >
            <svg
              class="w-16 h-16 text-gray-400 mb-4"
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
            <p class="text-gray-500 dark:text-gray-400">
              No notifications
            </p>
          </div>

          <!-- Notifications -->
          <div v-else>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
              :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read_at }"
              @click="handleNotificationClick(notification)"
            >
              <div class="flex items-start space-x-3">
                <!-- Icon -->
                <div
                  class="flex-shrink-0 mt-1"
                  :class="{
                    'text-yellow-500': notification.data.severity === 'critical',
                    'text-orange-500': notification.data.severity === 'high',
                    'text-blue-500': notification.data.severity === 'medium' || !notification.data.severity,
                    'text-gray-500': notification.data.severity === 'low'
                  }"
                >
                  <svg
                    class="w-5 h-5"
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
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ notification.data.title }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ notification.data.message }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    {{ formatTime(notification.created_at) }}
                  </p>
                </div>

                <!-- Actions -->
                <div class="flex-shrink-0 flex items-center space-x-2">
                  <button
                    v-if="!notification.read_at"
                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    title="Mark as read"
                    @click.stop="handleMarkAsRead(notification.id)"
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
                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                    title="Delete"
                    @click.stop="handleDelete(notification.id)"
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
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 rounded-b-lg">
          <button
            class="w-full text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
            @click="goToNotificationsPage"
          >
            View all notifications
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notifications'
import { formatDistanceToNow } from 'date-fns'

const router = useRouter()
const notificationStore = useNotificationStore()

const isOpen = ref(false)
const hasNewNotifications = ref(false)

const notifications = computed(() => notificationStore.unreadNotifications.slice(0, 5))
const unreadCount = computed(() => notificationStore.unreadCount)
const isLoading = computed(() => notificationStore.isLoading)

function toggleDropdown() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    hasNewNotifications.value = false
  }
}

function formatTime(timestamp: string): string {
  try {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
  } catch {
    return timestamp
  }
}

async function handleMarkAsRead(notificationId: string) {
  await notificationStore.markAsRead(notificationId)
}

async function handleMarkAllAsRead() {
  await notificationStore.markAllAsRead()
}

async function handleDelete(notificationId: string) {
  await notificationStore.deleteNotification(notificationId)
}

function handleNotificationClick(notification: any) {
  // Mark as read
  if (!notification.read_at) {
    handleMarkAsRead(notification.id)
  }

  // Navigate to action URL if available
  if (notification.data.action_url) {
    router.push(notification.data.action_url)
    isOpen.value = false
  }
}

function goToNotificationsPage() {
  router.push('/notifications')
  isOpen.value = false
}

// Close dropdown when clicking outside
function handleClickOutside(event: MouseEvent) {
  const target = event.target as HTMLElement
  if (isOpen.value && !target.closest('.relative')) {
    isOpen.value = false
  }
}

// Load notifications on mount
onMounted(() => {
  notificationStore.fetchUnreadNotifications()
  
  // Poll for new notifications every 30 seconds
  const pollInterval = setInterval(() => {
    notificationStore.updateUnreadCount()
  }, 30000)

  // Add click outside listener
  document.addEventListener('click', handleClickOutside)

  onUnmounted(() => {
    clearInterval(pollInterval)
    document.removeEventListener('click', handleClickOutside)
  })
})
</script>
