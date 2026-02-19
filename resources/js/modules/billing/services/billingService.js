import apiClient from '@/services/apiClient';

/**
 * Billing Service
 * 
 * API service for Billing module operations (plans, subscriptions, payments)
 */
export const billingService = {
    // Subscription Plan Operations
    plans: {
        async getAll(params = {}) {
            const response = await apiClient.get('/billing/plans', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/billing/plans/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/billing/plans', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/billing/plans/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/billing/plans/${id}`);
            return response.data;
        },

        async activate(id) {
            const response = await apiClient.post(`/billing/plans/${id}/activate`);
            return response.data;
        },

        async deactivate(id) {
            const response = await apiClient.post(`/billing/plans/${id}/deactivate`);
            return response.data;
        },
    },

    // Subscription Operations
    subscriptions: {
        async getAll(params = {}) {
            const response = await apiClient.get('/billing/subscriptions', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/billing/subscriptions/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/billing/subscriptions', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/billing/subscriptions/${id}`, data);
            return response.data;
        },

        async cancel(id, data = {}) {
            const response = await apiClient.post(`/billing/subscriptions/${id}/cancel`, data);
            return response.data;
        },

        async renew(id) {
            const response = await apiClient.post(`/billing/subscriptions/${id}/renew`);
            return response.data;
        },

        async changePlan(id, data) {
            const response = await apiClient.post(`/billing/subscriptions/${id}/change-plan`, data);
            return response.data;
        },
    },

    // Payment Operations
    payments: {
        async getAll(params = {}) {
            const response = await apiClient.get('/billing/payments', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/billing/payments/${id}`);
            return response.data;
        },

        async process(data) {
            const response = await apiClient.post('/billing/payments', data);
            return response.data;
        },

        async refund(id, data) {
            const response = await apiClient.post(`/billing/payments/${id}/refund`, data);
            return response.data;
        },
    },

    // Invoice Operations (Billing Module)
    invoices: {
        async getAll(params = {}) {
            const response = await apiClient.get('/billing/invoices', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/billing/invoices/${id}`);
            return response.data;
        },

        async download(id) {
            const response = await apiClient.get(`/billing/invoices/${id}/download`, {
                responseType: 'blob',
            });
            return response.data;
        },
    },
};
