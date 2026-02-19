import apiClient from '@/services/apiClient';

/**
 * Reporting Service
 * 
 * API service for Reporting module operations (reports, dashboards, analytics)
 */
export const reportingService = {
    // Report Operations
    reports: {
        async getAll(params = {}) {
            const response = await apiClient.get('/reporting/reports', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/reporting/reports/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/reporting/reports', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/reporting/reports/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/reporting/reports/${id}`);
            return response.data;
        },

        async generate(id, params = {}) {
            const response = await apiClient.post(`/reporting/reports/${id}/generate`, params);
            return response.data;
        },

        async export(id, format = 'pdf', params = {}) {
            const response = await apiClient.get(`/reporting/reports/${id}/export/${format}`, {
                params,
                responseType: 'blob',
            });
            return response.data;
        },
    },

    // Dashboard Operations
    dashboards: {
        async getAll(params = {}) {
            const response = await apiClient.get('/reporting/dashboards', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/reporting/dashboards/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/reporting/dashboards', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/reporting/dashboards/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/reporting/dashboards/${id}`);
            return response.data;
        },

        async getData(id, params = {}) {
            const response = await apiClient.get(`/reporting/dashboards/${id}/data`, { params });
            return response.data;
        },
    },

    // Analytics Operations
    analytics: {
        async getSalesAnalytics(params = {}) {
            const response = await apiClient.get('/reporting/analytics/sales', { params });
            return response.data;
        },

        async getRevenueAnalytics(params = {}) {
            const response = await apiClient.get('/reporting/analytics/revenue', { params });
            return response.data;
        },

        async getCustomerAnalytics(params = {}) {
            const response = await apiClient.get('/reporting/analytics/customers', { params });
            return response.data;
        },

        async getProductAnalytics(params = {}) {
            const response = await apiClient.get('/reporting/analytics/products', { params });
            return response.data;
        },

        async getInventoryAnalytics(params = {}) {
            const response = await apiClient.get('/reporting/analytics/inventory', { params });
            return response.data;
        },
    },
};
