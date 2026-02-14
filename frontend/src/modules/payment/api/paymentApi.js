import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/payments', { params })
  },

  getById(id) {
    return apiClient.get(`/payments/${id}`)
  },

  create(data) {
    return apiClient.post('/payments', data)
  },

  update(id, data) {
    return apiClient.put(`/payments/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/payments/${id}`)
  }
}
