// Product Types
export interface Product {
  id: number
  name: string
  sku: string
  barcode?: string
  type: ProductType
  product_type?: ProductType  // Backend may return this field name instead of 'type'
  description?: string
  cost_price: number
  selling_price: number
  profit_margin?: number
  markup?: number
  reorder_point?: number
  status: ProductStatus
  category_id?: number
  image_url?: string
  total_stock?: number  // Computed field from backend
  created_at: string
  updated_at: string
  deleted_at?: string
}

export type ProductType = 'inventory' | 'service' | 'bundle' | 'composite'
export type ProductStatus = 'active' | 'inactive' | 'discontinued' | 'draft'

export interface ProductQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: ProductStatus
  type?: ProductType
  category_id?: number
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
}

export interface ProductListResponse {
  data: Product[]
  meta: PaginationMeta
}

// Stock Types
export interface StockLevel {
  product_id: number
  warehouse_id: number
  location_id?: number
  available: number
  reserved: number
  allocated: number
  damaged: number
  total: number
  updated_at: string
}

export interface StockMovement {
  id: number
  product_id: number
  warehouse_id: number
  location_id?: number
  transaction_type: TransactionType
  quantity: number
  balance_after: number
  cost_per_unit?: number
  total_cost?: number
  reference?: string
  notes?: string
  created_at: string
  warehouse?: Warehouse
}

export type TransactionType = 
  | 'PURCHASE'
  | 'SALE'
  | 'ADJUSTMENT'
  | 'TRANSFER_IN'
  | 'TRANSFER_OUT'
  | 'RETURN_IN'
  | 'RETURN_OUT'
  | 'DAMAGE'
  | 'PRODUCTION_IN'
  | 'PRODUCTION_OUT'

export interface StockTransactionRequest {
  product_id: number
  warehouse_id: number
  location_id?: number
  quantity: number
  transaction_type: TransactionType
  cost_per_unit?: number
  reference?: string
  notes?: string
}

export interface StockAdjustmentRequest {
  product_id: number
  warehouse_id: number
  quantity: number
  transaction_type?: TransactionType
  reference?: string
  notes?: string
}

// Warehouse Types
export interface Warehouse {
  id: number
  name: string
  code: string
  type: WarehouseType
  address?: string
  city?: string
  state?: string
  postal_code?: string
  country?: string
  contact_person?: string
  contact_phone?: string
  email?: string
  capacity?: number
  is_active: boolean
  created_at: string
  updated_at: string
  stock_summary?: WarehouseStockSummary
}

export type WarehouseType = 'warehouse' | 'distribution_center' | 'retail_store' | 'virtual'

export interface WarehouseQueryParams {
  page?: number
  per_page?: number
  search?: string
  type?: WarehouseType
  is_active?: boolean
}

export interface WarehouseFormData {
  name: string
  code: string
  type: WarehouseType
  address?: string
  city?: string
  state?: string
  postal_code?: string
  country?: string
  contact_person?: string
  contact_phone?: string
  email?: string
  capacity?: number | null
  is_active: boolean
}

export interface WarehouseStockSummary {
  total_products: number
  total_stock: number
  low_stock_products: number
  out_of_stock_products: number
}

// Generic Types
export interface PaginationMeta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
  links?: PaginationLinks
}

export interface PaginationLinks {
  first: string
  last: string
  prev: string | null
  next: string | null
}

// Location Types (for future use)
export interface Location {
  id: number
  warehouse_id: number
  parent_id?: number
  name: string
  code: string
  type: LocationType
  capacity?: number
  is_active: boolean
}

export type LocationType = 'warehouse' | 'zone' | 'aisle' | 'rack' | 'bin' | 'shelf'

// Category Types
export interface Category {
  id: number
  name: string
  code: string
  description?: string
  parent_id?: number | null
  path?: string
  depth?: number
  is_active: boolean
  created_at: string
  updated_at: string
  children?: Category[]
}

export interface CategoryFormData {
  name: string
  code: string
  description?: string
  parent_id?: number | null
  is_active: boolean
}

export interface CategoryQueryParams {
  page?: number
  per_page?: number
  search?: string
  is_active?: boolean
  parent_id?: number
  root?: boolean
}
