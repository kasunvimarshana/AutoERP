import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface StockItem {
  id: number
  tenant_id: number
  product_id: number
  warehouse_id: number
  stock_location_id: number | null
  uom_id: number
  batch_number: string | null
  lot_number: string | null
  serial_number: string | null
  expiry_date: string | null
  quantity_on_hand: string
  quantity_reserved: string
  quantity_available: string
  costing_method: string
  /**
   * Batch-level purchase cost per unit (i.e. what was paid when the batch was received).
   * Named `cost_price` to distinguish it from `unit_cost` used in StockTransaction,
   * which captures the cost at the time of each individual stock movement (receipt,
   * shipment, transfer, etc.).
   */
  cost_price: string
  created_at: string
  updated_at: string
}

export interface StockTransaction {
  id: number
  tenant_id: number
  transaction_type: string
  warehouse_id: number
  product_id: number
  uom_id: number
  quantity: string
  unit_cost: string
  total_cost: string
  batch_number: string | null
  lot_number: string | null
  serial_number: string | null
  expiry_date: string | null
  notes: string | null
  transacted_at: string
  transacted_by: number | null
  is_pharmaceutical_compliant: boolean
  created_at: string
}

export interface StockLevel {
  quantity_on_hand: string
  quantity_reserved: string
  quantity_available: string
}

export interface RecordTransactionPayload {
  product_id: number
  warehouse_id: number
  uom_id?: number
  transaction_type: string
  quantity: string
  unit_cost: string
  batch_number?: string
  lot_number?: string
  serial_number?: string
  expiry_date?: string
  notes?: string
  is_pharmaceutical_compliant?: boolean
}

export interface ReserveStockPayload {
  product_id: number
  warehouse_id: number
  quantity_reserved: string
  reference_type: string
  reference_id: number
  expires_at?: string
}

export interface CreateBatchPayload {
  warehouse_id: number
  product_id: number
  uom_id: number
  quantity: string
  cost_price: string
  batch_number?: string
  lot_number?: string
  serial_number?: string
  expiry_date?: string
  costing_method?: 'fifo' | 'lifo' | 'weighted_average'
  stock_location_id?: number
}

export interface UpdateBatchPayload {
  cost_price?: string
  expiry_date?: string
  lot_number?: string
  batch_number?: string
  costing_method?: 'fifo' | 'lifo' | 'weighted_average'
}

export interface DeductByStrategyPayload {
  product_id: number
  warehouse_id: number
  uom_id: number
  quantity: string
  unit_cost: string
  strategy?: 'fifo' | 'lifo' | 'fefo' | 'manual'
  batch_number?: string
  notes?: string
  is_pharmaceutical_compliant?: boolean
}

export interface BatchDeductionResult {
  batch_number: string | null
  quantity_deducted: string
  transaction_id: number
}

const inventoryApi = {
  // Stock Items
  listStockItems: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<StockItem>>('/inventory/stock', { params }),

  getStockLevel: (productId: number, warehouseId: number) =>
    httpClient.get<ApiResponse<StockLevel>>(`/inventory/stock/${productId}/${warehouseId}`),

  getStockByFEFO: (productId: number, warehouseId: number) =>
    httpClient.get<ApiResponse<StockItem[]>>(`/inventory/fefo/${productId}/${warehouseId}`),

  // Transactions
  recordTransaction: (payload: RecordTransactionPayload) =>
    httpClient.post<ApiResponse<StockTransaction>>('/inventory/transactions', payload),

  listTransactions: (productId: number, params?: ListParams) =>
    httpClient.get<PaginatedResponse<StockTransaction>>(`/inventory/products/${productId}/transactions`, { params }),

  // Reservations
  reserveStock: (payload: ReserveStockPayload) =>
    httpClient.post<ApiResponse<{ id: number }>>('/inventory/reservations', payload),

  releaseReservation: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/inventory/reservations/${id}`),

  // Batch / Lot Management — Full CRUD
  createBatch: (payload: CreateBatchPayload) =>
    httpClient.post<ApiResponse<StockItem>>('/inventory/batches', payload),

  showBatch: (id: number) =>
    httpClient.get<ApiResponse<StockItem>>(`/inventory/batches/${id}`),

  updateBatch: (id: number, payload: UpdateBatchPayload) =>
    httpClient.patch<ApiResponse<StockItem>>(`/inventory/batches/${id}`, payload),

  deleteBatch: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/inventory/batches/${id}`),

  deductByStrategy: (payload: DeductByStrategyPayload) =>
    httpClient.post<ApiResponse<BatchDeductionResult[]>>('/inventory/batches/deduct', payload),
}

export default inventoryApi
