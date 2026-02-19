import apiClient from '@/services/apiClient';

/**
 * Tenant Service
 * 
 * API service for Tenant module operations (tenants, organizations, hierarchy)
 */
export const tenantService = {
    // Tenant Operations
    tenants: {
        async getAll(params = {}) {
            const response = await apiClient.get('/tenants', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/tenants/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/tenants', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/tenants/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/tenants/${id}`);
            return response.data;
        },

        async activate(id) {
            const response = await apiClient.post(`/tenants/${id}/activate`);
            return response.data;
        },

        async deactivate(id) {
            const response = await apiClient.post(`/tenants/${id}/deactivate`);
            return response.data;
        },

        async getSettings(id) {
            const response = await apiClient.get(`/tenants/${id}/settings`);
            return response.data;
        },

        async updateSettings(id, data) {
            const response = await apiClient.put(`/tenants/${id}/settings`, data);
            return response.data;
        },
    },

    // Organization Operations
    organizations: {
        async getAll(params = {}) {
            const response = await apiClient.get('/organizations', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/organizations/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/organizations', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/organizations/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/organizations/${id}`);
            return response.data;
        },

        async getHierarchy(id) {
            const response = await apiClient.get(`/organizations/${id}/hierarchy`);
            return response.data;
        },

        async getChildren(id) {
            const response = await apiClient.get(`/organizations/${id}/children`);
            return response.data;
        },

        async getUsers(id, params = {}) {
            const response = await apiClient.get(`/organizations/${id}/users`, { params });
            return response.data;
        },

        async addUser(id, userId, data = {}) {
            const response = await apiClient.post(`/organizations/${id}/users`, {
                user_id: userId,
                ...data,
            });
            return response.data;
        },

        async removeUser(id, userId) {
            const response = await apiClient.delete(`/organizations/${id}/users/${userId}`);
            return response.data;
        },
    },

    // Context Switching
    async switchTenant(tenantId) {
        const response = await apiClient.post('/tenants/switch', { tenant_id: tenantId });
        return response.data;
    },

    async switchOrganization(organizationId) {
        const response = await apiClient.post('/organizations/switch', { organization_id: organizationId });
        return response.data;
    },

    async getCurrentContext() {
        const response = await apiClient.get('/context');
        return response.data;
    },
};
