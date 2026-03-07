import { orderClient } from './apiClient';

export const getOrders   = (params) => orderClient.get('/orders', { params }).then(r => r.data);
export const getOrder    = (id)     => orderClient.get(`/orders/${id}`).then(r => r.data);
export const createOrder = (data)   => orderClient.post('/orders', data).then(r => r.data);
export const updateOrder = (id, data) => orderClient.put(`/orders/${id}`, data).then(r => r.data);
export const cancelOrder = (id)     => orderClient.post(`/orders/${id}/cancel`).then(r => r.data);
