import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { Grn, GrnInput, PurchaseDashboard, PurchaseLookup, PurchaseOrder, PurchaseOrderInput, PurchasePage, PurchaseReturn, PurchaseReturnInput } from '../types/purchase.types';
import type { Invoice } from '../../invoice/types/invoice.types';

type RecordAny = Record<string, any>;

function money(value: unknown) { return String(value ?? '0.0000'); }
function meta<T>(response: ApiCollectionResponse<RecordAny>, query: { page: number; perPage: number }, mapper: (record: RecordAny) => T): PurchasePage<T> {
    return { items: response.data.map(mapper), meta: { currentPage: response.meta?.current_page ?? query.page, lastPage: response.meta?.last_page ?? 1, perPage: response.meta?.per_page ?? query.perPage, total: response.meta?.total ?? response.data.length } };
}
function linePayload(line: any, mode: 'grn' | 'order' | 'return') {
    return {
        accepted_qty: line.acceptedQty || undefined,
        description: line.description || null,
        discount_amount: line.discountAmount || '0',
        discount_type: line.discountType || null,
        discount_value: line.discountValue || '0',
        item_id: line.itemId,
        location_id: line.locationId || null,
        original_grn_line_id: line.originalGrnLineId || null,
        purchase_order_line_id: line.purchaseOrderLineId || null,
        received_qty: mode === 'grn' ? line.receivedQty : undefined,
        return_qty: mode === 'return' ? line.returnQty : undefined,
        tax_amount: line.taxAmount || '0',
        tax_group_id: line.taxGroupId || null,
        unit_price: line.unitPrice,
        uom_id: line.uomId,
        warehouse_id: line.warehouseId || null,
        ordered_qty: mode === 'order' ? line.receivedQty || line.returnQty || line.orderedQty || line.quantity || undefined : undefined,
    };
}
function headerPayload(input: any) {
    const body: RecordAny = {};
    const set = (key: string, value: unknown) => { if (value !== undefined && value !== '') body[key] = value; };
    set('credit_note_total', input.creditNoteTotal);
    set('debit_note_total', input.debitNoteTotal);
    set('header_charge_total', input.headerChargeTotal);
    set('header_credit_adjustment_total', input.headerCreditAdjustmentTotal);
    set('header_debit_adjustment_total', input.headerDebitAdjustmentTotal);
    set('header_discount_amount', input.headerDiscountAmount);
    if (input.headerDiscountType !== undefined) set('header_discount_type', input.headerDiscountType || null);
    set('header_discount_value', input.headerDiscountValue);
    set('header_tax_amount', input.headerTaxAmount);
    if (input.headerTaxGroupId !== undefined) set('header_tax_group_id', input.headerTaxGroupId || null);
    return body;
}
function totals(record: RecordAny) {
    return {
        chargeTotal: money(record.charge_total),
        creditAdjustmentTotal: money(record.credit_adjustment_total),
        creditNoteTotal: money(record.credit_note_total),
        debitAdjustmentTotal: money(record.debit_adjustment_total),
        debitNoteTotal: money(record.debit_note_total),
        discountTotal: money(record.discount_total),
        headerDiscountAmount: money(record.header_discount_amount),
        headerDiscountType: record.header_discount_type,
        headerDiscountValue: record.header_discount_value == null ? null : money(record.header_discount_value),
        headerTaxAmount: money(record.header_tax_amount),
        lineDiscountTotal: money(record.line_discount_total),
        lineTaxTotal: money(record.line_tax_total),
        subtotal: money(record.subtotal),
        taxTotal: money(record.tax_total),
    };
}
function mapOrder(record: RecordAny): PurchaseOrder {
    return { ...totals(record), balance: money(record.balance), expectedDate: record.expected_date, grandTotal: money(record.grand_total), grns: record.grns, id: record.id, invoiceStatus: record.invoice_status, lines: record.lines, notes: record.notes, orderDate: record.order_date, paidAmount: money(record.paid_amount), poNumber: record.po_number, reference: record.reference, status: record.status, supplierBalance: record.supplier_balance, supplierId: record.supplier_id, supplierName: record.supplier_name, warehouseId: record.warehouse_id, warehouseName: record.warehouse_name };
}
function mapGrn(record: RecordAny): Grn {
    return { ...totals(record), grandTotal: money(record.grand_total), grnNumber: record.grn_number, id: record.id, invoiceLinks: record.invoice_links, invoiceStatus: record.invoice_status, lines: record.lines, notes: record.notes, poNumber: record.po_number, purchaseOrderId: record.purchase_order_id, receivedDate: record.received_date, reference: record.reference, status: record.status, supplierId: record.supplier_id, supplierName: record.supplier_name, warehouseId: record.warehouse_id };
}
function mapReturn(record: RecordAny): PurchaseReturn {
    return { ...totals(record), grandTotal: money(record.grand_total), grnNumber: record.grn_number, id: record.id, invoiceLinks: record.invoice_links, lines: record.lines, notes: record.notes, originalGrnId: record.original_grn_id, originalInvoiceId: record.original_invoice_id, reference: record.reference, returnDate: record.return_date, returnNumber: record.return_number, returnReason: record.return_reason, status: record.status, supplierId: record.supplier_id, supplierName: record.supplier_name };
}

export const purchaseApi = {
    async dashboard(): Promise<PurchaseDashboard> { const response = await httpClient<ApiResponse<PurchaseDashboard>>('/api/purchase/dashboard'); return response.data; },
    async lookup(type: string, query: { search?: string; warehouseId?: number } = {}): Promise<PurchaseLookup[]> { const response = await httpClient<ApiResponse<PurchaseLookup[]>>(`/api/purchase/lookups/${type}`, { query: { search: query.search, warehouse_id: query.warehouseId } }); return response.data; },
    async listOrders(query: { page: number; perPage: number; search?: string; status?: string }) { const response = await httpClient<ApiCollectionResponse<RecordAny>>('/api/purchase/purchase-orders', { query: { page: query.page, per_page: query.perPage, search: query.search, status: query.status } }); return meta(response, query, mapOrder); },
    async getOrder(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/purchase-orders/${id}`); return mapOrder(response.data); },
    async createOrder(input: PurchaseOrderInput) { const response = await httpClient<ApiResponse<RecordAny>>('/api/purchase/purchase-orders', { body: { ...headerPayload(input), expected_date: input.expectedDate || null, lines: input.lines.map((line) => ({ ...linePayload(line, 'order'), ordered_qty: line.receivedQty || '1' })), notes: input.notes || null, order_date: input.orderDate, po_number: input.poNumber || null, reference: input.reference || null, supplier_id: input.supplierId, warehouse_id: input.warehouseId }, method: 'POST' }); return mapOrder(response.data); },
    async updateOrder(id: number, input: PurchaseOrderInput) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/purchase-orders/${id}`, { body: { ...headerPayload(input), expected_date: input.expectedDate || null, lines: input.lines.map((line) => ({ ...linePayload(line, 'order'), ordered_qty: line.receivedQty || '1' })), notes: input.notes || null, order_date: input.orderDate, po_number: input.poNumber || null, reference: input.reference || null, supplier_id: input.supplierId, warehouse_id: input.warehouseId }, method: 'PUT' }); return mapOrder(response.data); },
    async confirmOrder(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/purchase-orders/${id}/confirm`, { method: 'POST' }); return mapOrder(response.data); },
    async closeOrder(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/purchase-orders/${id}/close`, { method: 'POST' }); return mapOrder(response.data); },
    async invoiceOrder(id: number, lines?: Array<{ quantity: string; sourceLineId: number }>) { const response = await httpClient<ApiResponse<any>>(`/api/purchase/purchase-orders/${id}/invoice`, { body: lines?.length ? { lines: lines.map((line) => ({ quantity: line.quantity, source_line_id: line.sourceLineId })) } : {}, method: 'POST' }); return response.data as Invoice; },
    async removeOrder(id: number) { await httpClient<void>(`/api/purchase/purchase-orders/${id}`, { method: 'DELETE' }); },
    async listGrns(query: { page: number; perPage: number; search?: string; status?: string }) { const response = await httpClient<ApiCollectionResponse<RecordAny>>('/api/purchase/grns', { query: { page: query.page, per_page: query.perPage, search: query.search, status: query.status } }); return meta(response, query, mapGrn); },
    async getGrn(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/grns/${id}`); return mapGrn(response.data); },
    async createGrn(input: GrnInput) { const response = await httpClient<ApiResponse<RecordAny>>('/api/purchase/grns', { body: { ...headerPayload(input), grn_number: input.grnNumber || null, lines: input.lines.map((line) => linePayload(line, 'grn')), notes: input.notes || null, purchase_order_id: input.purchaseOrderId, received_date: input.receivedDate, reference: input.reference || null, supplier_id: input.supplierId || null, warehouse_id: input.warehouseId || null }, method: 'POST' }); return mapGrn(response.data); },
    async updateGrn(id: number, input: GrnInput) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/grns/${id}`, { body: { ...headerPayload(input), grn_number: input.grnNumber || null, lines: input.lines.map((line) => linePayload(line, 'grn')), notes: input.notes || null, purchase_order_id: input.purchaseOrderId, received_date: input.receivedDate, reference: input.reference || null, supplier_id: input.supplierId || null, warehouse_id: input.warehouseId || null }, method: 'PUT' }); return mapGrn(response.data); },
    async postGrn(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/grns/${id}/post`, { method: 'POST' }); return mapGrn(response.data); },
    async invoiceGrn(id: number, lines?: Array<{ quantity: string; sourceLineId: number }>) { const response = await httpClient<ApiResponse<any>>(`/api/purchase/grns/${id}/invoice`, { body: lines?.length ? { lines: lines.map((line) => ({ quantity: line.quantity, source_line_id: line.sourceLineId })) } : {}, method: 'POST' }); return response.data as Invoice; },
    async removeGrn(id: number) { await httpClient<void>(`/api/purchase/grns/${id}`, { method: 'DELETE' }); },
    async listReturns(query: { page: number; perPage: number; search?: string; status?: string }) { const response = await httpClient<ApiCollectionResponse<RecordAny>>('/api/purchase/purchase-returns', { query: { page: query.page, per_page: query.perPage, search: query.search, status: query.status } }); return meta(response, query, mapReturn); },
    async getReturn(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/purchase-returns/${id}`); return mapReturn(response.data); },
    async createReturn(input: PurchaseReturnInput) { const response = await httpClient<ApiResponse<RecordAny>>('/api/purchase/purchase-returns', { body: { ...headerPayload(input), lines: input.lines.map((line) => linePayload(line, 'return')), notes: input.notes || null, original_grn_id: input.originalGrnId, original_invoice_id: input.originalInvoiceId || null, reference: input.reference || null, return_date: input.returnDate, return_number: input.returnNumber || null, return_reason: input.returnReason || null }, method: 'POST' }); return mapReturn(response.data); },
    async updateReturn(id: number, input: PurchaseReturnInput) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/purchase-returns/${id}`, { body: { ...headerPayload(input), lines: input.lines.map((line) => linePayload(line, 'return')), notes: input.notes || null, original_grn_id: input.originalGrnId, original_invoice_id: input.originalInvoiceId || null, reference: input.reference || null, return_date: input.returnDate, return_number: input.returnNumber || null, return_reason: input.returnReason || null }, method: 'PUT' }); return mapReturn(response.data); },
    async postReturn(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/purchase/purchase-returns/${id}/post`, { method: 'POST' }); return mapReturn(response.data); },
    async removeReturn(id: number) { await httpClient<void>(`/api/purchase/purchase-returns/${id}`, { method: 'DELETE' }); },
};
