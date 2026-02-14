import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const sidebarCollapsed = ref(false)
  const theme = ref(localStorage.getItem('theme') || 'light')
  const loading = ref(false)
  const notifications = ref([])

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  function setSidebarCollapsed(value) {
    sidebarCollapsed.value = value
  }

  function setTheme(newTheme) {
    theme.value = newTheme
    localStorage.setItem('theme', newTheme)
    
    if (newTheme === 'dark') {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  function setLoading(value) {
    loading.value = value
  }

  function addNotification(notification) {
    const id = Date.now()
    notifications.value.push({
      id,
      type: 'info',
      duration: 3000,
      ...notification
    })
    
    if (notification.duration !== 0) {
      setTimeout(() => {
        removeNotification(id)
      }, notification.duration || 3000)
    }
  }

  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  return {
    sidebarCollapsed,
    theme,
    loading,
    notifications,
    toggleSidebar,
    setSidebarCollapsed,
    setTheme,
    setLoading,
    addNotification,
    removeNotification
  }
})
