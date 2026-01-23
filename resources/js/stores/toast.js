import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toast', () => {
  // State
  const toasts = ref([]);
  let nextId = 0;

  // Actions

  /**
   * Add a toast notification
   * @param {Object} options - Toast options
   * @param {string} options.message - Toast message
   * @param {string} options.type - Toast type (success, error, warning, info)
   * @param {number} options.duration - Duration in milliseconds (default: 5000)
   */
  function addToast({ message, type = 'info', duration = 5000 }) {
    const id = nextId++;
    const toast = {
      id,
      message,
      type,
    };

    toasts.value.push(toast);

    // Auto remove after duration
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }

    return id;
  }

  /**
   * Remove a toast by ID
   */
  function removeToast(id) {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index !== -1) {
      toasts.value.splice(index, 1);
    }
  }

  /**
   * Show success toast
   */
  function success(message, duration = 5000) {
    return addToast({ message, type: 'success', duration });
  }

  /**
   * Show error toast
   */
  function error(message, duration = 5000) {
    return addToast({ message, type: 'error', duration });
  }

  /**
   * Show warning toast
   */
  function warning(message, duration = 5000) {
    return addToast({ message, type: 'warning', duration });
  }

  /**
   * Show info toast
   */
  function info(message, duration = 5000) {
    return addToast({ message, type: 'info', duration });
  }

  /**
   * Clear all toasts
   */
  function clearAll() {
    toasts.value = [];
  }

  return {
    // State
    toasts,
    // Actions
    addToast,
    removeToast,
    success,
    error,
    warning,
    info,
    clearAll,
  };
});
