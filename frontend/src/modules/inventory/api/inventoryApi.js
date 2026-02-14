import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/inventorys', { params })
  },

  getById(id) {
    return apiClient.get(`/inventorys/${id}`)
  },

  create(data) {
    return apiClient.post('/inventorys', data)
  },

  update(id, data) {
    return apiClient.put(`/inventorys/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/inventorys/${id}`)
  }
}
