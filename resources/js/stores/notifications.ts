import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: number;
  type: ToastType;
  message: string;
}

export const useNotificationStore = defineStore('notifications', () => {
  const toasts = ref<Toast[]>([]);
  let _nextId = 1;

  function add(type: ToastType, message: string, duration = 4000): void {
    const id = _nextId++;
    toasts.value.push({ id, type, message });
    setTimeout(() => remove(id), duration);
  }

  function success(message: string, duration?: number): void {
    add('success', message, duration);
  }

  function error(message: string, duration?: number): void {
    add('error', message, duration);
  }

  function warning(message: string, duration?: number): void {
    add('warning', message, duration);
  }

  function info(message: string, duration?: number): void {
    add('info', message, duration);
  }

  function remove(id: number): void {
    const idx = toasts.value.findIndex((t) => t.id === id);
    if (idx !== -1) toasts.value.splice(idx, 1);
  }

  return { toasts, add, success, error, warning, info, remove };
});
