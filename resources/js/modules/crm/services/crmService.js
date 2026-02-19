import apiClient from '@/services/apiClient';

/**
 * CRM Service
 * 
 * API service for CRM module operations (customers, leads, opportunities)
 */
export const crmService = {
    // Customer Operations
    customers: {
        async getAll(params = {}) {
            const response = await apiClient.get('/crm/customers', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/crm/customers/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/crm/customers', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/crm/customers/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/crm/customers/${id}`);
            return response.data;
        },
    },

    // Lead Operations
    leads: {
        async getAll(params = {}) {
            const response = await apiClient.get('/crm/leads', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/crm/leads/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/crm/leads', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/crm/leads/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/crm/leads/${id}`);
            return response.data;
        },

        async convertToCustomer(id) {
            const response = await apiClient.post(`/crm/leads/${id}/convert`);
            return response.data;
        },
    },

    // Opportunity Operations
    opportunities: {
        async getAll(params = {}) {
            const response = await apiClient.get('/crm/opportunities', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/crm/opportunities/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/crm/opportunities', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/crm/opportunities/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/crm/opportunities/${id}`);
            return response.data;
        },

        async updateStage(id, stage) {
            const response = await apiClient.patch(`/crm/opportunities/${id}/stage`, { stage });
            return response.data;
        },
    },
};
