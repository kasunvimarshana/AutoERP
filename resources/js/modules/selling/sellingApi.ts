import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { NamedResource } from '@/shared/types/common';
import { requestLookup } from '@/shared/api/lookupRequest';

export interface SaleLine {
    id: number;
    line_number: number;
    description: string;
    quantity: string;
    base_quantity: string;
    unit_price: string;
    line_total: string;
    variant?: NamedResource & { code: string } | null;
    batch?: NamedResource | null;
    serial_number?: string | null;
    item: NamedResource & { code: string };
    uom: NamedResource & { code: string };
}

export interface SaleDocument {
    id: number;
    row_version: number;
    sale_number: string;
    sale_date: string;
    due_date: string | null;
    status: string;
    customer: (NamedResource & { customer_number: string }) | null;
    warehouse: NamedResource | null;
    lines: SaleLine[];
    invoice: { id: number; number: string; status: string; grand_total: string; balance_due: string } | null;
    returns?: Array<{ id: number; number: string; date: string; reason: string; credit_amount: string; credit_allocated_amount: string; credit_available_amount: string; lines: Array<{ sale_line_id: number; quantity: string; credit_amount: string }> }>;
}

export interface SalePayload {
    customer_id: number;
    warehouse_id: number;
    sale_date: string;
    due_date?: string;
    lines: Array<{ item_id: number; uom_id: number; quantity: string; item_variant_id?: number; batch_id?: number; serial_number_id?: number }>;
}

export async function listSales(signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<SaleDocument>>('/api/v1/selling/sales', { signal, params: { per_page: 50 } });
    return response.data.data;
}

export async function getSale(id: number, signal?: AbortSignal): Promise<SaleDocument> {
    const response = await apiClient.get<ApiResource<SaleDocument>>(`/api/v1/selling/sales/${id}`, { signal });
    return response.data.data;
}

export async function createSale(payload: SalePayload): Promise<SaleDocument> {
    const response = await apiClient.post<ApiResource<SaleDocument>>('/api/v1/selling/sales', payload, {
        headers: { 'Idempotency-Key': crypto.randomUUID() },
    });
    return response.data.data;
}

export async function createSaleReturn(sale: SaleDocument, payload: { return_date: string; reason: string; lines: Array<{ sale_line_id: number; quantity: string }> }): Promise<SaleDocument> {
    const response = await apiClient.post<ApiResource<SaleDocument>>(`/api/v1/selling/sales/${sale.id}/returns`, {
        ...payload,
        expected_version: sale.row_version,
    }, { headers: { 'Idempotency-Key': crypto.randomUUID() } });
    return response.data.data;
}

export function searchSellingCustomers(params: LookupLoadParams): Promise<LookupResult<NamedResource & { customer_number: string }>> {
    return requestLookup('/api/v1/customers/lookup/active', params);
}

export function searchSellingWarehouses(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return requestLookup('/api/v1/warehouses', params, { is_active: true });
}
