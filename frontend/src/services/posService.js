import apiClient from './api'

export default {
  // Quotations
  getQuotations(params = {}) {
    return apiClient.get('/pos/quotations', { params })
  },
  getQuotation(id) {
    return apiClient.get(`/pos/quotations/${id}`)
  },
  createQuotation(data) {
    return apiClient.post('/pos/quotations', data)
  },
  updateQuotation(id, data) {
    return apiClient.put(`/pos/quotations/${id}`, data)
  },
  deleteQuotation(id) {
    return apiClient.delete(`/pos/quotations/${id}`)
  },
  
  // Sales Orders
  getSalesOrders(params = {}) {
    return apiClient.get('/pos/sales-orders', { params })
  },
  getSalesOrder(id) {
    return apiClient.get(`/pos/sales-orders/${id}`)
  },
  createSalesOrder(data) {
    return apiClient.post('/pos/sales-orders', data)
  },
  updateSalesOrder(id, data) {
    return apiClient.put(`/pos/sales-orders/${id}`, data)
  },
  
  // Invoices
  getInvoices(params = {}) {
    return apiClient.get('/pos/invoices', { params })
  },
  getInvoice(id) {
    return apiClient.get(`/pos/invoices/${id}`)
  },
  createInvoice(data) {
    return apiClient.post('/pos/invoices', data)
  },
  
  // Payments
  getPayments(params = {}) {
    return apiClient.get('/pos/payments', { params })
  },
  createPayment(data) {
    return apiClient.post('/pos/payments', data)
  },
  deletePayment(id) {
    return apiClient.delete(`/pos/payments/${id}`)
  }
}
