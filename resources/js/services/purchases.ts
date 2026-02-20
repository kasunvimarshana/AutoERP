import http from '@/services/http';
import type { Purchase, PaginatedResponse } from '@/types/index';

export interface PurchaseLinePayload {
  product_id: number;
  quantity: number;
  unit_cost: string;
}

export interface PurchasePayload {
  supplier_id?: number | null;
  expected_date?: string | null;
  lines: PurchaseLinePayload[];
  notes?: string | null;
}

export interface PurchaseReturn {
  id: number;
  reference_number: string;
  status: string;
  total_amount: string;
  created_at: string;
}

export interface PurchaseReturnPayload {
  purchase_id: number;
  reason?: string | null;
  lines: Array<{ purchase_line_id: number; quantity: number }>;
}

export const purchaseService = {
  list(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Purchase> | Purchase[]>('/purchases', { params });
  },
  create(payload: PurchasePayload) {
    return http.post<Purchase>('/purchases', payload);
  },
  listReturns(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<PurchaseReturn> | PurchaseReturn[]>('/purchase-returns', { params });
  },
  createReturn(payload: PurchaseReturnPayload) {
    return http.post<PurchaseReturn>('/purchase-returns', payload);
  },
  cancelReturn(id: number) {
    return http.patch(`/purchase-returns/${id}/cancel`);
  },
};
