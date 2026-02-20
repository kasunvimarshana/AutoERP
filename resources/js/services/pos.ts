import http from '@/services/http';
import type { PosTransaction, PaginatedResponse } from '@/types/index';

export interface PosSummaryRow {
  location_id?: number;
  location?: string;
  transaction_count: number;
  total_sales: string;
}

export const posService = {
  listTransactions(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<PosTransaction> | PosTransaction[]>('/pos/transactions', { params });
  },
  getSummary(params?: Record<string, unknown>) {
    return http.get<{ data: PosSummaryRow[] } | PosSummaryRow[]>('/reports/pos-sales-summary', { params });
  },
};
