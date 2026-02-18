import { ref } from 'vue'
import { useNotificationStore } from '../stores/notificationStore'

const globalError = ref(null)
const globalLoading = ref(false)

export function useErrorHandler() {
  const notificationStore = useNotificationStore()

  /**
   * Handle API errors consistently
   * @param {Error} error - The error object
   * @param {Object} options - Configuration options
   * @param {boolean} options.showNotification - Whether to show toast notification (default: true)
   * @param {string} options.defaultMessage - Default error message if none provided
   * @param {Function} options.onError - Custom error handler callback
   */
  const handleError = (error, options = {}) => {
    const {
      showNotification = true,
      defaultMessage = 'An error occurred. Please try again.',
      onError = null
    } = options

    // Extract error message
    let errorMessage = defaultMessage
    let errorDetails = null

    if (error.response) {
      // Server responded with error status
      const { data, status } = error.response
      
      if (data?.message) {
        errorMessage = data.message
      } else if (data?.error) {
        errorMessage = data.error
      }
      
      // Handle validation errors
      if (data?.errors && typeof data.errors === 'object') {
        errorDetails = data.errors
        const firstError = Object.values(data.errors)[0]
        if (Array.isArray(firstError)) {
          errorMessage = firstError[0]
        }
      }
      
      // Handle specific status codes
      switch (status) {
        case 401:
          errorMessage = 'Authentication required. Please log in.'
          break
        case 403:
          errorMessage = 'You do not have permission to perform this action.'
          break
        case 404:
          errorMessage = 'The requested resource was not found.'
          break
        case 422:
          errorMessage = errorMessage || 'Validation failed. Please check your input.'
          break
        case 429:
          errorMessage = 'Too many requests. Please try again later.'
          break
        case 500:
          errorMessage = 'Server error. Please contact support if this persists.'
          break
        case 503:
          errorMessage = 'Service temporarily unavailable. Please try again later.'
          break
      }
    } else if (error.request) {
      // Request was made but no response received
      errorMessage = 'Network error. Please check your connection.'
    } else if (error.message) {
      // Something else happened
      errorMessage = error.message
    }

    // Set global error state
    globalError.value = {
      message: errorMessage,
      details: errorDetails,
      timestamp: new Date(),
      originalError: error
    }

    // Show notification if enabled
    if (showNotification) {
      notificationStore.addNotification({
        type: 'error',
        message: errorMessage,
        duration: 5000
      })
    }

    // Call custom error handler if provided
    if (onError && typeof onError === 'function') {
      onError(error, errorMessage, errorDetails)
    }

    // Log to console in development
    if (import.meta.env.DEV) {
      console.error('[Error Handler]', {
        message: errorMessage,
        details: errorDetails,
        error
      })
    }

    return {
      message: errorMessage,
      details: errorDetails
    }
  }

  /**
   * Clear global error state
   */
  const clearError = () => {
    globalError.value = null
  }

  /**
   * Set global loading state
   */
  const setLoading = (loading) => {
    globalLoading.value = loading
  }

  /**
   * Handle async operations with loading and error states
   * @param {Function} asyncFn - Async function to execute
   * @param {Object} options - Configuration options
   */
  const handleAsync = async (asyncFn, options = {}) => {
    const {
      loadingMessage = null,
      successMessage = null,
      showErrorNotification = true,
      showSuccessNotification = !!successMessage
    } = options

    setLoading(true)
    clearError()

    if (loadingMessage) {
      notificationStore.addNotification({
        type: 'info',
        message: loadingMessage,
        duration: 2000
      })
    }

    try {
      const result = await asyncFn()
      
      if (successMessage && showSuccessNotification) {
        notificationStore.addNotification({
          type: 'success',
          message: successMessage,
          duration: 3000
        })
      }
      
      return { success: true, data: result }
    } catch (error) {
      const errorInfo = handleError(error, {
        showNotification: showErrorNotification
      })
      return { success: false, error: errorInfo }
    } finally {
      setLoading(false)
    }
  }

  /**
   * Retry an async operation with exponential backoff
   * @param {Function} asyncFn - Async function to retry
   * @param {number} maxRetries - Maximum number of retry attempts
   * @param {number} initialDelay - Initial delay in ms
   */
  const retryWithBackoff = async (asyncFn, maxRetries = 3, initialDelay = 1000) => {
    let lastError
    
    for (let attempt = 0; attempt < maxRetries; attempt++) {
      try {
        return await asyncFn()
      } catch (error) {
        lastError = error
        
        if (attempt < maxRetries - 1) {
          const delay = initialDelay * Math.pow(2, attempt)
          await new Promise(resolve => setTimeout(resolve, delay))
        }
      }
    }
    
    throw lastError
  }

  return {
    // State
    globalError,
    globalLoading,
    
    // Methods
    handleError,
    clearError,
    setLoading,
    handleAsync,
    retryWithBackoff
  }
}
