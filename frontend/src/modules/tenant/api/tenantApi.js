import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/tenants', { params })
  },

  getById(id) {
    return apiClient.get(`/tenants/${id}`)
  },

  create(data) {
    return apiClient.post('/tenants', data)
  },

  update(id, data) {
    return apiClient.put(`/tenants/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/tenants/${id}`)
  }
}
