// Purchasing Module Types

export interface Supplier {
  id: number
  name: string
  code: string
  email?: string
  phone?: string
  mobile?: string
  address?: Address
  city?: string
  state?: string
  country?: string
  postal_code?: string
  contact_person?: string
  contact_email?: string
  contact_phone?: string
  payment_terms?: string
  credit_limit?: number
  current_balance?: number
  is_active: boolean
  tax_number?: string
  website?: string
  notes?: string
  created_at: string
  updated_at: string
}

export interface PurchaseOrder {
  id: number
  po_number: string
  supplier_id: number
  supplier?: Supplier
  supplier_name?: string
  status: PurchaseOrderStatus
  order_date: string
  expected_delivery_date?: string
  actual_delivery_date?: string
  subtotal: number
  tax_amount: number
  discount_amount: number
  shipping_amount?: number
  total_amount: number
  currency: string
  payment_terms?: string
  delivery_address?: Address
  notes?: string
  items: PurchaseOrderItem[]
  receipts?: GoodsReceipt[]
  created_at: string
  updated_at: string
  approved_at?: string
  approved_by?: number
  approved_by_name?: string
}

export type PurchaseOrderStatus =
  | 'draft'
  | 'pending_approval'
  | 'approved'
  | 'sent'
  | 'confirmed'
  | 'partial_received'
  | 'received'
  | 'completed'
  | 'cancelled'

export interface PurchaseOrderItem {
  id: number
  purchase_order_id: number
  product_id: number
  product_name?: string
  product_sku?: string
  description?: string
  quantity: number
  quantity_received: number
  quantity_pending: number
  unit_price: number
  discount_percent: number
  discount_amount: number
  tax_percent: number
  tax_amount: number
  line_total: number
  notes?: string
}

export interface GoodsReceipt {
  id: number
  receipt_number: string
  purchase_order_id: number
  purchase_order?: PurchaseOrder
  po_number?: string
  receipt_date: string
  status: GoodsReceiptStatus
  warehouse_id?: number
  warehouse_name?: string
  received_by?: number
  received_by_name?: string
  inspected_by?: number
  inspected_by_name?: string
  inspection_date?: string
  inspection_notes?: string
  notes?: string
  items: GoodsReceiptItem[]
  created_at: string
  updated_at: string
}

export type GoodsReceiptStatus =
  | 'pending'
  | 'received'
  | 'inspecting'
  | 'inspected'
  | 'accepted'
  | 'partially_accepted'
  | 'rejected'

export interface GoodsReceiptItem {
  id: number
  goods_receipt_id: number
  purchase_order_item_id: number
  product_id: number
  product_name?: string
  product_sku?: string
  quantity_ordered: number
  quantity_received: number
  quantity_accepted: number
  quantity_rejected: number
  rejection_reason?: string
  batch_number?: string
  serial_number?: string
  expiry_date?: string
  notes?: string
}

export interface Address {
  street?: string
  street2?: string
  city?: string
  state?: string
  postal_code?: string
  country?: string
}

// Query Parameters
export interface SupplierQueryParams {
  page?: number
  per_page?: number
  search?: string
  is_active?: boolean
  country?: string
}

export interface PurchaseOrderQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: PurchaseOrderStatus
  supplier_id?: number
  date_from?: string
  date_to?: string
  expected_delivery_from?: string
  expected_delivery_to?: string
}

export interface GoodsReceiptQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: GoodsReceiptStatus
  purchase_order_id?: number
  warehouse_id?: number
  date_from?: string
  date_to?: string
}

// Form Data
export interface SupplierFormData {
  name: string
  code?: string
  email?: string
  phone?: string
  mobile?: string
  address?: Address
  contact_person?: string
  contact_email?: string
  contact_phone?: string
  payment_terms?: string
  credit_limit?: number
  is_active?: boolean
  tax_number?: string
  website?: string
  notes?: string
}

export interface PurchaseOrderFormData {
  supplier_id: number
  order_date: string
  expected_delivery_date?: string
  currency?: string
  payment_terms?: string
  delivery_address?: Address
  notes?: string
  items: Array<{
    product_id: number
    quantity: number
    unit_price: number
    discount_percent?: number
    tax_percent?: number
    description?: string
    notes?: string
  }>
}

export interface GoodsReceiptFormData {
  purchase_order_id: number
  receipt_date: string
  warehouse_id?: number
  notes?: string
  items: Array<{
    purchase_order_item_id: number
    product_id: number
    quantity_received: number
    quantity_accepted: number
    quantity_rejected: number
    rejection_reason?: string
    batch_number?: string
    serial_number?: string
    expiry_date?: string
    notes?: string
  }>
}

// Additional Types
export interface PurchaseRequisition {
  id: number
  requisition_number: string
  requested_by: number
  requested_by_name?: string
  department?: string
  status: RequisitionStatus
  requested_date: string
  required_date?: string
  notes?: string
  items: PurchaseRequisitionItem[]
  created_at: string
  updated_at: string
  approved_at?: string
  approved_by?: number
}

export type RequisitionStatus =
  | 'draft'
  | 'submitted'
  | 'approved'
  | 'rejected'
  | 'converted'
  | 'cancelled'

export interface PurchaseRequisitionItem {
  id: number
  requisition_id: number
  product_id: number
  product_name?: string
  product_sku?: string
  description?: string
  quantity: number
  estimated_price?: number
  notes?: string
}
