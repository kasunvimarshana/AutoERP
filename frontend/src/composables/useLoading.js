import { ref, computed } from 'vue'

// Global loading state registry
const loadingRegistry = ref(new Map())
const globalLoadingCount = ref(0)

export function useLoading(namespace = 'default') {
  /**
   * Start loading for a specific key
   * @param {string} key - Unique identifier for the loading operation
   */
  const startLoading = (key = 'default') => {
    const fullKey = `${namespace}:${key}`
    
    if (!loadingRegistry.value.has(fullKey)) {
      loadingRegistry.value.set(fullKey, true)
      globalLoadingCount.value++
    }
  }

  /**
   * Stop loading for a specific key
   * @param {string} key - Unique identifier for the loading operation
   */
  const stopLoading = (key = 'default') => {
    const fullKey = `${namespace}:${key}`
    
    if (loadingRegistry.value.has(fullKey)) {
      loadingRegistry.value.delete(fullKey)
      globalLoadingCount.value = Math.max(0, globalLoadingCount.value - 1)
    }
  }

  /**
   * Check if a specific operation is loading
   * @param {string} key - Unique identifier for the loading operation
   */
  const isLoading = (key = 'default') => {
    const fullKey = `${namespace}:${key}`
    return loadingRegistry.value.has(fullKey)
  }

  /**
   * Check if any operation in this namespace is loading
   */
  const isAnyLoading = computed(() => {
    for (const [key] of loadingRegistry.value) {
      if (key.startsWith(`${namespace}:`)) {
        return true
      }
    }
    return false
  })

  /**
   * Check if anything globally is loading
   */
  const isGlobalLoading = computed(() => globalLoadingCount.value > 0)

  /**
   * Clear all loading states for this namespace
   */
  const clearAllLoading = () => {
    const keysToDelete = []
    
    for (const [key] of loadingRegistry.value) {
      if (key.startsWith(`${namespace}:`)) {
        keysToDelete.push(key)
      }
    }
    
    keysToDelete.forEach(key => {
      loadingRegistry.value.delete(key)
      globalLoadingCount.value = Math.max(0, globalLoadingCount.value - 1)
    })
  }

  /**
   * Wrap an async function with automatic loading state management
   * @param {Function} asyncFn - Async function to wrap
   * @param {string} key - Loading state key
   */
  const withLoading = async (asyncFn, key = 'default') => {
    startLoading(key)
    try {
      return await asyncFn()
    } finally {
      stopLoading(key)
    }
  }

  /**
   * Create a loading wrapper for a specific operation
   * @param {string} key - Loading state key
   */
  const createLoader = (key = 'default') => {
    return {
      start: () => startLoading(key),
      stop: () => stopLoading(key),
      isLoading: () => isLoading(key),
      wrap: (asyncFn) => withLoading(asyncFn, key)
    }
  }

  return {
    // State
    isLoading,
    isAnyLoading,
    isGlobalLoading,
    
    // Methods
    startLoading,
    stopLoading,
    clearAllLoading,
    withLoading,
    createLoader
  }
}

/**
 * Loading state for specific resource types
 */
export function useResourceLoading(resourceType) {
  const loading = useLoading(resourceType)
  
  return {
    // Common CRUD operations
    isFetching: computed(() => loading.isLoading('fetch')),
    isCreating: computed(() => loading.isLoading('create')),
    isUpdating: computed(() => loading.isLoading('update')),
    isDeleting: computed(() => loading.isLoading('delete')),
    isAnyLoading: loading.isAnyLoading,
    
    // Methods
    startFetching: () => loading.startLoading('fetch'),
    stopFetching: () => loading.stopLoading('fetch'),
    startCreating: () => loading.startLoading('create'),
    stopCreating: () => loading.stopLoading('create'),
    startUpdating: () => loading.startLoading('update'),
    stopUpdating: () => loading.stopLoading('update'),
    startDeleting: () => loading.startLoading('delete'),
    stopDeleting: () => loading.stopLoading('delete'),
    
    // Wrappers
    withFetch: (fn) => loading.withLoading(fn, 'fetch'),
    withCreate: (fn) => loading.withLoading(fn, 'create'),
    withUpdate: (fn) => loading.withLoading(fn, 'update'),
    withDelete: (fn) => loading.withLoading(fn, 'delete')
  }
}
