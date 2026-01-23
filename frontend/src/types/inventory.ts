// Inventory Management Types
export interface InventoryItem {
  id: number
  uuid: string
  tenant_id: number | null
  sku: string
  part_number: string | null
  name: string
  description: string | null
  item_type: 'part' | 'consumable' | 'service' | 'labor' | 'dummy'
  category: string | null
  brand: string | null
  manufacturer: string | null
  unit_of_measure: string
  cost_price: number | null
  selling_price: number | null
  markup_percentage: number | null
  quantity_in_stock: number
  minimum_stock_level: number | null
  reorder_quantity: number | null
  location: string | null
  is_taxable: boolean
  is_active: boolean
  is_dummy: boolean
  created_at: string
  updated_at: string
}

export interface Supplier {
  id: number
  uuid: string
  tenant_id: number | null
  supplier_code: string
  company_name: string
  contact_person: string | null
  email: string | null
  phone: string | null
  mobile: string | null
  address: string | null
  city: string | null
  state: string | null
  postal_code: string | null
  country: string | null
  tax_id: string | null
  payment_terms: 'immediate' | 'net_7' | 'net_15' | 'net_30' | 'net_60' | 'net_90'
  credit_limit: number | null
  notes: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface PurchaseOrder {
  id: number
  uuid: string
  tenant_id: number | null
  po_number: string
  supplier_id: number
  status: 'draft' | 'submitted' | 'approved' | 'ordered' | 'received' | 'partially_received' | 'cancelled'
  order_date: string
  expected_delivery_date: string | null
  actual_delivery_date: string | null
  subtotal: number
  tax_amount: number
  shipping_cost: number
  total_amount: number
  notes: string | null
  created_by: number
  approved_by: number | null
  approved_at: string | null
  created_at: string
  updated_at: string
}
