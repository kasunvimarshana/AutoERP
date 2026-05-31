import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    creditCheckPreview,
    customerAdvances,
    customerRefunds,
    documentPreview,
    financePostingPreview,
    gdns,
    getGdnById,
    getSalesInvoiceById,
    getSalesOrderById,
    getSalesPaymentById,
    getSalesQuotationById,
    getSalesReturnById,
    inventoryEffects,
    invoiceCalculationPreview,
    paymentAllocationPreview,
    salesActivity,
    salesDashboardMetrics,
    salesInvoices,
    salesOrders,
    salesPayments,
    salesQuotations,
    salesReturns,
    salesSettings,
    stockAvailabilityPreview,
} from '../mock/salesMock';
import type {
    GoodsDeliveryNote,
    SalesCalculationPreview,
    SalesDashboardMetric,
    SalesInvoice,
    SalesOrder,
    SalesPayment,
    SalesQuotation,
    SalesReturn,
} from '../types/sales.types';

type BackendRecord = Record<string, unknown>;

const SALES_API_MODE = import.meta.env.VITE_SALES_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return SALES_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419, 422]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (SALES_API_MODE === 'real') {
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

function normalizeOrder(raw: BackendRecord): SalesOrder {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        balance: asString(raw.balance, 'Backend calculated'),
        customer: asString(raw.customer_name ?? raw.customer_id, 'Backend customer'),
        expectedDate: asString(raw.expected_date ?? raw.expectedDate),
        grandTotal: asString(raw.grand_total ?? raw.grandTotal, 'Backend calculated'),
        id: asString(raw.id),
        lines: lines.map((line, index) => ({
            backendConvertedQuantity: asString(line.base_quantity ?? line.converted_quantity, 'Backend converted'),
            deliveredQuantity: asString(line.delivered_quantity, 'Backend tracked'),
            discountAmount: asString(line.discount_amount, 'Backend calculated'),
            id: asString(line.id, `line-${index}`),
            item: asString(line.item_label ?? line.item_name ?? line.item_code, 'Backend item'),
            lineTotal: asString(line.line_total, 'Backend calculated'),
            orderedQuantity: asString(line.quantity ?? line.ordered_quantity, 'Backend quantity'),
            remainingQuantity: asString(line.remaining_quantity, 'Backend calculated'),
            stockAvailability: asString(line.stock_availability, 'Backend checked'),
            taxAmount: asString(line.tax_amount, 'Backend calculated'),
            unitPrice: asString(line.unit_price, 'Backend resolved'),
            uom: asString(line.uom_label ?? line.uom_code ?? line.uom_symbol ?? line.uom_name, 'Backend UOM'),
        })),
        orderDate: asString(raw.order_date ?? raw.so_date),
        soNumber: asString(raw.so_number ?? raw.sales_order_number, `SO-${asString(raw.id)}`),
        status: asString(raw.status, 'draft') as SalesOrder['status'],
        updatedAt: asString(raw.updated_at),
        workflow: asString(raw.workflow, 'Backend workflow status'),
    };
}

function normalizeGdn(raw: BackendRecord): GoodsDeliveryNote {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        customer: asString(raw.customer_name ?? raw.customer_id, 'Backend customer'),
        deliveryDate: asString(raw.delivery_date ?? raw.gdn_date),
        gdnNumber: asString(raw.gdn_number, `GDN-${asString(raw.id)}`),
        id: asString(raw.id),
        inventoryStatus: asString(raw.inventory_status, 'Backend inventory state'),
        lines: lines.map((line, index) => ({
            backendBaseQuantity: asString(line.base_quantity, 'Backend converted'),
            deliveredQuantity: asString(line.delivered_qty ?? line.delivered_quantity, 'Backend quantity'),
            id: asString(line.id, `line-${index}`),
            item: asString(line.item_label ?? line.item_name ?? line.item_code, 'Backend item'),
            orderedQuantity: asString(line.ordered_quantity),
            pickedQuantity: asString(line.picked_qty, 'Backend quantity'),
            rejectedQuantity: asString(line.rejected_qty, 'Backend quantity'),
            sourceLine: asString(line.sales_order_line_id),
            uom: asString(line.uom_label ?? line.uom_code ?? line.uom_symbol ?? line.uom_name, 'Backend UOM'),
        })),
        sourceOrder: asString(raw.sales_order_number ?? raw.sales_order_id),
        status: asString(raw.status, 'draft') as GoodsDeliveryNote['status'],
        updatedAt: asString(raw.updated_at),
    };
}

function normalizeInvoice(raw: BackendRecord): SalesInvoice {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        balance: asString(raw.balance, 'Backend calculated'),
        customer: asString(raw.customer_name ?? raw.customer_id, 'Backend customer'),
        documentStatus: asString(raw.document_status, 'Backend document state'),
        dueDate: asString(raw.due_date),
        grandTotal: asString(raw.grand_total, 'Backend calculated'),
        id: asString(raw.id),
        invoiceDate: asString(raw.invoice_date ?? raw.document_date),
        invoiceNumber: asString(raw.invoice_number ?? raw.document_number, `SINV-${asString(raw.id)}`),
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
        status: asString(raw.status, 'draft') as SalesInvoice['status'],
        updatedAt: asString(raw.updated_at),
    };
}

function normalizePayment(raw: BackendRecord): SalesPayment {
    return {
        allocations: [],
        amount: asString(raw.amount, 'Backend amount'),
        customer: asString(raw.customer_name ?? raw.party_name ?? raw.customer_id, 'Backend customer'),
        id: asString(raw.id),
        method: asString(raw.payment_method, 'Backend method'),
        paymentDate: asString(raw.payment_date),
        paymentNumber: asString(raw.payment_number, `CREC-${asString(raw.id)}`),
        reference: asString(raw.reference_number),
        status: asString(raw.status, 'draft') as SalesPayment['status'],
        unallocatedAmount: asString(raw.unallocated_amount, 'Backend calculated'),
    };
}

function normalizeReturn(raw: BackendRecord): SalesReturn {
    return {
        customer: asString(raw.customer_name ?? raw.customer_id, 'Backend customer'),
        id: asString(raw.id),
        lines: [],
        returnNumber: asString(raw.return_number, `SRET-${asString(raw.id)}`),
        returnTotal: asString(raw.grand_total, 'Backend calculated'),
        sourceReference: asString(raw.source_reference),
        status: asString(raw.status, 'draft') as SalesReturn['status'],
        updatedAt: asString(raw.updated_at),
    };
}

export const salesApi = {
    dashboard: {
        summary: (): Promise<ApiCollectionResponse<SalesDashboardMetric>> => mockCollectionResponse(salesDashboardMetrics),
    },
    quotations: {
        list: (): Promise<ApiCollectionResponse<SalesQuotation>> => mockCollectionResponse(salesQuotations),
        get: (id: string): Promise<ApiResponse<SalesQuotation>> => mockResponse(getSalesQuotationById(id)),
        create: (input: unknown) => mockResponse(input),
        convertToOrder: (id: string) => mockResponse({ action: 'convert-quotation-to-order', id }),
    },
    orders: {
        list: (): Promise<ApiCollectionResponse<SalesOrder>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-orders');
                return { ...response, data: response.data.map(normalizeOrder) };
            },
            () => mockCollectionResponse(salesOrders),
        ),
        get: (id: string): Promise<ApiResponse<SalesOrder>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-orders/${id}`);
                return { ...response, data: normalizeOrder(response.data) };
            },
            () => mockResponse(getSalesOrderById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-orders', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-orders/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        createWithLines: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-orders/with-lines', { body: input, method: 'POST' }), () => mockResponse(input)),
        updateWithLines: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-orders/${id}/with-lines`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        transition: (id: string, action: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/workflows/sales_order/${id}/transition`, { body: { action }, method: 'POST' }), () => mockResponse({ action, id })),
        history: (id: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>(`/api/sales/workflows/sales_order/${id}/history`), () => mockCollectionResponse(salesActivity)),
    },
    deliveries: {
        list: (): Promise<ApiCollectionResponse<GoodsDeliveryNote>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/gdn-headers');
                return { ...response, data: response.data.map(normalizeGdn) };
            },
            () => mockCollectionResponse(gdns),
        ),
        get: (id: string): Promise<ApiResponse<GoodsDeliveryNote>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/sales/gdn-headers/${id}`);
                return { ...response, data: normalizeGdn(response.data) };
            },
            () => mockResponse(getGdnById(id)),
        ),
        createDirect: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/gdn-headers/with-lines', { body: input, method: 'POST' }), () => mockResponse({ input, mode: 'direct-gdn' })),
        createFromOrder: (salesOrderId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/gdn-headers/with-lines', { body: { ...(input as object), sales_order_id: salesOrderId }, method: 'POST' }), () => mockResponse({ input, salesOrderId })),
        updateWithLines: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/gdn-headers/${id}/with-lines`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        postInventory: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/workflows/gdn_header/${id}/inventory/post`, { method: 'POST' }), () => mockResponse({ action: 'post-inventory', id })),
        previewStockIssue: (input: unknown) => mockPreviewResponse(input, stockAvailabilityPreview.calculated, [], stockAvailabilityPreview.warnings),
        history: (id: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>(`/api/sales/workflows/gdn_header/${id}/history`), () => mockCollectionResponse(salesActivity)),
    },
    invoices: {
        list: (): Promise<ApiCollectionResponse<SalesInvoice>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-invoices');
                return { ...response, data: response.data.map(normalizeInvoice) };
            },
            () => mockCollectionResponse(salesInvoices),
        ),
        get: (id: string): Promise<ApiResponse<SalesInvoice>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-invoices/${id}`);
                return { ...response, data: normalizeInvoice(response.data) };
            },
            () => mockResponse(getSalesInvoiceById(id)),
        ),
        preview: (input: unknown): Promise<ApiPreviewResponse<unknown, SalesCalculationPreview['calculated']>> => withMockFallback(
            () => httpClient<ApiPreviewResponse<unknown, SalesCalculationPreview['calculated']>>('/api/sales/calculate-invoice', { body: input, method: 'POST' }),
            () => mockPreviewResponse(input, invoiceCalculationPreview.calculated, invoiceCalculationPreview.breakdown, invoiceCalculationPreview.warnings),
        ),
        createDirect: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-invoices', { body: input, method: 'POST' }), () => mockResponse(input)),
        createFromOrder: (salesOrderId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-invoices/from-so', { body: { ...(input as object), sales_order_id: salesOrderId }, method: 'POST' }), () => mockResponse({ input, salesOrderId })),
        createFromDelivery: (gdnHeaderId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-invoices/from-gdn', { body: { ...(input as object), gdn_header_id: gdnHeaderId }, method: 'POST' }), () => mockResponse({ gdnHeaderId, input })),
        createFromMultipleDeliveries: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-invoices/from-multiple-gdns', { body: input, method: 'POST' }), () => mockResponse(input)),
        post: (invoiceId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-invoices/${invoiceId}/post`, { method: 'POST' }), () => mockResponse({ action: 'post-requested', invoiceId })),
        cancel: (invoiceId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-invoices/${invoiceId}/cancel`, { method: 'POST' }), () => mockResponse({ action: 'cancel-requested', invoiceId })),
        reverse: (invoiceId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-invoices/${invoiceId}/reverse`, { method: 'POST' }), () => mockResponse({ action: 'reverse-requested', invoiceId })),
        generateDocument: (invoiceId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/workflows/sales_invoice/${invoiceId}/document`, { method: 'POST' }), () => mockResponse(documentPreview)),
    },
    payments: {
        list: (): Promise<ApiCollectionResponse<SalesPayment>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-payments');
                return { ...response, data: response.data.map(normalizePayment) };
            },
            () => mockCollectionResponse(salesPayments),
        ),
        get: (id: string): Promise<ApiResponse<SalesPayment>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-payments/${id}`);
                return { ...response, data: normalizePayment(response.data) };
            },
            () => mockResponse(getSalesPaymentById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-payments', { body: input, method: 'POST' }), () => mockResponse(input)),
        post: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-payments/${id}/post`, { method: 'POST' }), () => mockResponse({ action: 'post-payment', id })),
        reverse: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-payments/${id}/reverse`, { method: 'POST' }), () => mockResponse({ action: 'reverse-payment', id })),
        previewAllocation: (input: unknown) => withMockFallback(() => httpClient<ApiPreviewResponse<unknown, typeof paymentAllocationPreview>>('/api/sales/preview-payment-allocation', { body: input, method: 'POST' }), () => mockPreviewResponse(input, paymentAllocationPreview)),
        allocate: (paymentId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-payments/${paymentId}/allocate`, { body: input, method: 'POST' }), () => mockResponse({ input, paymentId })),
    },
    advances: {
        list: () => mockCollectionResponse(customerAdvances),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-advances', { body: input, method: 'POST' }), () => mockResponse(input)),
        allocate: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/sales/sales-advances/${id}/allocate`, { body: input, method: 'POST' }), () => mockResponse({ id, input })),
    },
    returns: {
        list: (): Promise<ApiCollectionResponse<SalesReturn>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-returns');
                return { ...response, data: response.data.map(normalizeReturn) };
            },
            () => mockCollectionResponse(salesReturns),
        ),
        get: (id: string): Promise<ApiResponse<SalesReturn>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-returns/${id}`);
                return { ...response, data: normalizeReturn(response.data) };
            },
            () => mockResponse(getSalesReturnById(id)),
        ),
        createWithLines: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-returns/with-lines', { body: input, method: 'POST' }), () => mockResponse(input)),
        previewEffect: (input: unknown) => mockPreviewResponse(input, { financeEffect: financePostingPreview.calculated, inventoryEffect: inventoryEffects }),
    },
    refunds: {
        list: () => mockCollectionResponse(customerRefunds),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/sales-refunds', { body: input, method: 'POST' }), () => mockResponse(input)),
    },
    settings: {
        get: () => withMockFallback(() => httpClient<ApiResponse<typeof salesSettings>>('/api/sales/settings'), () => mockResponse(salesSettings)),
        update: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/settings', { body: input, method: 'PUT' }), () => mockResponse(input)),
        initialize: () => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/sales/settings/initialize', { method: 'POST' }), () => mockResponse(salesSettings)),
    },
    previews: {
        creditCheck: () => mockResponse(creditCheckPreview),
        inventoryEffect: () => mockCollectionResponse(inventoryEffects),
        financePosting: () => mockResponse(financePostingPreview),
        stockAvailability: (input: unknown) => withMockFallback(() => httpClient<ApiPreviewResponse<unknown, typeof stockAvailabilityPreview.calculated>>('/api/sales/stock-availability', { query: input as Record<string, string | number | boolean>, method: 'GET' }), () => mockPreviewResponse(input, stockAvailabilityPreview.calculated, [], stockAvailabilityPreview.warnings)),
        document: () => mockResponse(documentPreview),
    },
};
