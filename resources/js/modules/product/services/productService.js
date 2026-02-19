import apiClient from '@/services/apiClient';

/**
 * Product Service
 * 
 * API service for Product module operations
 */
export const productService = {
    /**
     * Get all products with optional filters
     */
    async getAll(params = {}) {
        const response = await apiClient.get('/products', { params });
        return response.data;
    },

    /**
     * Get product by ID
     */
    async getById(id) {
        const response = await apiClient.get(`/products/${id}`);
        return response.data;
    },

    /**
     * Create new product
     */
    async create(data) {
        const response = await apiClient.post('/products', data);
        return response.data;
    },

    /**
     * Update existing product
     */
    async update(id, data) {
        const response = await apiClient.put(`/products/${id}`, data);
        return response.data;
    },

    /**
     * Delete product
     */
    async delete(id) {
        const response = await apiClient.delete(`/products/${id}`);
        return response.data;
    },

    /**
     * Get product categories
     */
    async getCategories() {
        const response = await apiClient.get('/categories');
        return response.data;
    },

    /**
     * Get product units
     */
    async getUnits() {
        const response = await apiClient.get('/units');
        return response.data;
    },
};
