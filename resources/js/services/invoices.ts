import http from '@/services/http';
import type { Invoice, PaginatedResponse } from '@/types/index';

export interface InvoiceItemPayload {
  description: string;
  quantity: number;
  unit_price: string;
}

export interface InvoicePayload {
  order_id?: number | null;
  due_date?: string | null;
  items: InvoiceItemPayload[];
  notes?: string | null;
}

export const invoiceService = {
  list(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Invoice> | Invoice[]>('/invoices', { params });
  },
  create(payload: InvoicePayload) {
    return http.post<Invoice>('/invoices', payload);
  },
  send(id: number) {
    return http.patch<Invoice>(`/invoices/${id}/send`);
  },
  void(id: number) {
    return http.patch<Invoice>(`/invoices/${id}/void`);
  },
};
