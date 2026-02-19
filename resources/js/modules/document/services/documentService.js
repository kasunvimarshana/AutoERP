import apiClient from '@/services/apiClient';

/**
 * Document Service
 * 
 * API service for Document module operations (document management, uploads, sharing)
 */
export const documentService = {
    // Document Operations
    async getAll(params = {}) {
        const response = await apiClient.get('/documents', { params });
        return response.data;
    },

    async getById(id) {
        const response = await apiClient.get(`/documents/${id}`);
        return response.data;
    },

    async upload(file, metadata = {}) {
        const formData = new FormData();
        formData.append('file', file);
        
        Object.keys(metadata).forEach(key => {
            formData.append(key, metadata[key]);
        });

        const response = await apiClient.post('/documents', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    },

    async update(id, data) {
        const response = await apiClient.put(`/documents/${id}`, data);
        return response.data;
    },

    async delete(id) {
        const response = await apiClient.delete(`/documents/${id}`);
        return response.data;
    },

    async download(id) {
        const response = await apiClient.get(`/documents/${id}/download`, {
            responseType: 'blob',
        });
        return response.data;
    },

    async preview(id) {
        const response = await apiClient.get(`/documents/${id}/preview`);
        return response.data;
    },

    async share(id, data) {
        const response = await apiClient.post(`/documents/${id}/share`, data);
        return response.data;
    },

    async getSharedUsers(id) {
        const response = await apiClient.get(`/documents/${id}/shares`);
        return response.data;
    },

    async revokeShare(id, userId) {
        const response = await apiClient.delete(`/documents/${id}/shares/${userId}`);
        return response.data;
    },

    // Folder Operations
    folders: {
        async getAll(params = {}) {
            const response = await apiClient.get('/documents/folders', { params });
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/documents/folders', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/documents/folders/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/documents/folders/${id}`);
            return response.data;
        },

        async getContents(id, params = {}) {
            const response = await apiClient.get(`/documents/folders/${id}/contents`, { params });
            return response.data;
        },
    },
};
