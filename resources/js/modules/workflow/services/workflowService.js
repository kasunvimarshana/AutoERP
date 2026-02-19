import apiClient from '@/services/apiClient';

/**
 * Workflow Service
 * 
 * API service for Workflow module operations (workflow definitions, instances, tasks)
 */
export const workflowService = {
    // Workflow Definition Operations
    definitions: {
        async getAll(params = {}) {
            const response = await apiClient.get('/workflows/definitions', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/workflows/definitions/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/workflows/definitions', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/workflows/definitions/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/workflows/definitions/${id}`);
            return response.data;
        },

        async activate(id) {
            const response = await apiClient.post(`/workflows/definitions/${id}/activate`);
            return response.data;
        },

        async deactivate(id) {
            const response = await apiClient.post(`/workflows/definitions/${id}/deactivate`);
            return response.data;
        },
    },

    // Workflow Instance Operations
    instances: {
        async getAll(params = {}) {
            const response = await apiClient.get('/workflows/instances', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/workflows/instances/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/workflows/instances', data);
            return response.data;
        },

        async cancel(id) {
            const response = await apiClient.post(`/workflows/instances/${id}/cancel`);
            return response.data;
        },

        async getHistory(id) {
            const response = await apiClient.get(`/workflows/instances/${id}/history`);
            return response.data;
        },
    },

    // Task Operations
    tasks: {
        async getAll(params = {}) {
            const response = await apiClient.get('/workflows/tasks', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/workflows/tasks/${id}`);
            return response.data;
        },

        async complete(id, data = {}) {
            const response = await apiClient.post(`/workflows/tasks/${id}/complete`, data);
            return response.data;
        },

        async assign(id, userId) {
            const response = await apiClient.post(`/workflows/tasks/${id}/assign`, { user_id: userId });
            return response.data;
        },

        async reassign(id, userId) {
            const response = await apiClient.post(`/workflows/tasks/${id}/reassign`, { user_id: userId });
            return response.data;
        },
    },
};
