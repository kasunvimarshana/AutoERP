import apiClient from '@/api/client'

export default {
  getAll(params) {
    return apiClient.get('/invoices', { params })
  },

  getById(id) {
    return apiClient.get(`/invoices/${id}`)
  },

  create(data) {
    return apiClient.post('/invoices', data)
  },

  update(id, data) {
    return apiClient.put(`/invoices/${id}`, data)
  },

  delete(id) {
    return apiClient.delete(`/invoices/${id}`)
  }
}
