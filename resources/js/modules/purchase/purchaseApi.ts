import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export type PurchaseOrderStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'partially_received'
    | 'received'
    | 'partially_invoiced'
    | 'invoiced'
    | 'partially_returned'
    | 'returned'
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
    remaining_receivable_quantity?: string;
    remaining_invoiceable_quantity?: string;
    remaining_returnable_quantity?: string;
    unit_price: string;
    line_subtotal?: string;
    discount_calculation_type?: 'fixed' | 'percentage';
    discount_rate?: string;
    discount_amount: string;
    tax_calculation_type?: 'fixed' | 'percentage';
    tax_rate?: string;
    tax_amount: string;
    charge_calculation_type?: 'fixed' | 'percentage';
    charge_rate?: string;
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
    calculation_base?: 'subtotal' | 'subtotal_after_line_discount' | 'subtotal_after_line_adjustments';
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
        discount_calculation_type?: 'fixed' | 'percentage';
        discount_rate?: string;
        discount_amount?: string;
        tax_calculation_type?: 'fixed' | 'percentage';
        tax_rate?: string;
        tax_amount?: string;
        charge_calculation_type?: 'fixed' | 'percentage';
        charge_rate?: string;
        charge_amount?: string;
    }>;
    adjustments?: Array<{
        name: string;
        adjustment_type: string;
        effect: 'increase' | 'decrease';
        calculation_type?: 'fixed' | 'percentage';
        calculation_base?: string;
        rate?: string;
        amount: string;
        allocation_method?: string;
        is_allocatable?: boolean;
        sort_order?: number;
        description?: string;
    }>;
}

export type GoodsReceiptStatus = 'draft' | 'posted' | 'reversed' | 'partially_invoiced' | 'invoiced' | 'partially_returned' | 'returned';
export type PurchaseReturnStatus = 'draft' | 'approved' | 'posted' | 'cancelled';

export interface SourceSummary {
    type?: string | null;
    id?: number | null;
    number?: string | null;
    date?: string | null;
}

export interface GoodsReceiptLine {
    id?: number;
    purchase_order_line_id?: number | null;
    item?: NamedResource | null;
    item_id?: number | null;
    item_variant?: NamedResource | null;
    uom?: NamedResource | null;
    received_quantity: string;
    accepted_quantity: string;
    rejected_quantity?: string;
    invoiced_quantity?: string;
    returned_quantity?: string;
    remaining_quantity?: string;
    unit_price: string;
    line_subtotal?: string;
    line_total?: string;
    status?: string;
}

export interface GoodsReceipt {
    id: number;
    grn_number?: string;
    received_date?: string;
    status?: GoodsReceiptStatus | string;
    purchase_order?: NamedResource & { purchase_order_number?: string; status?: string } | null;
    supplier?: NamedResource | null;
    warehouse?: NamedResource | null;
    warehouse_location?: NamedResource | null;
    subtotal?: string;
    discount_total?: string;
    tax_total?: string;
    charge_total?: string;
    grand_total?: string;
    notes?: string | null;
    posted_at?: string | null;
    lines?: GoodsReceiptLine[];
    adjustments?: PurchaseHeaderAdjustment[];
}

export interface GoodsReceiptPayload {
    received_date: string;
    warehouse_id: number;
    purchase_order_id?: number;
    warehouse_location_id?: number;
    supplier_type?: string;
    supplier_id?: number;
    notes?: string;
    lines: Array<{
        item_id: number;
        received_quantity: string;
        accepted_quantity: string;
        rejected_quantity?: string;
        unit_price: string;
        purchase_order_line_id?: number;
        item_variant_id?: number;
        uom_id?: number;
        ordered_uom_id?: number;
        ordered_quantity?: string;
    }>;
}

export interface ReturnableLine {
    id: number;
    source_line_type: string;
    source_line_id: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    returnable_quantity: string;
    unit_price: string;
}

export interface PurchaseReturnLine {
    id?: number;
    source_line_type: string;
    source_line_id: number;
    item?: NamedResource | null;
    item_id?: number | null;
    item_variant?: NamedResource | null;
    uom?: NamedResource | null;
    returned_quantity: string;
    source_quantity?: string;
    previously_returned_quantity?: string;
    remaining_quantity?: string;
    unit_price: string;
    cost_basis?: string | null;
    line_total?: string;
    reason?: string | null;
}

export interface PurchaseReturn {
    id: number;
    return_number?: string;
    return_date?: string;
    return_type?: 'referenced' | 'manual_supplier_return' | string;
    source_type?: string | null;
    source_id?: number | null;
    source?: SourceSummary | null;
    status?: PurchaseReturnStatus | string;
    supplier?: NamedResource | null;
    warehouse?: NamedResource | null;
    warehouse_location?: NamedResource | null;
    approval_required?: boolean;
    affects_supplier_balance?: boolean;
    cost_basis?: string | null;
    reason?: string | null;
    subtotal?: string;
    adjustment_return_total?: string;
    grand_total?: string;
    debit_note_id?: number | null;
    debit_note?: { id: number; debit_note_number?: string; status?: string } | null;
    lines?: PurchaseReturnLine[];
    adjustment_allocations?: Array<Record<string, unknown>>;
}

export interface PurchaseReturnPayload {
    return_date: string;
    warehouse_id: number;
    warehouse_location_id?: number;
    supplier_type?: string;
    supplier_id?: number;
    reason?: string;
    return_type?: 'referenced' | 'manual_supplier_return';
    source_type?: string;
    source_id?: number;
    approval_required?: boolean;
    affects_supplier_balance?: boolean;
    cost_basis?: string;
    lines: Array<{
        source_line_type: string;
        source_line_id: number;
        returned_quantity: string;
        item_id?: number;
        item_variant_id?: number;
        uom_id?: number;
        unit_price?: string;
        cost_basis?: string;
        reason?: string;
    }>;
}

export interface PurchaseDebitNote {
    id: number;
    debit_note_number?: string;
    debit_note_date?: string;
    status?: string;
    supplier?: NamedResource | null;
    supplier_id?: number | null;
    purchase_return_id?: number | null;
    purchase_return?: { id: number; return_number?: string; status?: string } | null;
    source_type?: string | null;
    source_id?: number | null;
    source?: SourceSummary | null;
    amount?: string;
    allocated_amount?: string;
    remaining_amount?: string;
    reason?: string;
}

export interface PurchaseDebitNotePayload {
    debit_note_date: string;
    debit_note_number?: string;
    supplier_type?: string;
    supplier_id: number;
    amount: string;
    reason: string;
    source_type?: string;
    source_id?: number;
}

export interface PurchaseInvoicePayload {
    invoice_date: string;
    invoice_number?: string;
    supplier_type?: string;
    supplier_id?: number;
    due_date?: string;
    currency_id?: number;
    exchange_rate?: string;
    notes?: string;
    sources: Array<{
        source_type: 'goods_receipt_note' | 'purchase_order';
        source_id: number;
        line_quantities?: Record<number, string>;
    }>;
}

export interface PurchasePaymentPreparePayload {
    payment_date: string;
    amount: string;
    supplier_type?: string;
    supplier_id?: number;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    allocations?: Array<{
        invoice_id: number;
        allocated_amount: string;
        allocation_date?: string;
    }>;
}

export interface InventoryAdjustmentRequestPayload {
    adjustment_date: string;
    adjustment_type: 'increase' | 'decrease' | 'recount' | 'damage' | 'expiry' | 'opening_balance';
    warehouse_id: number;
    warehouse_location_id?: number;
    reason: string;
    notes?: string;
    lines: Array<{
        item_id: number;
        item_variant_id?: number;
        system_quantity: string;
        counted_quantity: string;
        adjustment_quantity: string;
        unit_cost?: string;
        reason?: string;
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

export async function submitPurchaseOrder(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/submit`);
    return response.data.data;
}

export async function getReceivablePurchaseOrderLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseOrderLine[]>>(`${endpoints.purchase}/orders/${id}/receivable-lines`, { signal });
    return response.data.data;
}

export async function getInvoiceablePurchaseOrderLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseOrderLine[]>>(`${endpoints.purchase}/orders/${id}/invoiceable-lines`, { signal });
    return response.data.data;
}

export async function listGoodsReceipts(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts`, { params, signal });
    return response.data;
}

export async function getGoodsReceipt(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}`, { signal });
    return response.data.data;
}

export async function createGoodsReceipt(payload: GoodsReceiptPayload) {
    const response = await apiClient.post<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts`, payload);
    return response.data.data;
}

export async function postGoodsReceipt(id: number) {
    const response = await apiClient.patch<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}/post`);
    return response.data.data;
}

export async function reverseGoodsReceipt(id: number) {
    const response = await apiClient.patch<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}/reverse`);
    return response.data.data;
}

export async function getReturnableGoodsReceiptLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<ReturnableLine[]>>(`${endpoints.purchase}/goods-receipts/${id}/returnable-lines`, { signal });
    return response.data.data;
}

export async function listPurchaseReturns(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseReturn>>(`${endpoints.purchase}/returns`, { params, signal });
    return response.data;
}

export async function getPurchaseReturn(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}`, { signal });
    return response.data.data;
}

export async function createPurchaseReturn(payload: PurchaseReturnPayload) {
    const response = await apiClient.post<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns`, payload);
    return response.data.data;
}

export async function approvePurchaseReturn(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}/approve`);
    return response.data.data;
}

export async function postPurchaseReturn(id: number) {
    const response = await apiClient.patch<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/returns/${id}/post`);
    return response.data.data;
}

export async function cancelPurchaseReturn(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}/cancel`);
    return response.data.data;
}

export async function createManualSupplierReturn(payload: PurchaseReturnPayload) {
    const response = await apiClient.post<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/manual-supplier-returns`, payload);
    return response.data.data;
}

export async function previewPurchaseInvoice(payload: PurchaseInvoicePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/invoices/preview`, payload);
    return response.data.data;
}

export async function createPurchaseInvoice(payload: PurchaseInvoicePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/invoices`, payload);
    return response.data.data;
}

export async function preparePurchasePayment(payload: PurchasePaymentPreparePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/payments/prepare`, payload);
    return response.data.data;
}

export async function listPurchaseDebitNotes(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseDebitNote>>(`${endpoints.purchase}/debit-notes`, { params, signal });
    return response.data;
}

export async function getPurchaseDebitNote(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseDebitNote>>(`${endpoints.purchase}/debit-notes/${id}`, { signal });
    return response.data.data;
}

export async function createPurchaseDebitNote(payload: PurchaseDebitNotePayload) {
    const response = await apiClient.post<ApiResource<PurchaseDebitNote>>(`${endpoints.purchase}/debit-notes`, payload);
    return response.data.data;
}

export async function createPurchaseInventoryAdjustmentRequest(payload: InventoryAdjustmentRequestPayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/inventory-adjustment-requests`, payload);
    return response.data.data;
}

export async function getSupplierItemMappings(supplierId: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Record<string, unknown>>>(`${endpoints.purchase}/suppliers/${supplierId}/item-mappings`, { params: { per_page: 50 }, signal });
    return response.data;
}

export async function searchPurchaseOrders(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listPurchaseOrders({ search, per_page: 20 }, signal);
    return response.data.map((order) => ({
        id: order.id,
        code: order.purchase_order_number,
        name: `${order.purchase_order_number ?? 'Purchase order'}${order.supplier?.name ? ` - ${order.supplier.name}` : ''}`,
    }));
}

export async function searchGoodsReceipts(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listGoodsReceipts({ search, per_page: 20 }, signal);
    return response.data.map((grn) => ({
        id: grn.id,
        code: grn.grn_number,
        name: `${grn.grn_number ?? 'Goods receipt'}${grn.supplier?.name ? ` - ${grn.supplier.name}` : ''}`,
    }));
}
