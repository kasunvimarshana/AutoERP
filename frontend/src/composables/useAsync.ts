import { ref, onMounted, onUnmounted } from 'vue';

interface UseAsyncOptions<T> {
  immediate?: boolean;
  initialData?: T;
  onError?: (error: any) => void;
  onSuccess?: (data: T) => void;
}

export function useAsync<T = any>(
  asyncFunction: (...args: any[]) => Promise<T>,
  options: UseAsyncOptions<T> = {}
) {
  const {
    immediate = true,
    initialData = null,
    onError,
    onSuccess,
  } = options;

  const data = ref<T | null>(initialData);
  const loading = ref(false);
  const error = ref<any>(null);
  const abortController = ref<AbortController | null>(null);

  const execute = async (...args: any[]): Promise<T | null> => {
    loading.value = true;
    error.value = null;
    abortController.value = new AbortController();

    try {
      const result = await asyncFunction(...args);
      data.value = result;
      
      if (onSuccess) {
        onSuccess(result);
      }
      
      return result;
    } catch (err: any) {
      if (err.name !== 'AbortError') {
        error.value = err;
        
        if (onError) {
          onError(err);
        }
      }
      
      return null;
    } finally {
      loading.value = false;
      abortController.value = null;
    }
  };

  const abort = () => {
    if (abortController.value) {
      abortController.value.abort();
    }
  };

  const reset = () => {
    data.value = initialData;
    error.value = null;
    loading.value = false;
  };

  onMounted(() => {
    if (immediate) {
      execute();
    }
  });

  onUnmounted(() => {
    abort();
  });

  return {
    data,
    loading,
    error,
    execute,
    abort,
    reset,
  };
}
