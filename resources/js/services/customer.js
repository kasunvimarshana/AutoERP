import api from './api';

export const getCustomers = async (params = {}) => {
    return await api.get('/customers', { params });
};

export const getCustomer = async (id) => {
    return await api.get(`/customers/${id}`);
};

export const createCustomer = async (data) => {
    return await api.post('/customers', data);
};

export const updateCustomer = async (id, data) => {
    return await api.put(`/customers/${id}`, data);
};

export const deleteCustomer = async (id) => {
    return await api.delete(`/customers/${id}`);
};

export const searchCustomers = async (query) => {
    return await api.get('/customers/search', { params: { q: query } });
};
