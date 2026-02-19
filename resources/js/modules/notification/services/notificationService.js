import apiClient from '@/services/apiClient';

/**
 * Notification Service
 * 
 * API service for Notification module operations (notifications, preferences, channels)
 */
export const notificationService = {
    // Notification Operations
    async getAll(params = {}) {
        const response = await apiClient.get('/notifications', { params });
        return response.data;
    },

    async getById(id) {
        const response = await apiClient.get(`/notifications/${id}`);
        return response.data;
    },

    async markAsRead(id) {
        const response = await apiClient.patch(`/notifications/${id}/read`);
        return response.data;
    },

    async markAllAsRead() {
        const response = await apiClient.post('/notifications/read-all');
        return response.data;
    },

    async delete(id) {
        const response = await apiClient.delete(`/notifications/${id}`);
        return response.data;
    },

    async deleteAll() {
        const response = await apiClient.delete('/notifications');
        return response.data;
    },

    async getUnreadCount() {
        const response = await apiClient.get('/notifications/unread-count');
        return response.data;
    },

    // Notification Preferences
    preferences: {
        async get() {
            const response = await apiClient.get('/notifications/preferences');
            return response.data;
        },

        async update(data) {
            const response = await apiClient.put('/notifications/preferences', data);
            return response.data;
        },
    },

    // Notification Channels
    channels: {
        async getAll() {
            const response = await apiClient.get('/notifications/channels');
            return response.data;
        },

        async enable(channel) {
            const response = await apiClient.post(`/notifications/channels/${channel}/enable`);
            return response.data;
        },

        async disable(channel) {
            const response = await apiClient.post(`/notifications/channels/${channel}/disable`);
            return response.data;
        },
    },

    // Push Notifications
    push: {
        async subscribe(subscription) {
            const response = await apiClient.post('/notifications/push/subscribe', subscription);
            return response.data;
        },

        async unsubscribe() {
            const response = await apiClient.post('/notifications/push/unsubscribe');
            return response.data;
        },
    },
};
