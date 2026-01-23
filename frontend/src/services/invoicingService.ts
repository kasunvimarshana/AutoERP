import apiClient from './api'
import type { Invoice, Payment } from '@/types/invoicing'

export const invoiceService = {
  /**
   * Get all invoices with optional filters
   */
  async getInvoices(filters?: Record<string, any>) {
    const response = await apiClient.get('/invoices', { params: filters })
    return response.data
  },

  /**
   * Get a single invoice by ID
   */
  async getInvoice(id: number) {
    const response = await apiClient.get(`/invoices/${id}`)
    return response.data
  },

  /**
   * Create a new invoice
   */
  async createInvoice(data: Partial<Invoice>) {
    const response = await apiClient.post('/invoices', data)
    return response.data
  },

  /**
   * Update an existing invoice
   */
  async updateInvoice(id: number, data: Partial<Invoice>) {
    const response = await apiClient.put(`/invoices/${id}`, data)
    return response.data
  },

  /**
   * Delete an invoice
   */
  async deleteInvoice(id: number) {
    const response = await apiClient.delete(`/invoices/${id}`)
    return response.data
  },

  /**
   * Generate invoice from job card
   */
  async generateFromJobCard(jobCardId: number) {
    const response = await apiClient.post(`/invoices/generate-from-job-card/${jobCardId}`)
    return response.data
  },

  /**
   * Send invoice to customer
   */
  async sendInvoice(id: number) {
    const response = await apiClient.post(`/invoices/${id}/send`)
    return response.data
  },

  /**
   * Mark invoice as paid
   */
  async payInvoice(id: number, paymentData: Partial<Payment>) {
    const response = await apiClient.post(`/invoices/${id}/pay`, paymentData)
    return response.data
  },
}

export const paymentService = {
  /**
   * Get all payments with optional filters
   */
  async getPayments(filters?: Record<string, any>) {
    const response = await apiClient.get('/payments', { params: filters })
    return response.data
  },

  /**
   * Get a single payment by ID
   */
  async getPayment(id: number) {
    const response = await apiClient.get(`/payments/${id}`)
    return response.data
  },

  /**
   * Create a new payment
   */
  async createPayment(data: Partial<Payment>) {
    const response = await apiClient.post('/payments', data)
    return response.data
  },

  /**
   * Apply payment to invoice
   */
  async applyPayment(id: number, invoiceId: number) {
    const response = await apiClient.post(`/payments/${id}/apply`, { invoice_id: invoiceId })
    return response.data
  },
}
