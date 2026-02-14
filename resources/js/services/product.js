import api from './api';

export const getProducts = async (params = {}) => {
    return await api.get('/products', { params });
};

export const getProduct = async (id) => {
    return await api.get(`/products/${id}`);
};

export const createProduct = async (data) => {
    return await api.post('/products', data);
};

export const updateProduct = async (id, data) => {
    return await api.put(`/products/${id}`, data);
};

export const deleteProduct = async (id) => {
    return await api.delete(`/products/${id}`);
};

export const searchProducts = async (query) => {
    return await api.get('/products/search', { params: { q: query } });
};

export const getProductsByCategory = async (category) => {
    return await api.get('/products/category', { params: { category } });
};
