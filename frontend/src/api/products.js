import { productClient } from './apiClient';

export const getProducts    = (params) => productClient.get('/products', { params }).then(r => r.data);
export const getProduct     = (id)     => productClient.get(`/products/${id}`).then(r => r.data);
export const createProduct  = (data)   => productClient.post('/products', data).then(r => r.data);
export const updateProduct  = (id, data) => productClient.put(`/products/${id}`, data).then(r => r.data);
export const deleteProduct  = (id)     => productClient.delete(`/products/${id}`).then(r => r.data);
export const getCategories  = ()       => productClient.get('/categories').then(r => r.data);
