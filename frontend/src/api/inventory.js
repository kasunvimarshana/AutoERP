import { inventoryClient } from './apiClient';

export const getInventory       = (params) => inventoryClient.get('/inventory', { params }).then(r => r.data);
export const getInventoryItem   = (id)     => inventoryClient.get(`/inventory/${id}`).then(r => r.data);
export const adjustStock        = (id, data) => inventoryClient.post(`/inventory/${id}/adjust`, data).then(r => r.data);
export const getTransactions    = (id, params) => inventoryClient.get(`/inventory/${id}/transactions`, { params }).then(r => r.data);
export const getLowStock        = ()       => inventoryClient.get('/inventory/low-stock').then(r => r.data);
