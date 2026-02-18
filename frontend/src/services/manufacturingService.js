import apiClient from './api'

export default {
  // BOMs
  getBOMs(params = {}) {
    return apiClient.get('/manufacturing/boms', { params })
  },
  getBOM(id) {
    return apiClient.get(`/manufacturing/boms/${id}`)
  },
  createBOM(data) {
    return apiClient.post('/manufacturing/boms', data)
  },
  updateBOM(id, data) {
    return apiClient.put(`/manufacturing/boms/${id}`, data)
  },
  deleteBOM(id) {
    return apiClient.delete(`/manufacturing/boms/${id}`)
  },
  
  // Production Orders
  getProductionOrders(params = {}) {
    return apiClient.get('/manufacturing/production-orders', { params })
  },
  getProductionOrder(id) {
    return apiClient.get(`/manufacturing/production-orders/${id}`)
  },
  createProductionOrder(data) {
    return apiClient.post('/manufacturing/production-orders', data)
  },
  updateProductionOrder(id, data) {
    return apiClient.put(`/manufacturing/production-orders/${id}`, data)
  },
  
  // Work Orders
  getWorkOrders(params = {}) {
    return apiClient.get('/manufacturing/work-orders', { params })
  },
  getWorkOrder(id) {
    return apiClient.get(`/manufacturing/work-orders/${id}`)
  },
  createWorkOrder(data) {
    return apiClient.post('/manufacturing/work-orders', data)
  },
  updateWorkOrder(id, data) {
    return apiClient.put(`/manufacturing/work-orders/${id}`, data)
  }
}
