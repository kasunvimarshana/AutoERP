import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });
    const unreadCount = ref(0);

    async function fetchNotifications(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/notifications', { params: { page } });
            notifications.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load notifications.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchUnreadCount() {
        try {
            const { data } = await api.get('/api/v1/notifications/unread-count');
            unreadCount.value = data.data?.unread_count ?? 0;
        } catch {
            // non-critical
        }
    }

    async function markRead(id) {
        try {
            await api.put(`/api/v1/notifications/${id}/read`);
            const n = notifications.value.find((x) => x.id === id);
            if (n) n.read_at = new Date().toISOString();
            if (unreadCount.value > 0) unreadCount.value -= 1;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to mark as read.';
        }
    }

    async function markAllRead() {
        try {
            await api.put('/api/v1/notifications/read-all');
            notifications.value.forEach((n) => {
                if (!n.read_at) n.read_at = new Date().toISOString();
            });
            unreadCount.value = 0;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to mark all as read.';
        }
    }

    async function remove(id) {
        try {
            await api.delete(`/api/v1/notifications/${id}`);
            notifications.value = notifications.value.filter((n) => n.id !== id);
            meta.value.total = Math.max(0, meta.value.total - 1);
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to delete notification.';
        }
    }

    return {
        notifications, loading, error, meta, unreadCount,
        fetchNotifications, fetchUnreadCount, markRead, markAllRead, remove,
    };
});
