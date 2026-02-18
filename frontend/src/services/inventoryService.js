import apiClient from './api'

export default {
  // Products
  getProducts(params = {}) {
    return apiClient.get('/inventory/products', { params })
  },
  getProduct(id) {
    return apiClient.get(`/inventory/products/${id}`)
  },
  createProduct(data) {
    return apiClient.post('/inventory/products', data)
  },
  updateProduct(id, data) {
    return apiClient.put(`/inventory/products/${id}`, data)
  },
  deleteProduct(id) {
    return apiClient.delete(`/inventory/products/${id}`)
  },
  
  // Stock Ledgers
  getStockLedgers(params = {}) {
    return apiClient.get('/inventory/stock-ledgers', { params })
  },
  getStockLedger(id) {
    return apiClient.get(`/inventory/stock-ledgers/${id}`)
  },
  
  // Stock Movements
  getStockMovements(params = {}) {
    return apiClient.get('/inventory/stock-movements', { params })
  },
  createStockMovement(data) {
    return apiClient.post('/inventory/stock-movements', data)
  },
  
  // Warehouses
  getWarehouses(params = {}) {
    return apiClient.get('/inventory/warehouses', { params })
  },
  getWarehouse(id) {
    return apiClient.get(`/inventory/warehouses/${id}`)
  },
  createWarehouse(data) {
    return apiClient.post('/inventory/warehouses', data)
  },
  updateWarehouse(id, data) {
    return apiClient.put(`/inventory/warehouses/${id}`, data)
  },
  deleteWarehouse(id) {
    return apiClient.delete(`/inventory/warehouses/${id}`)
  }
}
