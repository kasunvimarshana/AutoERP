import apiClient from '@/services/apiClient';

/**
 * Sales Service
 * 
 * API service for Sales module operations (quotations, orders, invoices)
 */
export const salesService = {
    // Quotation Operations
    quotations: {
        async getAll(params = {}) {
            const response = await apiClient.get('/sales/quotations', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/sales/quotations/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/sales/quotations', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/sales/quotations/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/sales/quotations/${id}`);
            return response.data;
        },

        async convertToOrder(id) {
            const response = await apiClient.post(`/sales/quotations/${id}/convert`);
            return response.data;
        },

        async sendEmail(id) {
            const response = await apiClient.post(`/sales/quotations/${id}/send`);
            return response.data;
        },
    },

    // Order Operations
    orders: {
        async getAll(params = {}) {
            const response = await apiClient.get('/sales/orders', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/sales/orders/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/sales/orders', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/sales/orders/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/sales/orders/${id}`);
            return response.data;
        },

        async confirm(id) {
            const response = await apiClient.post(`/sales/orders/${id}/confirm`);
            return response.data;
        },

        async cancel(id) {
            const response = await apiClient.post(`/sales/orders/${id}/cancel`);
            return response.data;
        },
    },

    // Invoice Operations
    invoices: {
        async getAll(params = {}) {
            const response = await apiClient.get('/sales/invoices', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/sales/invoices/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/sales/invoices', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/sales/invoices/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/sales/invoices/${id}`);
            return response.data;
        },

        async markAsPaid(id, data) {
            const response = await apiClient.post(`/sales/invoices/${id}/pay`, data);
            return response.data;
        },

        async sendEmail(id) {
            const response = await apiClient.post(`/sales/invoices/${id}/send`);
            return response.data;
        },

        async download(id) {
            const response = await apiClient.get(`/sales/invoices/${id}/download`, {
                responseType: 'blob',
            });
            return response.data;
        },
    },
};
