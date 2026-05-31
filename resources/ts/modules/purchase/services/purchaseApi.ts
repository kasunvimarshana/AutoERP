import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    documentPreview,
    financePostingPreview,
    getGrnById,
    getPurchaseInvoiceById,
    getPurchaseOrderById,
    getPurchasePaymentById,
    getPurchaseReturnById,
    grns,
    inventoryEffects,
    invoiceCalculationPreview,
    paymentAllocationPreview,
    purchaseActivity,
    purchaseAdvances,
    purchaseDashboardMetrics,
    purchaseInvoices,
    purchaseOrders,
    purchasePayments,
    purchaseReturns,
    purchaseSettings,
    supplierRefunds,
} from '../mock/purchaseMock';
import type {
    GoodsReceivedNote,
    PurchaseCalculationPreview,
    PurchaseDashboardMetric,
    PurchaseInvoice,
    PurchaseOrder,
    PurchasePayment,
    PurchaseReturn,
} from '../types/purchase.types';

type BackendRecord = Record<string, unknown>;

const PURCHASE_API_MODE = import.meta.env.VITE_PURCHASE_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return PURCHASE_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419, 422]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (PURCHASE_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function normalizeOrder(raw: BackendRecord): PurchaseOrder {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        balance: asString(raw.balance, 'Backend calculated'),
        expectedDate: asString(raw.expected_date ?? raw.expectedDate),
        grandTotal: asString(raw.grand_total ?? raw.grandTotal, 'Backend calculated'),
        id: asString(raw.id),
        lines: lines.map((line, index) => ({
            backendConvertedQuantity: asString(line.base_quantity ?? line.converted_quantity, 'Backend converted'),
            discountAmount: asString(line.discount_amount, 'Backend calculated'),
            id: asString(line.id, `line-${index}`),
            item: asString(line.item_label ?? line.item_name ?? line.item_code, 'Backend item'),
            lineTotal: asString(line.line_total, 'Backend calculated'),
            orderedQuantity: asString(line.quantity ?? line.ordered_quantity, 'Backend quantity'),
            receivedQuantity: asString(line.received_quantity, 'Backend calculated'),
            remainingQuantity: asString(line.remaining_quantity, 'Backend calculated'),
            taxAmount: asString(line.tax_amount, 'Backend calculated'),
            unitPrice: asString(line.unit_price, 'Backend resolved'),
            uom: asString(line.uom_label ?? line.uom_code ?? line.uom_symbol ?? line.uom_name, 'Backend UOM'),
        })),
        orderDate: asString(raw.order_date ?? raw.po_date),
        poNumber: asString(raw.po_number ?? raw.purchase_order_number, `PO-${asString(raw.id)}`),
        status: asString(raw.status, 'draft') as PurchaseOrder['status'],
        supplier: asString(raw.supplier_name ?? raw.supplier_id, 'Backend supplier'),
        updatedAt: asString(raw.updated_at),
        workflow: asString(raw.workflow, 'Backend workflow status'),
    };
}

function normalizeGrn(raw: BackendRecord): GoodsReceivedNote {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        grnDate: asString(raw.grn_date ?? raw.received_date),
        grnNumber: asString(raw.grn_number, `GRN-${asString(raw.id)}`),
        id: asString(raw.id),
        inventoryStatus: asString(raw.inventory_status, 'Backend inventory state'),
        lines: lines.map((line, index) => ({
            acceptedQuantity: asString(line.accepted_qty ?? line.received_quantity, 'Backend quantity'),
            backendBaseQuantity: asString(line.base_quantity, 'Backend converted'),
            id: asString(line.id, `line-${index}`),
            item: asString(line.item_label ?? line.item_name ?? line.item_code, 'Backend item'),
            orderedQuantity: asString(line.ordered_quantity),
            rejectedQuantity: asString(line.rejected_qty, 'Backend quantity'),
            sourceLine: asString(line.purchase_order_line_id),
            uom: asString(line.uom_label ?? line.uom_code ?? line.uom_symbol ?? line.uom_name, 'Backend UOM'),
        })),
        sourcePo: asString(raw.purchase_order_number ?? raw.purchase_order_id),
        status: asString(raw.status, 'draft') as GoodsReceivedNote['status'],
        supplier: asString(raw.supplier_name ?? raw.supplier_id, 'Backend supplier'),
        updatedAt: asString(raw.updated_at),
    };
}

function normalizeInvoice(raw: BackendRecord): PurchaseInvoice {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        balance: asString(raw.balance, 'Backend calculated'),
        documentStatus: asString(raw.document_status, 'Backend document state'),
        dueDate: asString(raw.due_date),
        grandTotal: asString(raw.grand_total, 'Backend calculated'),
        id: asString(raw.id),
        invoiceDate: asString(raw.invoice_date ?? raw.document_date),
        invoiceNumber: asString(raw.invoice_number ?? raw.document_number, `PINV-${asString(raw.id)}`),
        lines: lines.map((line, index) => ({
            discountAmount: asString(line.discount_amount, 'Backend calculated'),
            id: asString(line.id, `line-${index}`),
            invoiceQuantity: asString(line.quantity, 'Backend quantity'),
            item: asString(line.item_label ?? line.item_name ?? line.item_code, 'Backend item'),
            lineTotal: asString(line.line_total, 'Backend calculated'),
            sourceLine: asString(line.source_line_id),
            taxAmount: asString(line.tax_amount, 'Backend calculated'),
            unitPrice: asString(line.unit_price, 'Backend resolved'),
            uom: asString(line.uom_label ?? line.uom_code ?? line.uom_symbol ?? line.uom_name, 'Backend UOM'),
        })),
        paidAmount: asString(raw.paid_amount, 'Backend calculated'),
        sourceReference: asString(raw.source_reference),
        status: asString(raw.status, 'draft') as PurchaseInvoice['status'],
        supplier: asString(raw.supplier_name ?? raw.supplier_id, 'Backend supplier'),
        updatedAt: asString(raw.updated_at),
    };
}

function normalizePayment(raw: BackendRecord): PurchasePayment {
    return {
        allocations: [],
        amount: asString(raw.amount, 'Backend amount'),
        id: asString(raw.id),
        method: asString(raw.payment_method, 'Backend method'),
        paymentDate: asString(raw.payment_date),
        paymentNumber: asString(raw.payment_number, `SPAY-${asString(raw.id)}`),
        reference: asString(raw.reference_number),
        status: asString(raw.status, 'draft') as PurchasePayment['status'],
        supplier: asString(raw.supplier_name ?? raw.supplier_id, 'Backend supplier'),
        unallocatedAmount: asString(raw.unallocated_amount, 'Backend calculated'),
    };
}

function normalizeReturn(raw: BackendRecord): PurchaseReturn {
    return {
        id: asString(raw.id),
        lines: [],
        returnNumber: asString(raw.return_number, `PRET-${asString(raw.id)}`),
        returnTotal: asString(raw.grand_total, 'Backend calculated'),
        sourceReference: asString(raw.source_reference),
        status: asString(raw.status, 'draft') as PurchaseReturn['status'],
        supplier: asString(raw.supplier_name ?? raw.supplier_id, 'Backend supplier'),
        updatedAt: asString(raw.updated_at),
    };
}

export const purchaseApi = {
    dashboard: {
        summary: (): Promise<ApiCollectionResponse<PurchaseDashboardMetric>> => mockCollectionResponse(purchaseDashboardMetrics),
    },
    orders: {
        list: (): Promise<ApiCollectionResponse<PurchaseOrder>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-orders');
                return { ...response, data: response.data.map(normalizeOrder) };
            },
            () => mockCollectionResponse(purchaseOrders),
        ),
        get: (id: string): Promise<ApiResponse<PurchaseOrder>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-orders/${id}`);
                return { ...response, data: normalizeOrder(response.data) };
            },
            () => mockResponse(getPurchaseOrderById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-orders', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-orders/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        createWithLines: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-orders/with-lines', { body: input, method: 'POST' }), () => mockResponse(input)),
        updateWithLines: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-orders/${id}/with-lines`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        transition: (id: string, action: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/workflows/purchase_order/${id}/transition`, { body: { action }, method: 'POST' }), () => mockResponse({ action, id })),
        history: (id: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>(`/api/purchase/workflows/purchase_order/${id}/history`), () => mockCollectionResponse(purchaseActivity)),
    },
    grns: {
        list: (): Promise<ApiCollectionResponse<GoodsReceivedNote>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/grn-headers');
                return { ...response, data: response.data.map(normalizeGrn) };
            },
            () => mockCollectionResponse(grns),
        ),
        get: (id: string): Promise<ApiResponse<GoodsReceivedNote>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/purchase/grn-headers/${id}`);
                return { ...response, data: normalizeGrn(response.data) };
            },
            () => mockResponse(getGrnById(id)),
        ),
        createDirect: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/grn-headers/with-lines', { body: input, method: 'POST' }), () => mockResponse({ input, mode: 'direct-grn' })),
        createFromPo: (purchaseOrderId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/grn-headers/with-lines', { body: { ...(input as object), purchase_order_id: purchaseOrderId }, method: 'POST' }), () => mockResponse({ input, purchaseOrderId })),
        updateWithLines: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/grn-headers/${id}/with-lines`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        postInventory: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/workflows/grn_header/${id}/inventory/post`, { method: 'POST' }), () => mockResponse({ action: 'post-inventory', id })),
        history: (id: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>(`/api/purchase/workflows/grn_header/${id}/history`), () => mockCollectionResponse(purchaseActivity)),
    },
    invoices: {
        list: (): Promise<ApiCollectionResponse<PurchaseInvoice>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-invoices');
                return { ...response, data: response.data.map(normalizeInvoice) };
            },
            () => mockCollectionResponse(purchaseInvoices),
        ),
        get: (id: string): Promise<ApiResponse<PurchaseInvoice>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-invoices/${id}`);
                return { ...response, data: normalizeInvoice(response.data) };
            },
            () => mockResponse(getPurchaseInvoiceById(id)),
        ),
        preview: (input: unknown): Promise<ApiPreviewResponse<unknown, PurchaseCalculationPreview['calculated']>> => withMockFallback(
            () => httpClient<ApiPreviewResponse<unknown, PurchaseCalculationPreview['calculated']>>('/api/purchase/calculate-invoice', { body: input, method: 'POST' }),
            () => mockPreviewResponse(input, invoiceCalculationPreview.calculated, invoiceCalculationPreview.breakdown, invoiceCalculationPreview.warnings),
        ),
        createDirect: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-invoices', { body: input, method: 'POST' }), () => mockResponse(input)),
        createFromPo: (purchaseOrderId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-invoices/from-po', { body: { ...(input as object), purchase_order_id: purchaseOrderId }, method: 'POST' }), () => mockResponse({ input, purchaseOrderId })),
        createFromGrn: (grnHeaderId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-invoices/from-grn', { body: { ...(input as object), grn_header_id: grnHeaderId }, method: 'POST' }), () => mockResponse({ grnHeaderId, input })),
        createFromMultipleGrns: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-invoices/from-multiple-grns', { body: input, method: 'POST' }), () => mockResponse(input)),
        post: (invoiceId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-invoices/${invoiceId}/post`, { method: 'POST' }), () => mockResponse({ action: 'post-requested', invoiceId })),
        cancel: (invoiceId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-invoices/${invoiceId}/cancel`, { method: 'POST' }), () => mockResponse({ action: 'cancel-requested', invoiceId })),
        reverse: (invoiceId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-invoices/${invoiceId}/reverse`, { method: 'POST' }), () => mockResponse({ action: 'reverse-requested', invoiceId })),
        generateDocument: (invoiceId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/workflows/purchase_invoice/${invoiceId}/document`, { method: 'POST' }), () => mockResponse(documentPreview)),
    },
    payments: {
        list: (): Promise<ApiCollectionResponse<PurchasePayment>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-payments');
                return { ...response, data: response.data.map(normalizePayment) };
            },
            () => mockCollectionResponse(purchasePayments),
        ),
        get: (id: string): Promise<ApiResponse<PurchasePayment>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-payments/${id}`);
                return { ...response, data: normalizePayment(response.data) };
            },
            () => mockResponse(getPurchasePaymentById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-payments', { body: input, method: 'POST' }), () => mockResponse(input)),
        post: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-payments/${id}/post`, { method: 'POST' }), () => mockResponse({ action: 'post-payment', id })),
        reverse: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-payments/${id}/reverse`, { method: 'POST' }), () => mockResponse({ action: 'reverse-payment', id })),
        previewAllocation: (input: unknown) => withMockFallback(() => httpClient<ApiPreviewResponse<unknown, typeof paymentAllocationPreview>>('/api/purchase/preview-payment-allocation', { body: input, method: 'POST' }), () => mockPreviewResponse(input, paymentAllocationPreview)),
        allocate: (paymentId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-payments/${paymentId}/allocate`, { body: input, method: 'POST' }), () => mockResponse({ input, paymentId })),
    },
    advances: {
        list: () => mockCollectionResponse(purchaseAdvances),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-advances', { body: input, method: 'POST' }), () => mockResponse(input)),
        allocate: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-advances/${id}/allocate`, { body: input, method: 'POST' }), () => mockResponse({ id, input })),
    },
    returns: {
        list: (): Promise<ApiCollectionResponse<PurchaseReturn>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-returns');
                return { ...response, data: response.data.map(normalizeReturn) };
            },
            () => mockCollectionResponse(purchaseReturns),
        ),
        get: (id: string): Promise<ApiResponse<PurchaseReturn>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-returns/${id}`);
                return { ...response, data: normalizeReturn(response.data) };
            },
            () => mockResponse(getPurchaseReturnById(id)),
        ),
        createWithLines: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-returns/with-lines', { body: input, method: 'POST' }), () => mockResponse(input)),
        previewEffect: (input: unknown) => mockPreviewResponse(input, { financeEffect: financePostingPreview.calculated, inventoryEffect: inventoryEffects }),
    },
    refunds: {
        list: () => mockCollectionResponse(supplierRefunds),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-refunds', { body: input, method: 'POST' }), () => mockResponse(input)),
    },
    settings: {
        get: () => withMockFallback(() => httpClient<ApiResponse<typeof purchaseSettings>>('/api/purchase/settings'), () => mockResponse(purchaseSettings)),
        update: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/settings', { body: input, method: 'PUT' }), () => mockResponse(input)),
        initialize: () => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/purchase/settings/initialize', { method: 'POST' }), () => mockResponse(purchaseSettings)),
    },
    previews: {
        inventoryEffect: () => mockCollectionResponse(inventoryEffects),
        financePosting: () => mockResponse(financePostingPreview),
        document: () => mockResponse(documentPreview),
    },
};
