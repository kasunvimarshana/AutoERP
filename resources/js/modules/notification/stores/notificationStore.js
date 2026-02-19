import { defineStore } from 'pinia';
import { ref } from 'vue';
import { notificationService } from '../services/notificationService';

/**
 * Notification Store
 * 
 * Manages Notification module state (notifications, preferences, channels)
 */
export const useNotificationStore = defineStore('notification', () => {
    // State
    const notifications = ref([]);
    const unreadCount = ref(0);
    const preferences = ref(null);
    const channels = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Notifications
    async function fetchNotifications(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.getAll(params);
            notifications.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function markAsRead(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.markAsRead(id);
            const index = notifications.value.findIndex(n => n.id === id);
            if (index !== -1) {
                notifications.value[index] = response.data;
            }
            if (unreadCount.value > 0) {
                unreadCount.value--;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function markAllAsRead() {
        loading.value = true;
        error.value = null;
        try {
            await notificationService.markAllAsRead();
            notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date() }));
            unreadCount.value = 0;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteNotification(id) {
        loading.value = true;
        error.value = null;
        try {
            await notificationService.delete(id);
            const notification = notifications.value.find(n => n.id === id);
            if (notification && !notification.read_at) {
                unreadCount.value--;
            }
            notifications.value = notifications.value.filter(n => n.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteAllNotifications() {
        loading.value = true;
        error.value = null;
        try {
            await notificationService.deleteAll();
            notifications.value = [];
            unreadCount.value = 0;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function retryNotification(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.retry(id);
            const index = notifications.value.findIndex(n => n.id === id);
            if (index !== -1) {
                notifications.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchUnreadCount() {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.getUnreadCount();
            unreadCount.value = response.data.count || 0;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Preferences
    async function fetchPreferences() {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.preferences.get();
            preferences.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updatePreferences(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.preferences.update(data);
            preferences.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Channels
    async function fetchChannels() {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.channels.getAll();
            channels.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function enableChannel(channel) {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.channels.enable(channel);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function disableChannel(channel) {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.channels.disable(channel);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Push Notifications
    async function subscribeToPush(subscription) {
        loading.value = true;
        error.value = null;
        try {
            const response = await notificationService.push.subscribe(subscription);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function unsubscribeFromPush() {
        loading.value = true;
        error.value = null;
        try {
            await notificationService.push.unsubscribe();
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        // State
        notifications,
        unreadCount,
        preferences,
        channels,
        loading,
        error,

        // Actions - Notifications
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        deleteNotification,
        deleteAllNotifications,
        retryNotification,
        fetchUnreadCount,

        // Actions - Preferences
        fetchPreferences,
        updatePreferences,

        // Actions - Channels
        fetchChannels,
        enableChannel,
        disableChannel,

        // Actions - Push Notifications
        subscribeToPush,
        unsubscribeFromPush,
    };
});
