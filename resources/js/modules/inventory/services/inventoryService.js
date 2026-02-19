import apiClient from '@/services/apiClient';

/**
 * Inventory Service
 * 
 * API service for Inventory module operations (warehouses, stock movements)
 */
export const inventoryService = {
    // Warehouse Operations
    warehouses: {
        async getAll(params = {}) {
            const response = await apiClient.get('/inventory/warehouses', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/inventory/warehouses/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/inventory/warehouses', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/inventory/warehouses/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/inventory/warehouses/${id}`);
            return response.data;
        },

        async getStock(id, params = {}) {
            const response = await apiClient.get(`/inventory/warehouses/${id}/stock`, { params });
            return response.data;
        },

        async activate(id) {
            const response = await apiClient.post(`/inventory/warehouses/${id}/activate`);
            return response.data;
        },

        async deactivate(id) {
            const response = await apiClient.post(`/inventory/warehouses/${id}/deactivate`);
            return response.data;
        },
    },

    // Stock Operations
    stock: {
        async getAll(params = {}) {
            const response = await apiClient.get('/inventory/stock', { params });
            return response.data;
        },

        async getByProduct(productId, params = {}) {
            const response = await apiClient.get(`/inventory/stock/product/${productId}`, { params });
            return response.data;
        },

        async adjustStock(data) {
            const response = await apiClient.post('/inventory/stock/adjust', data);
            return response.data;
        },

        async transferStock(data) {
            const response = await apiClient.post('/inventory/stock/transfer', data);
            return response.data;
        },

        async getMovements(params = {}) {
            const response = await apiClient.get('/inventory/stock/movements', { params });
            return response.data;
        },

        async reserveStock(data) {
            const response = await apiClient.post('/inventory/stock/reserve', data);
            return response.data;
        },

        async releaseStock(data) {
            const response = await apiClient.post('/inventory/stock/release', data);
            return response.data;
        },

        async receiveStock(data) {
            const response = await apiClient.post('/inventory/stock/receive', data);
            return response.data;
        },

        async issueStock(data) {
            const response = await apiClient.post('/inventory/stock/issue', data);
            return response.data;
        },
    },

    // Inventory Count Operations
    inventoryCounts: {
        async getAll(params = {}) {
            const response = await apiClient.get('/inventory/counts', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/inventory/counts/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/inventory/counts', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/inventory/counts/${id}`, data);
            return response.data;
        },

        async validate(id) {
            const response = await apiClient.post(`/inventory/counts/${id}/validate`);
            return response.data;
        },
    },
};
