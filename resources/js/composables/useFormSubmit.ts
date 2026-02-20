/**
 * Composable that handles the common "submit form, show saving state, catch errors"
 * pattern so page components don't have to repeat this boilerplate.
 */
import { ref } from 'vue';
import { useNotificationStore } from '@/stores/notifications';

interface FormSubmitOptions<T> {
  /** Async action to run on submit */
  action: () => Promise<T>;
  /** Success notification message (optional) */
  successMessage?: string;
  /** Callback after a successful submission */
  onSuccess?: (result: T) => void;
  /** Whether to show a notification on success (default: true) */
  notify?: boolean;
}

export function useFormSubmit<T = void>() {
  const saving = ref(false);
  const formError = ref<string | null>(null);
  const notifications = useNotificationStore();

  async function submit(options: FormSubmitOptions<T>): Promise<void> {
    saving.value = true;
    formError.value = null;

    try {
      const result = await options.action();

      if (options.notify !== false && options.successMessage) {
        notifications.success(options.successMessage);
      }

      options.onSuccess?.(result);
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
      const msg = err.response?.data?.message ?? 'An unexpected error occurred.';
      formError.value = msg;
    } finally {
      saving.value = false;
    }
  }

  function clearError(): void {
    formError.value = null;
  }

  return { saving, formError, submit, clearError };
}
