import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface PickingOrder {
  id: number
  tenant_id: number
  warehouse_id: number
  reference: string
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled'
  picking_strategy: 'batch' | 'wave' | 'zone'
  created_at: string
  updated_at: string
}

export interface BinLocation {
  id: number
  tenant_id: number
  warehouse_id: number
  zone_id: number
  code: string
  aisle: string | null
  rack: string | null
  level: string | null
  capacity: string
  is_active: boolean
}

const warehouseApi = {
  // Picking Orders
  listPickingOrders: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<PickingOrder>>('/warehouse/picking-orders', { params }),

  getPickingOrder: (id: number) =>
    httpClient.get<ApiResponse<PickingOrder>>(`/warehouse/picking-orders/${id}`),

  createPickingOrder: (payload: Partial<PickingOrder>) =>
    httpClient.post<ApiResponse<PickingOrder>>('/warehouse/picking-orders', payload),

  completePickingOrder: (id: number) =>
    httpClient.post<ApiResponse<PickingOrder>>(`/warehouse/picking-orders/${id}/complete`),

  // Bin Locations
  listBinLocations: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<BinLocation>>('/warehouse/bin-locations', { params }),

  // Putaway
  getPutawayRecommendation: (productId: number, warehouseId: number) =>
    httpClient.get<ApiResponse<BinLocation>>(`/warehouse/putaway/${productId}/${warehouseId}`),
}

export default warehouseApi
