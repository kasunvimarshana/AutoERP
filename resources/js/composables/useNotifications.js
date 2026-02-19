import { ref } from 'vue';

const notifications = ref([]);
let nextId = 0;

export function useNotifications() {
  const add = (notification) => {
    const id = ++nextId;
    const item = {
      id,
      variant: notification.variant || 'info',
      title: notification.title || '',
      message: notification.message || '',
      duration: notification.duration || 5000,
      dismissible: notification.dismissible !== false,
    };
    
    notifications.value.push(item);
    
    if (item.duration > 0) {
      setTimeout(() => {
        remove(id);
      }, item.duration);
    }
    
    return id;
  };

  const remove = (id) => {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index > -1) {
      notifications.value.splice(index, 1);
    }
  };

  const clear = () => {
    notifications.value = [];
  };

  const showSuccess = (message, title = 'Success') => {
    return add({ variant: 'success', title, message });
  };

  const showError = (message, title = 'Error') => {
    return add({ variant: 'danger', title, message });
  };

  const showWarning = (message, title = 'Warning') => {
    return add({ variant: 'warning', title, message });
  };

  const showInfo = (message, title = 'Info') => {
    return add({ variant: 'info', title, message });
  };

  return {
    notifications,
    add,
    remove,
    clear,
    showSuccess,
    showError,
    showWarning,
    showInfo,
  };
}
