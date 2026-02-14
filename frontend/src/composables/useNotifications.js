import { ref } from 'vue'

export function useNotifications() {
  const notifications = ref([])

  const addNotification = (message, type = 'info') => {
    const id = Date.now()
    notifications.value.push({ id, message, type })
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

  const success = (message) => addNotification(message, 'success')
  const error = (message) => addNotification(message, 'error')
  const warning = (message) => addNotification(message, 'warning')
  const info = (message) => addNotification(message, 'info')

  return {
    notifications,
    addNotification,
    removeNotification,
    success,
    error,
    warning,
    info
  }
}
