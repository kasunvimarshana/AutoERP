import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * Notification Types
 */
export type NotificationType = 'success' | 'error' | 'warning' | 'info'

export interface Notification {
  id: string
  type: NotificationType
  message: string
  title?: string
  duration?: number
  closable?: boolean
}

/**
 * UI Store
 * Manages UI state including notifications, loading states, modals, etc.
 */

export const useUiStore = defineStore('ui', () => {
  // State
  const notifications = ref<Notification[]>([])
  const sidebarCollapsed = ref(false)
  const theme = ref<'light' | 'dark'>('light')
  const loading = ref(false)
  const loadingMessage = ref<string>('')

  // Actions
  function showNotification(
    message: string,
    type: NotificationType = 'info',
    options?: Partial<Notification>,
  ) {
    const id = `notification-${Date.now()}-${Math.random()}`
    const notification: Notification = {
      id,
      type,
      message,
      title: options?.title,
      duration: options?.duration ?? 5000,
      closable: options?.closable ?? true,
    }

    notifications.value.push(notification)

    // Auto-remove after duration
    if (notification.duration && notification.duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, notification.duration)
    }

    return id
  }

  function removeNotification(id: string) {
    const index = notifications.value.findIndex((n) => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }

  function clearNotifications() {
    notifications.value = []
  }

  function success(message: string, title?: string) {
    return showNotification(message, 'success', { title })
  }

  function error(message: string, title?: string) {
    return showNotification(message, 'error', { title, duration: 8000 })
  }

  function warning(message: string, title?: string) {
    return showNotification(message, 'warning', { title })
  }

  function info(message: string, title?: string) {
    return showNotification(message, 'info', { title })
  }

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  function setSidebarCollapsed(collapsed: boolean) {
    sidebarCollapsed.value = collapsed
  }

  function setTheme(newTheme: 'light' | 'dark') {
    theme.value = newTheme
    // Update document class for theme switching
    document.documentElement.classList.toggle('dark', newTheme === 'dark')
    localStorage.setItem('theme', newTheme)
  }

  function toggleTheme() {
    setTheme(theme.value === 'light' ? 'dark' : 'light')
  }

  function initializeTheme() {
    const stored = localStorage.getItem('theme') as 'light' | 'dark' | null
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    
    setTheme(stored || (prefersDark ? 'dark' : 'light'))
  }

  function startLoading(message = 'Loading...') {
    loading.value = true
    loadingMessage.value = message
  }

  function stopLoading() {
    loading.value = false
    loadingMessage.value = ''
  }

  return {
    // State
    notifications,
    sidebarCollapsed,
    theme,
    loading,
    loadingMessage,

    // Actions
    showNotification,
    removeNotification,
    clearNotifications,
    success,
    error,
    warning,
    info,
    toggleSidebar,
    setSidebarCollapsed,
    setTheme,
    toggleTheme,
    initializeTheme,
    startLoading,
    stopLoading,
  }
})
