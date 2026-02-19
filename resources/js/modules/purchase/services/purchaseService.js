import apiClient from '@/services/apiClient';

/**
 * Purchase Service
 * 
 * API service for Purchase module operations (vendors, purchase orders, bills)
 */
export const purchaseService = {
    // Vendor Operations
    vendors: {
        async getAll(params = {}) {
            const response = await apiClient.get('/purchase/vendors', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/purchase/vendors/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/purchase/vendors', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/purchase/vendors/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/purchase/vendors/${id}`);
            return response.data;
        },
    },

    // Purchase Order Operations
    purchaseOrders: {
        async getAll(params = {}) {
            const response = await apiClient.get('/purchase/orders', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/purchase/orders/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/purchase/orders', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/purchase/orders/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/purchase/orders/${id}`);
            return response.data;
        },

        async confirm(id) {
            const response = await apiClient.post(`/purchase/orders/${id}/confirm`);
            return response.data;
        },

        async receive(id, data) {
            const response = await apiClient.post(`/purchase/orders/${id}/receive`, data);
            return response.data;
        },
    },

    // Bill Operations
    bills: {
        async getAll(params = {}) {
            const response = await apiClient.get('/purchase/bills', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/purchase/bills/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/purchase/bills', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/purchase/bills/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/purchase/bills/${id}`);
            return response.data;
        },

        async markAsPaid(id, data) {
            const response = await apiClient.post(`/purchase/bills/${id}/pay`, data);
            return response.data;
        },
    },
};
