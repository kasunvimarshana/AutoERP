// Accounting Module Types

export interface Account {
  id: number
  code: string
  name: string
  type: AccountType
  parent_id?: number
  parent_code?: string
  parent_name?: string
  level: number
  is_active: boolean
  is_header: boolean
  balance: number
  debit_balance?: number
  credit_balance?: number
  currency: string
  description?: string
  children?: Account[]
  created_at: string
  updated_at: string
}

export type AccountType =
  | 'asset'
  | 'liability'
  | 'equity'
  | 'revenue'
  | 'expense'
  | 'contra'

export interface JournalEntry {
  id: number
  entry_number: string
  date: string
  reference?: string
  description: string
  status: JournalEntryStatus
  total_debit: number
  total_credit: number
  currency: string
  lines: JournalEntryLine[]
  posted_at?: string
  posted_by?: number
  posted_by_name?: string
  created_at: string
  updated_at: string
  created_by?: number
  updated_by?: number
}

export type JournalEntryStatus = 'draft' | 'posted' | 'cancelled' | 'reversed'

export interface JournalEntryLine {
  id: number
  journal_entry_id: number
  account_id: number
  account_code?: string
  account_name?: string
  debit: number
  credit: number
  description?: string
  reference?: string
}

export interface Invoice {
  id: number
  invoice_number: string
  customer_id: number
  customer_name?: string
  customer_email?: string
  invoice_date: string
  due_date: string
  status: InvoiceStatus
  subtotal: number
  tax_amount: number
  discount_amount: number
  total_amount: number
  paid_amount: number
  balance: number
  currency: string
  payment_terms?: string
  notes?: string
  terms_conditions?: string
  items: InvoiceItem[]
  payments?: Payment[]
  created_at: string
  updated_at: string
  sent_at?: string
  paid_at?: string
}

export type InvoiceStatus =
  | 'draft'
  | 'sent'
  | 'viewed'
  | 'partial'
  | 'paid'
  | 'overdue'
  | 'cancelled'
  | 'refunded'

export interface InvoiceItem {
  id: number
  invoice_id: number
  product_id?: number
  product_name?: string
  product_sku?: string
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
  payment_method: PaymentMethod
  reference?: string
  transaction_id?: string
  status: PaymentStatus
  notes?: string
  allocations?: PaymentAllocation[]
  created_at: string
  updated_at: string
  created_by?: number
  updated_by?: number
}

export type PaymentMethod =
  | 'cash'
  | 'check'
  | 'credit_card'
  | 'debit_card'
  | 'bank_transfer'
  | 'online'
  | 'mobile'
  | 'other'

export type PaymentStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled' | 'refunded'

export interface PaymentAllocation {
  id: number
  payment_id: number
  invoice_id: number
  invoice_number?: string
  amount: number
  notes?: string
}

export interface FinancialReport {
  name: string
  period: string
  currency: string
  data: any
  generated_at: string
}

export interface BalanceSheet extends FinancialReport {
  assets: AccountBalance[]
  liabilities: AccountBalance[]
  equity: AccountBalance[]
  total_assets: number
  total_liabilities: number
  total_equity: number
}

export interface IncomeStatement extends FinancialReport {
  revenue: AccountBalance[]
  expenses: AccountBalance[]
  total_revenue: number
  total_expenses: number
  net_income: number
}

export interface AccountBalance {
  account_id: number
  account_code: string
  account_name: string
  balance: number
  percentage?: number
}

// Query Parameters
export interface AccountQueryParams {
  page?: number
  per_page?: number
  search?: string
  type?: AccountType
  is_active?: boolean
  parent_id?: number
  level?: number
}

export interface JournalEntryQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: JournalEntryStatus
  date_from?: string
  date_to?: string
  account_id?: number
}

export interface InvoiceQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: InvoiceStatus
  customer_id?: number
  date_from?: string
  date_to?: string
  due_date_from?: string
  due_date_to?: string
}

export interface PaymentQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: PaymentStatus
  payment_method?: PaymentMethod
  date_from?: string
  date_to?: string
  invoice_id?: number
}

// Form Data
export interface AccountFormData {
  code: string
  name: string
  type: AccountType
  parent_id?: number
  is_active?: boolean
  is_header?: boolean
  description?: string
}

export interface JournalEntryFormData {
  date: string
  reference?: string
  description: string
  lines: Array<{
    account_id: number
    debit: number
    credit: number
    description?: string
    reference?: string
  }>
}

export interface InvoiceFormData {
  customer_id: number
  invoice_date: string
  due_date: string
  currency?: string
  payment_terms?: string
  notes?: string
  terms_conditions?: string
  items: Array<{
    product_id?: number
    description: string
    quantity: number
    unit_price: number
    discount_percent?: number
    tax_percent?: number
  }>
}

export interface PaymentFormData {
  payment_date: string
  amount: number
  payment_method: PaymentMethod
  reference?: string
  transaction_id?: string
  notes?: string
  allocations?: Array<{
    invoice_id: number
    amount: number
    notes?: string
  }>
}
