import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/customers', { params })
  },

  getById(id) {
    return apiClient.get(`/customers/${id}`)
  },

  create(data) {
    return apiClient.post('/customers', data)
  },

  update(id, data) {
    return apiClient.put(`/customers/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/customers/${id}`)
  }
}
