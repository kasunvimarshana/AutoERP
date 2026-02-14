import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/users', { params })
  },

  getById(id) {
    return apiClient.get(`/users/${id}`)
  },

  create(data) {
    return apiClient.post('/users', data)
  },

  update(id, data) {
    return apiClient.put(`/users/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/users/${id}`)
  }
}
