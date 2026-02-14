import { ref } from 'vue'
import { useUiStore } from '@stores/ui'

export function useApi(apiFunction) {
  const loading = ref(false)
  const error = ref(null)
  const data = ref(null)
  const uiStore = useUiStore()

  const execute = async (...args) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await apiFunction(...args)
      data.value = response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'An error occurred'
      uiStore.addNotification({
        type: 'error',
        title: 'Error',
        message: error.value
      })
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    data,
    execute
  }
}
