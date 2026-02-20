import http from '@/services/http';
import type { StockItem, Warehouse, PaginatedResponse } from '@/types/index';

export type { Warehouse };

export interface WarehousePayload {
  name: string;
  location?: string | null;
  is_active?: boolean;
}

export const inventoryService = {
  listStock(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<StockItem> | StockItem[]>('/inventory/stock', { params });
  },
  listLowStock(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<StockItem> | StockItem[]>('/inventory/alerts/low-stock', { params });
  },
  listWarehouses(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Warehouse> | Warehouse[]>('/warehouses', { params });
  },
  createWarehouse(payload: WarehousePayload) {
    return http.post<Warehouse>('/warehouses', payload);
  },
  updateWarehouse(id: number, payload: Partial<WarehousePayload>) {
    return http.put<Warehouse>(`/warehouses/${id}`, payload);
  },
  deleteWarehouse(id: number) {
    return http.delete(`/warehouses/${id}`);
  },
};
