import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/vehicles', { params })
  },

  getById(id) {
    return apiClient.get(`/vehicles/${id}`)
  },

  create(data) {
    return apiClient.post('/vehicles', data)
  },

  update(id, data) {
    return apiClient.put(`/vehicles/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/vehicles/${id}`)
  }
}
