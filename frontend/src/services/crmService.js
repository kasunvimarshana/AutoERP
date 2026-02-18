import apiClient from './api'

export default {
  // Customers
  getCustomers(params = {}) {
    return apiClient.get('/crm/customers', { params })
  },
  getCustomer(id) {
    return apiClient.get(`/crm/customers/${id}`)
  },
  createCustomer(data) {
    return apiClient.post('/crm/customers', data)
  },
  updateCustomer(id, data) {
    return apiClient.put(`/crm/customers/${id}`, data)
  },
  deleteCustomer(id) {
    return apiClient.delete(`/crm/customers/${id}`)
  },
  
  // Contacts
  getContacts(params = {}) {
    return apiClient.get('/crm/contacts', { params })
  },
  getContact(id) {
    return apiClient.get(`/crm/contacts/${id}`)
  },
  createContact(data) {
    return apiClient.post('/crm/contacts', data)
  },
  updateContact(id, data) {
    return apiClient.put(`/crm/contacts/${id}`, data)
  },
  deleteContact(id) {
    return apiClient.delete(`/crm/contacts/${id}`)
  }
}
