import apiClient from '@/services/apiClient';

/**
 * Auth Service
 * API calls for user and role management
 */
export const authService = {
    // User Management
    users: {
        getAll: (params = {}) => apiClient.get('/api/users', { params }),
        getById: (id) => apiClient.get(`/api/users/${id}`),
        create: (data) => apiClient.post('/api/users', data),
        update: (id, data) => apiClient.put(`/api/users/${id}`, data),
        delete: (id) => apiClient.delete(`/api/users/${id}`),
        activate: (id) => apiClient.post(`/api/users/${id}/activate`),
        deactivate: (id) => apiClient.post(`/api/users/${id}/deactivate`),
        resetPassword: (id) => apiClient.post(`/api/users/${id}/reset-password`),
        assignRoles: (id, roleIds) => apiClient.post(`/api/users/${id}/roles`, { role_ids: roleIds }),
        assignPermissions: (id, permissionIds) => apiClient.post(`/api/users/${id}/permissions`, { permission_ids: permissionIds }),
    },

    // Role Management
    roles: {
        getAll: (params = {}) => apiClient.get('/api/roles', { params }),
        getById: (id) => apiClient.get(`/api/roles/${id}`),
        create: (data) => apiClient.post('/api/roles', data),
        update: (id, data) => apiClient.put(`/api/roles/${id}`, data),
        delete: (id) => apiClient.delete(`/api/roles/${id}`),
        assignPermissions: (id, permissionIds) => apiClient.post(`/api/roles/${id}/permissions`, { permission_ids: permissionIds }),
    },

    // Permission Management
    permissions: {
        getAll: (params = {}) => apiClient.get('/api/permissions', { params }),
        getById: (id) => apiClient.get(`/api/permissions/${id}`),
    },

    // Device Management
    devices: {
        getAll: (userId) => apiClient.get(`/api/users/${userId}/devices`),
        revoke: (userId, deviceId) => apiClient.delete(`/api/users/${userId}/devices/${deviceId}`),
        revokeAll: (userId) => apiClient.delete(`/api/users/${userId}/devices`),
    },
};

export default authService;
