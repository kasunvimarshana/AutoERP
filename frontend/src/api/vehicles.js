import apiClient from './client'

export default {
  getAll(params = {}) {
    return apiClient.get('/crm/vehicles', { params })
  },
  
  get(id) {
    return apiClient.get(`/crm/vehicles/${id}`)
  },
  
  create(data) {
    return apiClient.post('/crm/vehicles', data)
  },
  
  update(id, data) {
    return apiClient.put(`/crm/vehicles/${id}`, data)
  },
  
  delete(id) {
    return apiClient.delete(`/crm/vehicles/${id}`)
  }
}
