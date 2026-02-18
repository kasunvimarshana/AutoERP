import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Notification } from '@/types';

export const useUiStore = defineStore('ui', () => {
  // State
  const notifications = ref<Notification[]>([]);
  const sidebarOpen = ref(true);
  const loading = ref(false);
  const modalOpen = ref(false);
  const modalComponent = ref<any>(null);
  const modalProps = ref<Record<string, any>>({});

  // Actions
  function addNotification(notification: Omit<Notification, 'id'>) {
    const id = `notification-${Date.now()}-${Math.random()}`;
    const newNotification: Notification = {
      id,
      duration: 5000,
      ...notification,
    };

    notifications.value.push(newNotification);

    // Auto-remove after duration
    if (newNotification.duration && newNotification.duration > 0) {
      setTimeout(() => {
        removeNotification(id);
      }, newNotification.duration);
    }

    return id;
  }

  function removeNotification(id: string) {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index > -1) {
      notifications.value.splice(index, 1);
    }
  }

  function clearNotifications() {
    notifications.value = [];
  }

  function showSuccess(title: string, message: string) {
    return addNotification({ type: 'success', title, message });
  }

  function showError(title: string, message: string) {
    return addNotification({ type: 'error', title, message });
  }

  function showWarning(title: string, message: string) {
    return addNotification({ type: 'warning', title, message });
  }

  function showInfo(title: string, message: string) {
    return addNotification({ type: 'info', title, message });
  }

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
  }

  function openModal(component: any, props: Record<string, any> = {}) {
    modalComponent.value = component;
    modalProps.value = props;
    modalOpen.value = true;
  }

  function closeModal() {
    modalOpen.value = false;
    modalComponent.value = null;
    modalProps.value = {};
  }

  function setLoading(value: boolean) {
    loading.value = value;
  }

  return {
    // State
    notifications,
    sidebarOpen,
    loading,
    modalOpen,
    modalComponent,
    modalProps,
    // Actions
    addNotification,
    removeNotification,
    clearNotifications,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    toggleSidebar,
    openModal,
    closeModal,
    setLoading,
  };
});
