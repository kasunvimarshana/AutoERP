import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/orders', { params })
  },

  getById(id) {
    return apiClient.get(`/orders/${id}`)
  },

  create(data) {
    return apiClient.post('/orders', data)
  },

  update(id, data) {
    return apiClient.put(`/orders/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/orders/${id}`)
  }
}
