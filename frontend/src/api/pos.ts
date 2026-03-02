import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface PosTransaction {
  id: number
  tenant_id: number
  terminal_id: number
  session_id: number
  transaction_number: string
  status: 'open' | 'paid' | 'voided'
  subtotal: string
  tax_amount: string
  discount_amount: string
  total_amount: string
  amount_paid: string
  change_due: string
  created_at: string
  updated_at: string
}

export interface PosSession {
  id: number
  tenant_id: number
  terminal_id: number
  opened_by: number
  opened_at: string
  closed_at: string | null
  status: 'open' | 'closed'
  opening_balance: string
  closing_balance: string | null
  created_at: string
  updated_at: string
}

export interface PosTransactionLine {
  product_id: number
  quantity: string
  unit_price: string
  discount_amount: string
  tax_rate: string
}

export interface CreatePosTransactionPayload {
  terminal_id: number
  session_id: number
  lines: PosTransactionLine[]
  payments: Array<{ payment_method: string; amount: string }>
  discount_amount?: string
}

export interface OpenSessionPayload {
  terminal_id: number
  opening_balance: string
}

const posApi = {
  // Transactions
  listTransactions: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<PosTransaction>>('/pos/transactions', { params }),

  getTransaction: (id: number) =>
    httpClient.get<ApiResponse<PosTransaction>>(`/pos/transactions/${id}`),

  createTransaction: (payload: CreatePosTransactionPayload) =>
    httpClient.post<ApiResponse<PosTransaction>>('/pos/transactions', payload),

  voidTransaction: (id: number) =>
    httpClient.post<ApiResponse<PosTransaction>>(`/pos/transactions/${id}/void`),

  syncOfflineTransactions: (transactions: CreatePosTransactionPayload[]) =>
    httpClient.post<ApiResponse<null>>('/pos/sync', { transactions }),

  // Sessions
  listSessions: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<PosSession>>('/pos/sessions', { params }),

  openSession: (payload: OpenSessionPayload) =>
    httpClient.post<ApiResponse<PosSession>>('/pos/sessions', payload),

  closeSession: (id: number, payload: { closing_balance: string }) =>
    httpClient.post<ApiResponse<PosSession>>(`/pos/sessions/${id}/close`, payload),
}

export default posApi
