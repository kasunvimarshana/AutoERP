import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationApi, type Notification } from '@/api/notifications'

export const useNotificationStore = defineStore('notifications', () => {
  // State
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const unreadNotifications = computed(() => 
    notifications.value.filter(n => !n.read_at)
  )

  const readNotifications = computed(() => 
    notifications.value.filter(n => n.read_at)
  )

  const hasUnread = computed(() => unreadCount.value > 0)

  // Actions
  async function fetchUnreadNotifications(limit = 10) {
    try {
      isLoading.value = true
      error.value = null
      const response = await notificationApi.getUnreadNotifications(limit)
      notifications.value = response.data || []
      unreadCount.value = response.count || 0
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch notifications'
      console.error('Failed to fetch notifications:', err)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchNotifications(page = 1, perPage = 20) {
    try {
      isLoading.value = true
      error.value = null
      const response = await notificationApi.getNotifications(page, perPage)
      notifications.value = response.data || []
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch notifications'
      console.error('Failed to fetch notifications:', err)
    } finally {
      isLoading.value = false
    }
  }

  async function updateUnreadCount() {
    try {
      unreadCount.value = await notificationApi.getUnreadCount()
    } catch (err: any) {
      console.error('Failed to update unread count:', err)
    }
  }

  async function markAsRead(notificationId: string) {
    try {
      await notificationApi.markAsRead(notificationId)
      
      // Update local state
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification && !notification.read_at) {
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to mark notification as read'
      console.error('Failed to mark notification as read:', err)
    }
  }

  async function markMultipleAsRead(notificationIds: string[]) {
    try {
      const count = await notificationApi.markMultipleAsRead(notificationIds)
      
      // Update local state
      notificationIds.forEach(id => {
        const notification = notifications.value.find(n => n.id === id)
        if (notification && !notification.read_at) {
          notification.read_at = new Date().toISOString()
        }
      })
      
      unreadCount.value = Math.max(0, unreadCount.value - count)
    } catch (err: any) {
      error.value = err.message || 'Failed to mark notifications as read'
      console.error('Failed to mark notifications as read:', err)
    }
  }

  async function markAllAsRead() {
    try {
      const count = await notificationApi.markAllAsRead()
      
      // Update local state
      notifications.value.forEach(n => {
        if (!n.read_at) {
          n.read_at = new Date().toISOString()
        }
      })
      
      unreadCount.value = 0
    } catch (err: any) {
      error.value = err.message || 'Failed to mark all notifications as read'
      console.error('Failed to mark all notifications as read:', err)
    }
  }

  async function deleteNotification(notificationId: string) {
    try {
      await notificationApi.deleteNotification(notificationId)
      
      // Update local state
      const index = notifications.value.findIndex(n => n.id === notificationId)
      if (index !== -1) {
        const wasUnread = !notifications.value[index].read_at
        notifications.value.splice(index, 1)
        if (wasUnread) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to delete notification'
      console.error('Failed to delete notification:', err)
    }
  }

  function addNotification(notification: Notification) {
    // Add to beginning of array
    notifications.value.unshift(notification)
    
    // If unread, increment count
    if (!notification.read_at) {
      unreadCount.value++
    }
    
    // Limit array size to prevent memory issues
    if (notifications.value.length > 100) {
      notifications.value = notifications.value.slice(0, 100)
    }
  }

  function clearNotifications() {
    notifications.value = []
    unreadCount.value = 0
  }

  return {
    // State
    notifications,
    unreadCount,
    isLoading,
    error,
    
    // Getters
    unreadNotifications,
    readNotifications,
    hasUnread,
    
    // Actions
    fetchUnreadNotifications,
    fetchNotifications,
    updateUnreadCount,
    markAsRead,
    markMultipleAsRead,
    markAllAsRead,
    deleteNotification,
    addNotification,
    clearNotifications,
  }
})
