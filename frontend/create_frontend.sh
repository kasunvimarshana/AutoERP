#!/bin/bash

# Notification Store
cat > src/stores/notificationStore.js << 'EOF'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  const showPanel = ref(false)
  
  const unreadCount = computed(() => 
    notifications.value.filter(n => !n.read).length
  )
  
  const addNotification = (notification) => {
    notifications.value.unshift({
      id: Date.now(),
      read: false,
      timestamp: new Date(),
      ...notification
    })
  }
  
  const markAsRead = (id) => {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
      notification.read = true
    }
  }
  
  const markAllAsRead = () => {
    notifications.value.forEach(n => n.read = true)
  }
  
  const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }
  
  const togglePanel = () => {
    showPanel.value = !showPanel.value
  }
  
  const success = (message) => {
    addNotification({ type: 'success', message })
  }
  
  const error = (message) => {
    addNotification({ type: 'error', message })
  }
  
  const warning = (message) => {
    addNotification({ type: 'warning', message })
  }
  
  const info = (message) => {
    addNotification({ type: 'info', message })
  }
  
  return {
    notifications,
    showPanel,
    unreadCount,
    addNotification,
    markAsRead,
    markAllAsRead,
    removeNotification,
    togglePanel,
    success,
    error,
    warning,
    info
  }
})
EOF

# UI Store
cat > src/stores/uiStore.js << 'EOF'
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUIStore = defineStore('ui', () => {
  const loading = ref(false)
  const sidebarOpen = ref(true)
  const modal = ref(null)
  
  const setLoading = (value) => {
    loading.value = value
  }
  
  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }
  
  const openModal = (component, props = {}) => {
    modal.value = { component, props }
  }
  
  const closeModal = () => {
    modal.value = null
  }
  
  return {
    loading,
    sidebarOpen,
    modal,
    setLoading,
    toggleSidebar,
    openModal,
    closeModal
  }
})
EOF

echo "Stores created successfully"
