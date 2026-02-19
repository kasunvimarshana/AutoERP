import { ref, computed } from 'vue';

export function useAsync(asyncFunction) {
  const data = ref(null);
  const error = ref(null);
  const loading = ref(false);

  const execute = async (...args) => {
    loading.value = true;
    error.value = null;
    
    try {
      const result = await asyncFunction(...args);
      data.value = result;
      return result;
    } catch (err) {
      error.value = err;
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const reset = () => {
    data.value = null;
    error.value = null;
    loading.value = false;
  };

  const isSuccess = computed(() => {
    return !loading.value && !error.value && data.value !== null;
  });

  const isError = computed(() => {
    return !loading.value && error.value !== null;
  });

  return {
    data,
    error,
    loading,
    isSuccess,
    isError,
    execute,
    reset,
  };
}
