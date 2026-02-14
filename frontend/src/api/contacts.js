import apiClient from './client'

export default {
  getAll(params = {}) {
    return apiClient.get('/crm/contacts', { params })
  },
  
  get(id) {
    return apiClient.get(`/crm/contacts/${id}`)
  },
  
  create(data) {
    return apiClient.post('/crm/contacts', data)
  },
  
  update(id, data) {
    return apiClient.put(`/crm/contacts/${id}`, data)
  },
  
  delete(id) {
    return apiClient.delete(`/crm/contacts/${id}`)
  }
}
