import apiClient from './api'

export default {
  // Suppliers
  getSuppliers(params = {}) {
    return apiClient.get('/procurement/suppliers', { params })
  },
  getSupplier(id) {
    return apiClient.get(`/procurement/suppliers/${id}`)
  },
  createSupplier(data) {
    return apiClient.post('/procurement/suppliers', data)
  },
  updateSupplier(id, data) {
    return apiClient.put(`/procurement/suppliers/${id}`, data)
  },
  deleteSupplier(id) {
    return apiClient.delete(`/procurement/suppliers/${id}`)
  },
  toggleSupplierStatus(id) {
    return apiClient.patch(`/procurement/suppliers/${id}/toggle-status`)
  },
  getSupplierMetrics(id) {
    return apiClient.get(`/procurement/suppliers/${id}/metrics`)
  },
  
  // Purchase Orders
  getPurchaseOrders(params = {}) {
    return apiClient.get('/procurement/purchase-orders', { params })
  },
  getPurchaseOrder(id) {
    return apiClient.get(`/procurement/purchase-orders/${id}`)
  },
  createPurchaseOrder(data) {
    return apiClient.post('/procurement/purchase-orders', data)
  },
  updatePurchaseOrder(id, data) {
    return apiClient.put(`/procurement/purchase-orders/${id}`, data)
  },
  deletePurchaseOrder(id) {
    return apiClient.delete(`/procurement/purchase-orders/${id}`)
  },
  submitPurchaseOrder(id) {
    return apiClient.post(`/procurement/purchase-orders/${id}/submit`)
  },
  approvePurchaseOrder(id) {
    return apiClient.post(`/procurement/purchase-orders/${id}/approve`)
  },
  cancelPurchaseOrder(id) {
    return apiClient.post(`/procurement/purchase-orders/${id}/cancel`)
  },
  
  // GRNs
  getGRNs(params = {}) {
    return apiClient.get('/procurement/grns', { params })
  },
  getGRN(id) {
    return apiClient.get(`/procurement/grns/${id}`)
  },
  createGRN(data) {
    return apiClient.post('/procurement/grns', data)
  },
  updateGRN(id, data) {
    return apiClient.put(`/procurement/grns/${id}`, data)
  },
  completeGRN(id) {
    return apiClient.post(`/procurement/grns/${id}/complete`)
  },
  printGRN(id) {
    return apiClient.get(`/procurement/grns/${id}/print`, { responseType: 'blob' })
  }
}
