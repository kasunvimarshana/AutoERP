import { apiClient } from './client'
import type { PaginatedResponse } from '@/types/api'

export interface Account {
  id: number
  code: string
  name: string
  type: 'asset' | 'liability' | 'equity' | 'revenue' | 'expense'
  parent_id?: number
  parent_code?: string
  parent_name?: string
  level: number
  is_active: boolean
  is_header: boolean
  balance: number
  currency: string
  description?: string
  children?: Account[]
  created_at: string
  updated_at: string
}

export interface JournalEntry {
  id: number
  entry_number: string
  date: string
  reference?: string
  description: string
  status: 'draft' | 'posted' | 'cancelled'
  total_debit: number
  total_credit: number
  currency: string
  lines: JournalEntryLine[]
  posted_at?: string
  posted_by?: number
  created_at: string
  updated_at: string
}

export interface JournalEntryLine {
  id: number
  account_id: number
  account_code?: string
  account_name?: string
  debit: number
  credit: number
  description?: string
}

export interface Invoice {
  id: number
  invoice_number: string
  customer_id: number
  customer_name?: string
  invoice_date: string
  due_date: string
  status: 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled'
  subtotal: number
  tax_amount: number
  discount_amount: number
  total_amount: number
  paid_amount: number
  balance: number
  currency: string
  notes?: string
  items: InvoiceItem[]
  payments?: Payment[]
  created_at: string
  updated_at: string
}

export interface InvoiceItem {
  id: number
  product_id?: number
  description: string
  quantity: number
  unit_price: number
  discount_percent: number
  discount_amount: number
  tax_percent: number
  tax_amount: number
  line_total: number
}

export interface Payment {
  id: number
  payment_number: string
  payment_date: string
  amount: number
  payment_method: string
  reference?: string
  status: 'pending' | 'completed' | 'cancelled'
  notes?: string
  allocations?: PaymentAllocation[]
  created_at: string
  updated_at: string
}

export interface PaymentAllocation {
  id: number
  invoice_id: number
  invoice_number?: string
  amount: number
}

export interface AccountQueryParams {
  page?: number
  per_page?: number
  search?: string
  type?: string
  is_active?: boolean
  parent_id?: number
}

export interface JournalEntryQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
  date_from?: string
  date_to?: string
}

export interface InvoiceQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
  customer_id?: number
  date_from?: string
  date_to?: string
}

export interface PaymentQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
  payment_method?: string
  date_from?: string
  date_to?: string
}

export const accountingApi = {
  // Account endpoints
  async getAccounts(params: AccountQueryParams = {}): Promise<PaginatedResponse<Account>> {
    const response = await apiClient.get('/accounting/accounts', { params })
    return response.data
  },

  async getAccountTree(): Promise<Account[]> {
    const response = await apiClient.get('/accounting/accounts/tree')
    return response.data
  },

  async getAccount(id: string | number): Promise<Account> {
    const response = await apiClient.get(`/accounting/accounts/${id}`)
    return response.data
  },

  async createAccount(data: Partial<Account>): Promise<Account> {
    const response = await apiClient.post('/accounting/accounts', data)
    return response.data
  },

  async updateAccount(id: string | number, data: Partial<Account>): Promise<Account> {
    const response = await apiClient.put(`/accounting/accounts/${id}`, data)
    return response.data
  },

  async deleteAccount(id: string | number): Promise<void> {
    await apiClient.delete(`/accounting/accounts/${id}`)
  },

  // Journal Entry endpoints
  async getJournalEntries(params: JournalEntryQueryParams = {}): Promise<PaginatedResponse<JournalEntry>> {
    const response = await apiClient.get('/accounting/journal-entries', { params })
    return response.data
  },

  async getJournalEntry(id: string | number): Promise<JournalEntry> {
    const response = await apiClient.get(`/accounting/journal-entries/${id}`)
    return response.data
  },

  async createJournalEntry(data: Partial<JournalEntry>): Promise<JournalEntry> {
    const response = await apiClient.post('/accounting/journal-entries', data)
    return response.data
  },

  async updateJournalEntry(id: string | number, data: Partial<JournalEntry>): Promise<JournalEntry> {
    const response = await apiClient.put(`/accounting/journal-entries/${id}`, data)
    return response.data
  },

  async postJournalEntry(id: string | number): Promise<JournalEntry> {
    const response = await apiClient.post(`/accounting/journal-entries/${id}/post`)
    return response.data
  },

  async deleteJournalEntry(id: string | number): Promise<void> {
    await apiClient.delete(`/accounting/journal-entries/${id}`)
  },

  // Invoice endpoints
  async getInvoices(params: InvoiceQueryParams = {}): Promise<PaginatedResponse<Invoice>> {
    const response = await apiClient.get('/accounting/invoices', { params })
    return response.data
  },

  async getInvoice(id: string | number): Promise<Invoice> {
    const response = await apiClient.get(`/accounting/invoices/${id}`)
    return response.data
  },

  async createInvoice(data: Partial<Invoice>): Promise<Invoice> {
    const response = await apiClient.post('/accounting/invoices', data)
    return response.data
  },

  async updateInvoice(id: string | number, data: Partial<Invoice>): Promise<Invoice> {
    const response = await apiClient.put(`/accounting/invoices/${id}`, data)
    return response.data
  },

  async generateInvoiceFromOrder(orderId: string | number): Promise<Invoice> {
    const response = await apiClient.post(`/accounting/invoices/from-order/${orderId}`)
    return response.data
  },

  async sendInvoice(id: string | number): Promise<Invoice> {
    const response = await apiClient.post(`/accounting/invoices/${id}/send`)
    return response.data
  },

  async markInvoiceAsPaid(id: string | number): Promise<Invoice> {
    const response = await apiClient.post(`/accounting/invoices/${id}/mark-paid`)
    return response.data
  },

  async deleteInvoice(id: string | number): Promise<void> {
    await apiClient.delete(`/accounting/invoices/${id}`)
  },

  // Payment endpoints
  async getPayments(params: PaymentQueryParams = {}): Promise<PaginatedResponse<Payment>> {
    const response = await apiClient.get('/accounting/payments', { params })
    return response.data
  },

  async getPayment(id: string | number): Promise<Payment> {
    const response = await apiClient.get(`/accounting/payments/${id}`)
    return response.data
  },

  async createPayment(data: Partial<Payment>): Promise<Payment> {
    const response = await apiClient.post('/accounting/payments', data)
    return response.data
  },

  async updatePayment(id: string | number, data: Partial<Payment>): Promise<Payment> {
    const response = await apiClient.put(`/accounting/payments/${id}`, data)
    return response.data
  },

  async allocatePayment(id: string | number, allocations: PaymentAllocation[]): Promise<Payment> {
    const response = await apiClient.post(`/accounting/payments/${id}/allocate`, { allocations })
    return response.data
  },

  async completePayment(id: string | number): Promise<Payment> {
    const response = await apiClient.post(`/accounting/payments/${id}/complete`)
    return response.data
  },

  async cancelPayment(id: string | number): Promise<Payment> {
    const response = await apiClient.post(`/accounting/payments/${id}/cancel`)
    return response.data
  },

  async deletePayment(id: string | number): Promise<void> {
    await apiClient.delete(`/accounting/payments/${id}`)
  },
}

export default accountingApi
