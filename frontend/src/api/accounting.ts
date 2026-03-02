import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface JournalEntry {
  id: number
  tenant_id: number
  entry_number: string
  fiscal_period_id: number
  description: string
  status: 'draft' | 'posted' | 'reversed'
  total_debit: string
  total_credit: string
  posted_at: string | null
  created_at: string
  updated_at: string
}

export interface JournalEntryLine {
  account_id: number
  debit_amount: string
  credit_amount: string
  description: string | null
}

export interface CreateJournalEntryPayload {
  fiscal_period_id: number
  description: string
  lines: JournalEntryLine[]
}

export interface Account {
  id: number
  tenant_id: number
  code: string
  name: string
  type: string
  parent_id: number | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface CreateAccountPayload {
  code: string
  name: string
  account_type_id: number
  parent_id?: number
  is_active?: boolean
}

export type UpdateAccountPayload = Partial<CreateAccountPayload>

export interface FiscalPeriod {
  id: number
  tenant_id: number
  name: string
  start_date: string
  end_date: string
  is_closed: boolean
  created_at: string
  updated_at: string
}

export interface CreateFiscalPeriodPayload {
  name: string
  start_date: string
  end_date: string
}

const accountingApi = {
  // Journal Entries
  listEntries: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<JournalEntry>>('/journals', { params }),

  getEntry: (id: number) =>
    httpClient.get<ApiResponse<JournalEntry>>(`/journals/${id}`),

  createEntry: (payload: CreateJournalEntryPayload) =>
    httpClient.post<ApiResponse<JournalEntry>>('/journals', payload),

  postEntry: (id: number) =>
    httpClient.post<ApiResponse<JournalEntry>>(`/journals/${id}/post`),

  // Chart of Accounts
  listAccounts: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Account>>('/accounting/accounts', { params }),

  createAccount: (payload: CreateAccountPayload) =>
    httpClient.post<ApiResponse<Account>>('/accounting/accounts', payload),

  getAccount: (id: number) =>
    httpClient.get<ApiResponse<Account>>(`/accounting/accounts/${id}`),

  updateAccount: (id: number, payload: UpdateAccountPayload) =>
    httpClient.put<ApiResponse<Account>>(`/accounting/accounts/${id}`, payload),

  // Fiscal Periods
  listFiscalPeriods: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<FiscalPeriod>>('/accounting/fiscal-periods', { params }),

  createFiscalPeriod: (payload: CreateFiscalPeriodPayload) =>
    httpClient.post<ApiResponse<FiscalPeriod>>('/accounting/fiscal-periods', payload),

  getFiscalPeriod: (id: number) =>
    httpClient.get<ApiResponse<FiscalPeriod>>(`/accounting/fiscal-periods/${id}`),

  closeFiscalPeriod: (id: number) =>
    httpClient.post<ApiResponse<FiscalPeriod>>(`/accounting/fiscal-periods/${id}/close`),

  // Financial Statements
  getTrialBalance: (fiscalPeriodId: number) =>
    httpClient.get<ApiResponse<Record<string, unknown>>>(`/accounting/fiscal-periods/${fiscalPeriodId}/trial-balance`),

  getProfitAndLoss: (fiscalPeriodId: number) =>
    httpClient.get<ApiResponse<Record<string, unknown>>>(`/accounting/fiscal-periods/${fiscalPeriodId}/profit-and-loss`),

  getBalanceSheet: (fiscalPeriodId: number) =>
    httpClient.get<ApiResponse<Record<string, unknown>>>(`/accounting/fiscal-periods/${fiscalPeriodId}/balance-sheet`),
}

export default accountingApi
