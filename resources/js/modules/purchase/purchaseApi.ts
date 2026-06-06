import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export type PurchaseOrderStatus =
    | 'draft'
    | 'approved'
    | 'partially_received'
    | 'received'
    | 'partially_invoiced'
    | 'invoiced'
    | 'closed'
    | 'cancelled';

export interface PurchaseOrderLine {
    id?: number;
    line_number?: number;
    item_id?: number | null;
    item?: NamedResource | null;
    item_variant_id?: number | null;
    item_variant?: NamedResource | null;
    description?: string | null;
    uom_id?: number | null;
    uom?: NamedResource | null;
    ordered_quantity: string;
    received_quantity?: string;
    invoiced_quantity?: string;
    returned_quantity?: string;
    cancelled_quantity?: string;
    remaining_quantity?: string;
    unit_price: string;
    discount_amount: string;
    tax_amount: string;
    charge_amount: string;
    line_total?: string;
    status?: string;
}

export interface PurchaseHeaderAdjustment {
    id?: number;
    name: string;
    adjustment_type: string;
    effect: 'increase' | 'decrease';
    calculation_type: 'fixed' | 'percentage';
    rate: string;
    amount: string;
    allocated_amount?: string;
    returned_amount?: string;
    remaining_amount?: string;
    allocation_method: string;
    is_allocatable?: boolean;
    sort_order?: number;
    description?: string | null;
}

export interface PurchaseOrder {
    id: number;
    purchase_order_number?: string;
    purchase_order_date?: string;
    expected_delivery_date?: string | null;
    status?: PurchaseOrderStatus;
    supplier_id?: number | null;
    supplier?: NamedResource | null;
    warehouse_id?: number | null;
    warehouse?: NamedResource | null;
    warehouse_location_id?: number | null;
    warehouse_location?: NamedResource | null;
    currency_id?: number | null;
    currency?: NamedResource | null;
    exchange_rate?: string;
    subtotal?: string;
    discount_total?: string;
    tax_total?: string;
    charge_total?: string;
    adjustment_total?: string;
    grand_total?: string;
    received_quantity?: string;
    invoiced_quantity?: string;
    returned_quantity?: string;
    notes?: string | null;
    lines?: PurchaseOrderLine[];
    adjustments?: PurchaseHeaderAdjustment[];
    approved_at?: string | null;
    closed_at?: string | null;
}

export interface PurchaseOrderPayload {
    purchase_order_number?: string;
    purchase_order_date: string;
    supplier_type?: string;
    supplier_id: number;
    warehouse_id: number;
    warehouse_location_id?: number;
    expected_delivery_date?: string;
    currency_id?: number;
    exchange_rate?: string;
    notes?: string;
    lines: Array<{
        item_id: number;
        item_variant_id?: number;
        description?: string;
        uom_id: number;
        ordered_quantity: string;
        unit_price: string;
        discount_amount?: string;
        tax_amount?: string;
        charge_amount?: string;
    }>;
    adjustments?: Array<{
        name: string;
        adjustment_type: string;
        effect: 'increase' | 'decrease';
        calculation_type?: 'fixed' | 'percentage';
        rate?: string;
        amount: string;
        allocation_method?: string;
        is_allocatable?: boolean;
        sort_order?: number;
        description?: string;
    }>;
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

export async function updatePurchaseOrder(id: number, payload: PurchaseOrderPayload) {
    const response = await apiClient.put<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}`, payload);
    return response.data.data;
}

export async function deletePurchaseOrder(id: number) {
    await apiClient.delete(`${endpoints.purchase}/orders/${id}`);
}

export async function approvePurchaseOrder(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/approve`);
    return response.data.data;
}

export async function cancelPurchaseOrder(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/cancel`);
    return response.data.data;
}

export async function closePurchaseOrder(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/close`);
    return response.data.data;
}
