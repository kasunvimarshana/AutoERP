import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface PurchaseOrder extends Record<string, unknown> {
    id: number;
    purchase_order_number?: string;
    purchase_order_date?: string;
    expected_delivery_date?: string | null;
    status?: string;
    supplier?: { id: number; name: string };
    grand_total?: string;
    subtotal?: string;
    lines?: Record<string, unknown>[];
    adjustments?: Record<string, unknown>[];
    goods_receipt_notes?: Record<string, unknown>[];
}

export interface PurchaseOrderPayload {
    purchase_order_date: string;
    supplier_type?: string;
    supplier_id?: number;
    warehouse_id?: number;
    expected_delivery_date?: string;
    currency_id?: number;
    exchange_rate?: string;
    notes?: string;
    lines: Array<{ item_id: number; ordered_quantity: string; unit_price: string; description?: string }>;
}

export async function listPurchaseOrders(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseOrder>>(`${endpoints.purchase}/orders`, { params, signal });
    return response.data;
}

export async function getPurchaseOrder(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}`, { signal });
    return response.data.data;
}

export async function createPurchaseOrder(payload: PurchaseOrderPayload) {
    const response = await apiClient.post<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders`, payload);
    return response.data.data;
}
