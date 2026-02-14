import apiClient from './client'

export default {
  getAll(params = {}) {
    return apiClient.get('/appointments', { params })
  },
  
  get(id) {
    return apiClient.get(`/appointments/${id}`)
  },
  
  create(data) {
    return apiClient.post('/appointments', data)
  },
  
  update(id, data) {
    return apiClient.put(`/appointments/${id}`, data)
  },
  
  delete(id) {
    return apiClient.delete(`/appointments/${id}`)
  },
  
  updateStatus(id, status) {
    return apiClient.patch(`/appointments/${id}/status`, { status })
  }
}
