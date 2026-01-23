import { ref, type Ref } from 'vue'
import { useNotification } from './useNotification'

/**
 * useApi Composable
 * Provides a reusable pattern for API calls with loading and error states
 */

interface UseApiOptions<T> {
  immediate?: boolean
  onSuccess?: (data: T) => void
  onError?: (error: any) => void
  showSuccessMessage?: boolean
  showErrorMessage?: boolean
  successMessage?: string
}

export function useApi<T = any>(
  apiCall: (...args: any[]) => Promise<T>,
  options: UseApiOptions<T> = {},
) {
  const data = ref<T | null>(null) as Ref<T | null>
  const error = ref<any>(null)
  const isLoading = ref(false)
  const notification = useNotification()

  async function execute(...args: any[]): Promise<T | null> {
    isLoading.value = true
    error.value = null

    try {
      const result = await apiCall(...args)
      data.value = result

      if (options.showSuccessMessage && options.successMessage) {
        notification.success(options.successMessage)
      }

      if (options.onSuccess) {
        options.onSuccess(result)
      }

      return result
    } catch (err: any) {
      error.value = err
      
      if (options.showErrorMessage !== false) {
        const errorMessage = err.message || 'An error occurred'
        notification.error(errorMessage)
      }

      if (options.onError) {
        options.onError(err)
      }

      return null
    } finally {
      isLoading.value = false
    }
  }

  // Execute immediately if requested
  if (options.immediate) {
    execute()
  }

  return {
    data,
    error,
    isLoading,
    execute,
  }
}
