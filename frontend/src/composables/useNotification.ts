import { useUiStore } from '@/stores/ui'

/**
 * useNotification Composable
 * Provides easy access to notification methods
 */

export function useNotification() {
  const uiStore = useUiStore()

  return {
    success: uiStore.success,
    error: uiStore.error,
    warning: uiStore.warning,
    info: uiStore.info,
    show: uiStore.showNotification,
    remove: uiStore.removeNotification,
    clear: uiStore.clearNotifications,
  }
}
