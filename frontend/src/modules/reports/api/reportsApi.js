import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/reportss', { params })
  },

  getById(id) {
    return apiClient.get(`/reportss/${id}`)
  },

  create(data) {
    return apiClient.post('/reportss', data)
  },

  update(id, data) {
    return apiClient.put(`/reportss/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/reportss/${id}`)
  }
}
