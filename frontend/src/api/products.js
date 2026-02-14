import apiClient from './client'

export default {
  getAll(params = {}) {
    return apiClient.get('/inventory/products', { params })
  },
  
  get(id) {
    return apiClient.get(`/inventory/products/${id}`)
  },
  
  create(data) {
    return apiClient.post('/inventory/products', data)
  },
  
  update(id, data) {
    return apiClient.put(`/inventory/products/${id}`, data)
  },
  
  delete(id) {
    return apiClient.delete(`/inventory/products/${id}`)
  },
  
  getSkus(productId) {
    return apiClient.get(`/inventory/products/${productId}/skus`)
  }
}
