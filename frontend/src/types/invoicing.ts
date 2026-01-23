// Invoicing Management Types
export interface Invoice {
  id: number
  uuid: string
  tenant_id: number | null
  invoice_number: string
  job_card_id: number | null
  customer_id: number
  vehicle_id: number | null
  status: 'draft' | 'sent' | 'viewed' | 'partially_paid' | 'paid' | 'overdue' | 'cancelled'
  invoice_date: string
  due_date: string
  subtotal: number
  discount_amount: number
  discount_percentage: number
  tax_amount: number
  total_amount: number
  paid_amount: number
  balance: number
  terms_and_conditions: string | null
  notes: string | null
  sent_at: string | null
  paid_at: string | null
  created_by: number
  created_at: string
  updated_at: string
  items?: InvoiceItem[]
}

export interface InvoiceItem {
  id: number
  invoice_id: number
  inventory_item_id: number | null
  item_type: 'part' | 'service' | 'labor' | 'package' | 'custom'
  description: string
  quantity: number
  unit_price: number
  discount_percentage: number
  discount_amount: number
  tax_percentage: number
  tax_amount: number
  line_total: number
  created_at: string
  updated_at: string
}

export interface Payment {
  id: number
  uuid: string
  tenant_id: number | null
  payment_number: string
  invoice_id: number
  customer_id: number
  payment_method: 'cash' | 'credit_card' | 'debit_card' | 'bank_transfer' | 'check' | 'mobile_payment' | 'other'
  amount: number
  payment_date: string
  reference_number: string | null
  notes: string | null
  received_by: number
  created_at: string
  updated_at: string
}
