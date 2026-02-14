import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/products', { params })
  },

  getById(id) {
    return apiClient.get(`/products/${id}`)
  },

  create(data) {
    return apiClient.post('/products', data)
  },

  update(id, data) {
    return apiClient.put(`/products/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/products/${id}`)
  }
}
