import apiClient from './client'

export default {
  getAll(params = {}) {
    return apiClient.get('/crm/customers', { params })
  },
  
  get(id) {
    return apiClient.get(`/crm/customers/${id}`)
  },
  
  create(data) {
    return apiClient.post('/crm/customers', data)
  },
  
  update(id, data) {
    return apiClient.put(`/crm/customers/${id}`, data)
  },
  
  delete(id) {
    return apiClient.delete(`/crm/customers/${id}`)
  },
  
  getContacts(customerId) {
    return apiClient.get(`/crm/customers/${customerId}/contacts`)
  },
  
  getVehicles(customerId) {
    return apiClient.get(`/crm/customers/${customerId}/vehicles`)
  }
}
