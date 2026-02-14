import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAppStore = defineStore('app', () => {
  const sidebarOpen = ref(true)
  const loading = ref(false)
  const notifications = ref([])

  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }

  const setLoading = (value) => {
    loading.value = value
  }

  const addNotification = (notification) => {
    const id = Date.now()
    notifications.value.push({ id, ...notification })
    setTimeout(() => {
      removeNotification(id)
    }, 5000)
  }

  const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  return {
    sidebarOpen,
    loading,
    notifications,
    toggleSidebar,
    setLoading,
    addNotification,
    removeNotification
  }
})
