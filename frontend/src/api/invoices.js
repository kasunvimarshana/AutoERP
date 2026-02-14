import apiClient from './client'

export default {
  getAll(params = {}) {
    return apiClient.get('/billing/invoices', { params })
  },
  
  get(id) {
    return apiClient.get(`/billing/invoices/${id}`)
  },
  
  create(data) {
    return apiClient.post('/billing/invoices', data)
  },
  
  update(id, data) {
    return apiClient.put(`/billing/invoices/${id}`, data)
  },
  
  delete(id) {
    return apiClient.delete(`/billing/invoices/${id}`)
  },
  
  send(id) {
    return apiClient.post(`/billing/invoices/${id}/send`)
  },
  
  void(id) {
    return apiClient.post(`/billing/invoices/${id}/void`)
  },
  
  downloadPdf(id) {
    return apiClient.get(`/billing/invoices/${id}/pdf`, { responseType: 'blob' })
  }
}
