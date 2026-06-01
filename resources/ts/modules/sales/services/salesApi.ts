import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredAuthSession, getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import { customerApi } from '../../customer/services/customerApi';
import { documentApi } from '../../document/services/documentApi';
import { inventoryApi } from '../../inventory/services/inventoryApi';
import { itemApi } from '../../item/services/itemApi';
import type {
    CustomerAdvance,
    CustomerRefund,
    GdnFormInput,
    GoodsDeliveryNote,
    GoodsDeliveryNoteLine,
    SalesAuditEntry,
    SalesCalculationPreview,
    SalesDashboardMetric,
    SalesFinancePostingPreview,
    SalesInventoryEffect,
    SalesInvoice,
    SalesInvoiceFormInput,
    SalesInvoiceLine,
    SalesLedgerNote,
    SalesLineFormInput,
    SalesListQuery,
    SalesLookupOption,
    SalesOrder,
    SalesOrderFormInput,
    SalesOrderLine,
    SalesPayment,
    SalesPaymentAllocation,
    SalesPaymentFormInput,
    SalesQuotation,
    SalesReturn,
    SalesReturnFormInput,
    SalesReturnLine,
    SalesSettings,
} from '../types/sales.types';

type BackendRecord = Record<string, unknown>;
type LookupContext = {
    customers: Map<string, SalesLookupOption>;
    items: Map<string, SalesLookupOption>;
    uoms: Map<string, SalesLookupOption>;
    warehouses: Map<string, SalesLookupOption>;
};

let lookupCache: Promise<LookupContext> | null = null;

function asRecord(value: unknown): BackendRecord {
    return value !== null && typeof value === 'object' && !Array.isArray(value) ? value as BackendRecord : {};
}

function asString(value: unknown, fallback = ''): string {
    return value === null || value === undefined || value === '' ? fallback : String(value);
}

function asOptionalNumber(value: string | undefined): number | undefined {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function money(value: unknown, fallback = '0.0000'): string {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed.toFixed(4) : fallback;
}

function contextQuery(query: Record<string, string | number | boolean | undefined> = {}) {
    return {
        organization_unit_id: asOptionalNumber(getStoredOrganizationUnitId() ?? undefined),
        per_page: 100,
        tenant_id: asOptionalNumber(getStoredTenantId() ?? undefined),
        ...query,
    };
}

function contextPayload(input: BackendRecord = {}): BackendRecord {
    return {
        ...input,
        actor_id: input.actor_id ?? asOptionalNumber(getStoredAuthSession().user?.id),
        organization_unit_id: input.organization_unit_id ?? asOptionalNumber(getStoredOrganizationUnitId() ?? undefined),
        tenant_id: input.tenant_id ?? asOptionalNumber(getStoredTenantId() ?? undefined),
    };
}

function collection<T>(response: ApiCollectionResponse<BackendRecord>, mapper: (row: BackendRecord) => T): ApiCollectionResponse<T> {
    return { ...response, data: response.data.map(mapper) };
}

function normalizeLookup(id: unknown, code: unknown, name: unknown, fallback: string): SalesLookupOption {
    const normalizedId = asString(id);
    const normalizedCode = asString(code);
    const normalizedName = asString(name, fallback);

    return {
        id: normalizedId,
        label: normalizedCode && normalizedName ? `${normalizedCode} - ${normalizedName}` : normalizedName || normalizedCode || fallback,
        secondary: normalizedCode,
    };
}

function lookupLabel(map: Map<string, SalesLookupOption>, id: unknown, fallback: string): string {
    return map.get(asString(id))?.label ?? fallback;
}

function directLabel(raw: BackendRecord, keys: string[], fallback = ''): string {
    for (const key of keys) {
        const value = asString(raw[key]);
        if (value) return value;
    }

    return fallback;
}

async function lookupContext(): Promise<LookupContext> {
    if (!lookupCache) {
        lookupCache = Promise.all([
            customerApi.listCustomers({ perPage: 200 }),
            itemApi.listItems({ perPage: 200 }),
            itemApi.listUoms(),
            inventoryApi.listWarehouses(),
        ]).then(([customers, items, uoms, warehouses]) => ({
            customers: new Map(customers.data.map((customer) => [customer.id, normalizeLookup(customer.id, customer.code, customer.name, 'Customer')])),
            items: new Map(items.data.map((item) => [item.id, normalizeLookup(item.id, item.code, item.name, 'Item')])),
            uoms: new Map(uoms.data.map((uom) => [uom.id, normalizeLookup(uom.id, uom.symbol, uom.name, 'UOM')])),
            warehouses: new Map(warehouses.data.map((warehouse) => [warehouse.id, normalizeLookup(warehouse.id, warehouse.secondary, warehouse.label, 'Warehouse')])),
        }));
    }

    return lookupCache;
}

export function clearSalesLookupCache(): void {
    lookupCache = null;
}

function normalizeOrderLine(raw: BackendRecord, lookups: LookupContext, index = 0): SalesOrderLine {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);
    const ordered = Number(raw.ordered_qty ?? raw.quantity ?? 0);
    const delivered = Number(raw.delivered_qty ?? 0);

    return {
        backendConvertedQuantity: money(raw.ordered_base_qty ?? raw.base_quantity ?? raw.converted_quantity ?? raw.ordered_qty),
        deliveredQuantity: money(raw.delivered_qty),
        discountAmount: money(raw.discount_amount),
        id: asString(raw.id, `line-${index}`),
        item: directLabel(raw, ['item_label', 'item_name', 'item_code']) || lookupLabel(lookups.items, itemId, 'Item not loaded'),
        itemId,
        lineTotal: money(raw.line_total ?? raw.line_total_with_tax),
        orderedQuantity: money(raw.ordered_qty ?? raw.quantity),
        remainingQuantity: money(raw.remaining_quantity ?? Math.max(0, ordered - delivered)),
        stockAvailability: asString(raw.stock_availability ?? raw.reservation_status, 'Backend checked'),
        taxAmount: money(raw.tax_amount),
        unitPrice: money(raw.unit_price),
        uom: directLabel(raw, ['uom_label', 'uom_symbol', 'uom_code', 'uom_name']) || lookupLabel(lookups.uoms, uomId, 'UOM not loaded'),
        uomId,
    };
}

function normalizeOrder(raw: BackendRecord, lookups: LookupContext, lines: BackendRecord[] = []): SalesOrder {
    const customerId = asString(raw.customer_id);
    const warehouseId = asString(raw.warehouse_id);

    return {
        balance: money(raw.balance),
        creditNoteTotal: money(raw.credit_note_total),
        customer: directLabel(raw, ['customer_label', 'customer_name', 'customer_code']) || lookupLabel(lookups.customers, customerId, 'Customer not loaded'),
        customerId,
        debitNoteTotal: money(raw.debit_note_total),
        expectedDate: asString(raw.requested_delivery_date ?? raw.expected_date),
        grandTotal: money(raw.grand_total),
        id: asString(raw.id),
        lines: lines.map((line, index) => normalizeOrderLine(line, lookups, index)),
        orderDate: asString(raw.order_date ?? raw.so_date),
        soNumber: asString(raw.so_number ?? raw.sales_order_number, `SO-${asString(raw.id)}`),
        status: asString(raw.status, 'draft') as SalesOrder['status'],
        updatedAt: asString(raw.updated_at),
        warehouse: directLabel(raw, ['warehouse_label', 'warehouse_name', 'warehouse_code']) || lookupLabel(lookups.warehouses, warehouseId, 'Warehouse not loaded'),
        warehouseId,
        workflow: asString(raw.workflow ?? raw.status, 'draft'),
    };
}

function normalizeGdnLine(raw: BackendRecord, lookups: LookupContext, index = 0): GoodsDeliveryNoteLine {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);

    return {
        backendBaseQuantity: money(raw.delivered_base_qty ?? raw.base_quantity ?? raw.delivered_qty),
        deliveredQuantity: money(raw.delivered_qty ?? raw.delivered_quantity),
        id: asString(raw.id, `line-${index}`),
        item: directLabel(raw, ['item_label', 'item_name', 'item_code']) || lookupLabel(lookups.items, itemId, 'Item not loaded'),
        itemId,
        orderedQuantity: money(raw.expected_qty ?? raw.ordered_quantity ?? raw.ordered_qty),
        pickedQuantity: money(raw.picked_qty),
        rejectedQuantity: money(raw.rejected_qty),
        sourceLine: asString(raw.sales_order_line_id),
        uom: directLabel(raw, ['uom_label', 'uom_symbol', 'uom_code', 'uom_name']) || lookupLabel(lookups.uoms, uomId, 'UOM not loaded'),
        uomId,
    };
}

function normalizeGdn(raw: BackendRecord, lookups: LookupContext, lines: BackendRecord[] = []): GoodsDeliveryNote {
    const customerId = asString(raw.customer_id);
    const warehouseId = asString(raw.warehouse_id);

    return {
        creditNoteTotal: money(raw.credit_note_total),
        customer: directLabel(raw, ['customer_label', 'customer_name', 'customer_code']) || lookupLabel(lookups.customers, customerId, 'Customer not loaded'),
        customerId,
        debitNoteTotal: money(raw.debit_note_total),
        deliveryDate: asString(raw.delivery_date ?? raw.gdn_date),
        gdnNumber: asString(raw.gdn_number, `GDN-${asString(raw.id)}`),
        id: asString(raw.id),
        inventoryStatus: asString(raw.inventory_status ?? raw.putaway_status ?? raw.status, 'draft'),
        lines: lines.map((line, index) => normalizeGdnLine(line, lookups, index)),
        sourceOrder: asString(raw.sales_order_number ?? raw.sales_order_id),
        status: asString(raw.status, 'draft') as GoodsDeliveryNote['status'],
        updatedAt: asString(raw.updated_at),
        warehouse: directLabel(raw, ['warehouse_label', 'warehouse_name', 'warehouse_code']) || lookupLabel(lookups.warehouses, warehouseId, 'Warehouse not loaded'),
        warehouseId,
    };
}

function normalizeInvoiceLine(raw: BackendRecord, lookups: LookupContext, index = 0): SalesInvoiceLine {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);

    return {
        discountAmount: money(raw.discount_amount),
        id: asString(raw.id ?? raw.link_id, `line-${index}`),
        invoiceQuantity: money(raw.quantity ?? raw.linked_quantity),
        item: directLabel(raw, ['item_label', 'item_name', 'item_code']) || lookupLabel(lookups.items, itemId, 'Item not loaded'),
        itemId,
        lineTotal: money(raw.line_total ?? raw.linked_amount),
        sourceLine: asString(raw.source_line_id),
        taxAmount: money(raw.tax_amount),
        unitPrice: money(raw.unit_price),
        uom: directLabel(raw, ['uom_label', 'uom_symbol', 'uom_code', 'uom_name']) || lookupLabel(lookups.uoms, uomId, 'UOM not loaded'),
        uomId,
    };
}

function normalizeInvoice(raw: BackendRecord, lookups: LookupContext, lines: BackendRecord[] = []): SalesInvoice {
    const customerId = asString(raw.customer_id ?? raw.party_id);

    return {
        balance: money(raw.balance ?? raw.outstanding_amount),
        customer: directLabel(raw, ['customer_label', 'customer_name', 'party_label']) || lookupLabel(lookups.customers, customerId, 'Customer not loaded'),
        customerId,
        documentStatus: asString(raw.document_status ?? raw.status, 'draft'),
        dueDate: asString(raw.due_date),
        grandTotal: money(raw.grand_total ?? raw.linked_amount ?? raw.total_amount),
        id: asString(raw.id ?? raw.document_id),
        invoiceDate: asString(raw.invoice_date ?? raw.document_date ?? raw.created_at).slice(0, 10),
        invoiceNumber: asString(raw.invoice_number ?? raw.document_number ?? raw.source_reference, `SINV-${asString(raw.id ?? raw.document_id)}`),
        lines: lines.map((line, index) => normalizeInvoiceLine(line, lookups, index)),
        paidAmount: money(raw.paid_amount ?? raw.allocated_amount),
        sourceReference: asString(raw.source_reference ?? raw.source_type),
        status: asString(raw.status, 'draft') as SalesInvoice['status'],
        updatedAt: asString(raw.updated_at),
    };
}

function normalizePaymentAllocation(raw: BackendRecord, index = 0): SalesPaymentAllocation {
    return {
        allocatedAmount: money(raw.allocated_amount),
        documentBalanceAfter: money(raw.remaining_after_allocation ?? raw.document_balance_after),
        id: asString(raw.id, `allocation-${index}`),
        sourceDocument: asString(raw.source_reference ?? raw.document_number ?? raw.document_id, 'Linked document'),
        status: asString(raw.status, 'active'),
    };
}

function normalizePayment(raw: BackendRecord, lookups: LookupContext, allocations: BackendRecord[] = []): SalesPayment {
    const customerId = asString(raw.party_id ?? raw.customer_id);

    return {
        allocations: allocations.map(normalizePaymentAllocation),
        amount: money(raw.amount),
        customer: directLabel(raw, ['customer_label', 'customer_name', 'party_label']) || lookupLabel(lookups.customers, customerId, 'Customer not loaded'),
        customerId,
        id: asString(raw.id),
        method: asString(raw.payment_method_name ?? raw.method ?? raw.payment_method ?? raw.payment_method_id, 'Payment method'),
        paymentDate: asString(raw.payment_date ?? raw.transaction_date),
        paymentNumber: asString(raw.payment_number ?? raw.reference_number, `CREC-${asString(raw.id)}`),
        reference: asString(raw.reference_number ?? raw.source_reference),
        status: asString(raw.status, 'draft') as SalesPayment['status'],
        unallocatedAmount: money(raw.unallocated_amount ?? raw.remaining_amount),
    };
}

function normalizeReturnLine(raw: BackendRecord, lookups: LookupContext, index = 0): SalesReturnLine {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);

    return {
        backendReturnableQuantity: money(raw.available_return_qty ?? raw.returnable_qty),
        id: asString(raw.id, `line-${index}`),
        item: directLabel(raw, ['item_label', 'item_name', 'item_code']) || lookupLabel(lookups.items, itemId, 'Item not loaded'),
        itemId,
        returnQuantity: money(raw.return_qty ?? raw.quantity),
        sourceLine: asString(raw.original_gdn_line_id ?? raw.source_line_id),
        uom: directLabel(raw, ['uom_label', 'uom_symbol', 'uom_code', 'uom_name']) || lookupLabel(lookups.uoms, uomId, 'UOM not loaded'),
        uomId,
    };
}

function normalizeReturn(raw: BackendRecord, lookups: LookupContext, lines: BackendRecord[] = []): SalesReturn {
    const customerId = asString(raw.customer_id);

    return {
        creditNoteTotal: money(raw.credit_note_total),
        customer: directLabel(raw, ['customer_label', 'customer_name', 'customer_code']) || lookupLabel(lookups.customers, customerId, 'Customer not loaded'),
        customerId,
        debitNoteTotal: money(raw.debit_note_total),
        id: asString(raw.id),
        lines: lines.map((line, index) => normalizeReturnLine(line, lookups, index)),
        returnNumber: asString(raw.return_number, `SRET-${asString(raw.id)}`),
        returnTotal: money(raw.grand_total),
        sourceReference: asString(raw.source_reference ?? raw.original_document_id ?? raw.original_gdn_id ?? raw.original_sales_order_id),
        status: asString(raw.status, 'draft') as SalesReturn['status'],
        updatedAt: asString(raw.updated_at),
    };
}

function normalizeSettings(raw: BackendRecord): SalesSettings {
    return {
        allowDeliveryWithoutOrder: raw.allow_delivery_without_order !== false,
        allowDirectInvoice: raw.allow_direct_sales_document !== false,
        allowInvoiceWithoutDelivery: raw.require_gdn_before_invoice !== true,
        allowNegativeStock: raw.allow_negative_stock === true,
        creditCheckBehavior: asString(raw.credit_check_behavior ?? 'Backend workflow'),
        defaultCogsAccount: asString(raw.default_cogs_account_label ?? raw.default_cogs_account_id, 'Not configured'),
        defaultIncomeAccount: asString(raw.default_income_account_label ?? raw.default_income_account_id, 'Not configured'),
        defaultInventoryAccount: asString(raw.default_inventory_account_label ?? raw.default_inventory_account_id, 'Not configured'),
        defaultPaymentTerm: asString(raw.default_payment_term_label ?? raw.default_payment_term_id, 'Not configured'),
        defaultReceivableAccount: asString(raw.default_receivable_account_label ?? raw.default_receivable_account_id, 'Not configured'),
        defaultTaxGroup: asString(raw.default_tax_group_label ?? raw.default_tax_group_id, 'Not configured'),
        defaultWarehouse: asString(raw.default_warehouse_label ?? raw.default_warehouse_id, 'Not configured'),
        deliverySequence: asString(raw.gdn_sequence_label ?? raw.gdn_sequence_id, 'Backend sequence'),
        id: asString(raw.id),
        invoiceDocumentDefinition: asString(raw.sales_invoice_document_definition_label ?? raw.sales_invoice_document_definition_id, 'Document module'),
        invoiceMatchingRule: asString(raw.invoice_matching_rule ?? (raw.require_gdn_before_invoice ? 'Delivery required' : 'Flexible')),
        invoiceSequence: asString(raw.invoice_sequence_label ?? raw.sales_invoice_sequence_id, 'Backend sequence'),
        quotationSequence: asString(raw.quotation_sequence_label ?? raw.quotation_sequence_id, 'Backend gap'),
        returnSequence: asString(raw.return_sequence_label ?? raw.sales_return_sequence_id, 'Backend sequence'),
        salesOrderSequence: asString(raw.so_sequence_label ?? raw.sales_order_sequence_id, 'Backend sequence'),
        stockDeductionTiming: asString(raw.stock_deduction_timing ?? 'Backend workflow'),
    };
}

function linePayload(line: SalesLineFormInput, quantityField: 'delivered_qty' | 'ordered_qty' | 'return_qty'): BackendRecord {
    return {
        discount_type: line.discountType || null,
        discount_value: line.discountValue ? Number(line.discountValue) : 0,
        item_id: Number(line.itemId),
        [quantityField]: Number(line.quantity),
        unit_price: Number(line.unitPrice || 0),
        uom_id: Number(line.uomId),
    };
}

function orderPayload(input: SalesOrderFormInput): BackendRecord {
    return contextPayload({
        customer_id: Number(input.customerId),
        lines: input.lines.map((line) => linePayload(line, 'ordered_qty')),
        notes: input.notes || null,
        order_date: input.orderDate,
        requested_delivery_date: input.expectedDate || null,
        so_number: input.soNumber,
        status: input.status || 'draft',
        warehouse_id: Number(input.warehouseId),
    });
}

function gdnPayload(input: GdnFormInput): BackendRecord {
    return contextPayload({
        customer_id: Number(input.customerId),
        delivery_date: input.deliveryDate,
        gdn_number: input.gdnNumber,
        lines: input.lines.map((line) => linePayload(line, 'delivered_qty')),
        notes: input.notes || null,
        sales_order_id: asOptionalNumber(input.salesOrderId),
        status: input.status || 'draft',
        warehouse_id: Number(input.warehouseId),
    });
}

function returnPayload(input: SalesReturnFormInput): BackendRecord {
    return contextPayload({
        customer_id: Number(input.customerId),
        lines: input.lines.map((line) => linePayload(line, 'return_qty')),
        notes: input.notes || null,
        original_document_id: input.sourceType === 'document' ? asOptionalNumber(input.sourceId) : undefined,
        original_gdn_id: input.sourceType === 'gdn_header' ? asOptionalNumber(input.sourceId) : undefined,
        original_sales_order_id: input.sourceType === 'sales_order' ? asOptionalNumber(input.sourceId) : undefined,
        return_date: input.returnDate,
        return_number: input.returnNumber,
        return_reason: input.returnReason || null,
        status: input.status || 'draft',
    });
}

async function orderLines(orderId: string): Promise<BackendRecord[]> {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-order-lines', { query: contextQuery({ sales_order_id: Number(orderId) }) });
    return response.data;
}

async function gdnLines(gdnId: string): Promise<BackendRecord[]> {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/gdn-lines', { query: contextQuery({ gdn_header_id: Number(gdnId) }) });
    return response.data;
}

async function returnLines(returnId: string): Promise<BackendRecord[]> {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-return-lines', { query: contextQuery({ sales_return_id: Number(returnId) }) });
    return response.data;
}

function ledgerNotesFromSource(source: SalesOrder | GoodsDeliveryNote | SalesReturn, sourceType: SalesLedgerNote['sourceType'], sourceReference: string): SalesLedgerNote[] {
    const debitAmount = Number(source.debitNoteTotal);
    const creditAmount = Number(source.creditNoteTotal);
    const notes: SalesLedgerNote[] = [];

    if (Number.isFinite(debitAmount) && debitAmount > 0) {
        notes.push({ amount: money(debitAmount), customer: source.customer, id: `${sourceType}-${source.id}-debit`, noteType: 'debit', sourceId: source.id, sourceReference, sourceType, status: source.status, updatedAt: source.updatedAt });
    }

    if (Number.isFinite(creditAmount) && creditAmount > 0) {
        notes.push({ amount: money(creditAmount), customer: source.customer, id: `${sourceType}-${source.id}-credit`, noteType: 'credit', sourceId: source.id, sourceReference, sourceType, status: source.status, updatedAt: source.updatedAt });
    }

    return notes;
}

export const salesApi = {
    dashboard: {
        summary: async (): Promise<ApiCollectionResponse<SalesDashboardMetric>> => {
            const [orders, deliveries, invoices, returns] = await Promise.all([
                salesApi.orders.list({ perPage: 20 }),
                salesApi.deliveries.list({ perPage: 20 }),
                salesApi.invoices.list({ perPage: 20 }),
                salesApi.returns.list({ perPage: 20 }),
            ]);

            return {
                data: [
                    { label: 'Sales Orders', status: 'active', value: String(orders.data.length) },
                    { label: 'Deliveries', status: 'active', value: String(deliveries.data.length) },
                    { label: 'Customer Invoices', status: 'active', value: String(invoices.data.length) },
                    { label: 'Returns', status: 'active', value: String(returns.data.length) },
                ],
            };
        },
    },
    lookups: {
        customers: async () => ({ data: Array.from((await lookupContext()).customers.values()) }),
        items: async () => ({ data: Array.from((await lookupContext()).items.values()) }),
        itemUoms: async (itemId: string) => {
            const response = await itemApi.getItemUnits(itemId);
            return {
                data: response.data.map((unit) => ({ id: unit.id, label: unit.unit, secondary: unit.purpose })).filter((unit) => unit.id && !unit.id.startsWith('base-uom')),
            };
        },
        uoms: async () => ({ data: Array.from((await lookupContext()).uoms.values()) }),
        warehouses: async () => ({ data: Array.from((await lookupContext()).warehouses.values()) }),
    },
    quotations: {
        list: async (): Promise<ApiCollectionResponse<SalesQuotation>> => ({ data: [] }),
        get: async (id: string): Promise<ApiResponse<SalesQuotation>> => {
            throw new Error(`Quotation ${id} is not available because this repository has no Sales quotation backend schema or API.`);
        },
        create: async () => {
            throw new Error('Quotation creation is unavailable because this repository has no Sales quotation backend schema or API.');
        },
        convertToOrder: async () => {
            throw new Error('Quotation conversion is unavailable because this repository has no Sales quotation backend schema or API.');
        },
    },
    orders: {
        list: async (query: SalesListQuery = {}): Promise<ApiCollectionResponse<SalesOrder>> => {
            const [response, lookups] = await Promise.all([
                httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-orders', { query: contextQuery({ page: query.page, per_page: query.perPage ?? 50, so_number: query.search, status: query.status }) }),
                lookupContext(),
            ]);
            return collection(response, (row) => normalizeOrder(row, lookups));
        },
        get: async (id: string): Promise<ApiResponse<SalesOrder>> => {
            const [response, lines, lookups] = await Promise.all([
                httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-orders/${id}`),
                orderLines(id),
                lookupContext(),
            ]);
            return { ...response, data: normalizeOrder(response.data, lookups, lines) };
        },
        createWithLines: async (input: SalesOrderFormInput) => {
            clearSalesLookupCache();
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/sales/sales-orders/with-lines', { body: orderPayload(input), method: 'POST' });
            return { ...response, data: normalizeOrder(response.data, await lookupContext()) };
        },
        updateWithLines: async (id: string, input: SalesOrderFormInput) => {
            clearSalesLookupCache();
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-orders/${id}/with-lines`, { body: orderPayload(input), method: 'PUT' });
            return { ...response, data: normalizeOrder(response.data, await lookupContext()) };
        },
        history: async (id: string): Promise<ApiCollectionResponse<SalesAuditEntry>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/sales/workflows/sales_order/${id}/history`, { query: contextQuery() });
            return collection(response, (row) => ({ actor: asString(row.actor_name ?? row.actor_id, 'System'), description: asString(row.description ?? row.status ?? row.transition, 'Sales order activity'), id: asString(row.id), time: asString(row.created_at ?? row.updated_at), type: asString(row.type ?? row.status, 'workflow') }));
        },
        transition: (id: string, action: string) => httpClient<ApiResponse<unknown>>(`/api/sales/workflows/sales_order/${id}/transition`, { body: contextPayload({ action }), method: 'POST' }),
    },
    deliveries: {
        list: async (query: SalesListQuery = {}): Promise<ApiCollectionResponse<GoodsDeliveryNote>> => {
            const [response, lookups] = await Promise.all([
                httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/gdn-headers', { query: contextQuery({ gdn_number: query.search, page: query.page, per_page: query.perPage ?? 50, status: query.status }) }),
                lookupContext(),
            ]);
            return collection(response, (row) => normalizeGdn(row, lookups));
        },
        get: async (id: string): Promise<ApiResponse<GoodsDeliveryNote>> => {
            const [response, lines, lookups] = await Promise.all([
                httpClient<ApiResponse<BackendRecord>>(`/api/sales/gdn-headers/${id}`),
                gdnLines(id),
                lookupContext(),
            ]);
            return { ...response, data: normalizeGdn(response.data, lookups, lines) };
        },
        createDirect: async (input: GdnFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/sales/gdn-headers/with-lines', { body: gdnPayload(input), method: 'POST' });
            return { ...response, data: normalizeGdn(response.data, await lookupContext()) };
        },
        updateWithLines: async (id: string, input: GdnFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/sales/gdn-headers/${id}/with-lines`, { body: gdnPayload(input), method: 'PUT' });
            return { ...response, data: normalizeGdn(response.data, await lookupContext()) };
        },
        history: async (id: string): Promise<ApiCollectionResponse<SalesAuditEntry>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/sales/workflows/gdn_header/${id}/history`, { query: contextQuery() });
            return collection(response, (row) => ({ actor: asString(row.actor_name ?? row.actor_id, 'System'), description: asString(row.description ?? row.status ?? row.transition, 'Delivery activity'), id: asString(row.id), time: asString(row.created_at ?? row.updated_at), type: asString(row.type ?? row.status, 'workflow') }));
        },
        postInventory: (id: string) => httpClient<ApiResponse<unknown>>(`/api/sales/workflows/gdn_header/${id}/inventory/post`, { body: contextPayload(), method: 'POST' }),
        transition: (id: string, action: string) => httpClient<ApiResponse<unknown>>(`/api/sales/workflows/gdn_header/${id}/transition`, { body: contextPayload({ action }), method: 'POST' }),
    },
    invoices: {
        list: async (_query: SalesListQuery = {}): Promise<ApiCollectionResponse<SalesInvoice>> => {
            const [response, lookups] = await Promise.all([documentApi.listDocuments(), lookupContext()]);
            const salesDocuments = response.data.filter((document) => (
                document.sourceModule === 'sales'
                || ['sales_invoice', 'customer_invoice'].includes(document.typeCode)
                || ['sales_order', 'gdn_header', 'sales_return'].includes(document.sourceType)
            ));

            return {
                data: salesDocuments.map((document) => normalizeInvoice({
                    document_date: document.documentDate,
                    document_id: document.id,
                    document_number: document.documentNumber,
                    due_date: document.dueDate,
                    grand_total: document.grandTotal,
                    source_reference: document.sourceReference,
                    source_type: document.sourceType,
                    status: document.status,
                    updated_at: document.createdAt,
                }, lookups)),
                meta: response.meta,
            };
        },
        get: async (id: string): Promise<ApiResponse<SalesInvoice>> => {
            const lookups = await lookupContext();
            const [document, lines] = await Promise.all([documentApi.getDocument(id), documentApi.listDocumentLines(id)]);
            return {
                data: normalizeInvoice({
                    document_date: document.data.documentDate,
                    document_id: document.data.id,
                    document_number: document.data.documentNumber,
                    due_date: document.data.dueDate,
                    grand_total: document.data.grandTotal,
                    source_reference: document.data.sourceReference,
                    source_type: document.data.sourceType,
                    status: document.data.status,
                    updated_at: document.data.createdAt,
                }, lookups, lines.data.map((line) => ({ id: line.id, item_label: line.itemLabel, line_total: line.lineTotal, quantity: line.quantity, source_line_id: line.sourceLineId, unit_price: line.unitPrice, uom_label: line.uomLabel }))),
            };
        },
        preview: async (input: unknown): Promise<ApiPreviewResponse<unknown, SalesCalculationPreview['calculated']>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/sales/calculate-invoice', { body: contextPayload(asRecord(input)), method: 'POST' });
            return {
                breakdown: Array.isArray(response.data.lines) ? response.data.lines as BackendRecord[] : [],
                calculated: { discountTotal: money(response.data.discount_total), grandTotal: money(response.data.grand_total), pricing: 'Resolved by backend', subtotal: money(response.data.subtotal), taxTotal: money(response.data.tax_total), uomConversion: 'Validated by backend' },
                errors: [],
                input: asRecord(input),
                warnings: [],
            };
        },
        createDirect: (input: SalesInvoiceFormInput) => salesApi.invoices.createFromOrder(input.sourceId, input),
        createFromOrder: (salesOrderId: string, input: SalesInvoiceFormInput) => httpClient<ApiResponse<unknown>>('/api/sales/sales-invoices/from-so', { body: contextPayload({ lines: input.lines.map((line) => ({ linked_quantity: Number(line.quantity), unit_price: Number(line.unitPrice) })), sales_order_id: Number(salesOrderId) }), method: 'POST' }),
        createFromDelivery: (gdnHeaderId: string, input: SalesInvoiceFormInput) => httpClient<ApiResponse<unknown>>('/api/sales/sales-invoices/from-gdn', { body: contextPayload({ gdn_header_id: Number(gdnHeaderId), lines: input.lines.map((line) => ({ linked_quantity: Number(line.quantity), unit_price: Number(line.unitPrice) })) }), method: 'POST' }),
        post: (invoiceId: string, sourceType: string, sourceId: string) => httpClient<ApiResponse<unknown>>(`/api/sales/sales-invoices/${invoiceId}/post`, { body: contextPayload({ source_id: Number(sourceId), source_type: sourceType }), method: 'POST' }),
        cancel: (invoiceId: string, sourceType: string, sourceId: string) => httpClient<ApiResponse<unknown>>(`/api/sales/sales-invoices/${invoiceId}/cancel`, { body: contextPayload({ source_id: Number(sourceId), source_type: sourceType }), method: 'POST' }),
        reverse: (invoiceId: string, sourceType: string, sourceId: string) => httpClient<ApiResponse<unknown>>(`/api/sales/sales-invoices/${invoiceId}/reverse`, { body: contextPayload({ source_id: Number(sourceId), source_type: sourceType }), method: 'POST' }),
        generateDocument: (entityType: 'gdn_header' | 'sales_order' | 'sales_return', entityId: string) => httpClient<ApiResponse<unknown>>(`/api/sales/workflows/${entityType}/${entityId}/document`, { body: contextPayload(), method: 'POST' }),
    },
    payments: {
        list: async (query: SalesListQuery = {}): Promise<ApiCollectionResponse<SalesPayment>> => {
            const [response, lookups] = await Promise.all([
                httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-payments', { query: contextQuery({ page: query.page, per_page: query.perPage ?? 50, status: query.status }) }),
                lookupContext(),
            ]);
            return collection(response, (row) => normalizePayment(row, lookups));
        },
        get: async (id: string): Promise<ApiResponse<SalesPayment>> => {
            const [response, allocations, lookups] = await Promise.all([
                httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-payments/${id}`),
                httpClient<ApiCollectionResponse<BackendRecord>>(`/api/sales/sales-payments/${id}/allocations`, { query: contextQuery() }),
                lookupContext(),
            ]);
            return { ...response, data: normalizePayment(response.data, lookups, allocations.data) };
        },
        create: (input: SalesPaymentFormInput) => httpClient<ApiResponse<unknown>>('/api/sales/sales-payments', {
            body: contextPayload({ amount: Number(input.amount), direction: 'customer_receipt', party_id: Number(input.customerId), party_type: 'customer', payment_date: input.paymentDate, payment_method: input.method, reference_number: input.reference || null, source_id: asOptionalNumber(input.sourceId), source_type: input.sourceType || null }),
            method: 'POST',
        }),
        post: (id: string) => httpClient<ApiResponse<unknown>>(`/api/sales/sales-payments/${id}/post`, { body: contextPayload(), method: 'POST' }),
        reverse: (id: string) => httpClient<ApiResponse<unknown>>(`/api/sales/sales-payments/${id}/reverse`, { body: contextPayload(), method: 'POST' }),
        previewAllocation: async (input: unknown): Promise<ApiPreviewResponse<unknown, BackendRecord>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/sales/preview-payment-allocation', { body: contextPayload(asRecord(input)), method: 'POST' });
            return { breakdown: [], calculated: response.data, errors: [], input, warnings: [] };
        },
        allocate: (paymentId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/sales/sales-payments/${paymentId}/allocate`, { body: contextPayload(asRecord(input)), method: 'POST' }),
    },
    advances: {
        list: async (): Promise<ApiCollectionResponse<CustomerAdvance>> => {
            const response = await httpClient<ApiResponse<{ advances?: BackendRecord[] } | BackendRecord[]>>('/api/sales/integrations/customers/advances', { query: contextQuery() });
            const raw = Array.isArray(response.data) ? response.data : Array.isArray(response.data.advances) ? response.data.advances : [];
            const lookups = await lookupContext();
            return { data: raw.map((row, index) => ({ advanceNumber: asString(row.payment_number ?? row.reference_number, `ADV-${index + 1}`), amount: money(row.amount), customer: lookupLabel(lookups.customers, row.party_id ?? row.customer_id, 'Customer not loaded'), id: asString(row.id ?? index), remainingAmount: money(row.remaining_amount ?? row.unallocated_amount), status: asString(row.status, 'active') })) };
        },
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/sales/sales-advances', { body: contextPayload(asRecord(input)), method: 'POST' }),
        allocate: (id: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/sales/sales-advances/${id}/allocate`, { body: contextPayload(asRecord(input)), method: 'POST' }),
    },
    returns: {
        list: async (query: SalesListQuery = {}): Promise<ApiCollectionResponse<SalesReturn>> => {
            const [response, lookups] = await Promise.all([
                httpClient<ApiCollectionResponse<BackendRecord>>('/api/sales/sales-returns', { query: contextQuery({ page: query.page, per_page: query.perPage ?? 50, return_number: query.search, status: query.status }) }),
                lookupContext(),
            ]);
            return collection(response, (row) => normalizeReturn(row, lookups));
        },
        get: async (id: string): Promise<ApiResponse<SalesReturn>> => {
            const [response, lines, lookups] = await Promise.all([
                httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-returns/${id}`),
                returnLines(id),
                lookupContext(),
            ]);
            return { ...response, data: normalizeReturn(response.data, lookups, lines) };
        },
        createWithLines: async (input: SalesReturnFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/sales/sales-returns/with-lines', { body: returnPayload(input), method: 'POST' });
            return { ...response, data: normalizeReturn(response.data, await lookupContext()) };
        },
        updateWithLines: async (id: string, input: SalesReturnFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/sales/sales-returns/${id}/with-lines`, { body: returnPayload(input), method: 'PUT' });
            return { ...response, data: normalizeReturn(response.data, await lookupContext()) };
        },
        previewEffect: async (input: unknown): Promise<ApiPreviewResponse<unknown, BackendRecord>> => {
            const response = await httpClient<ApiResponse<BackendRecord[]>>('/api/sales/lookups/returnable-lines', { query: contextQuery(asRecord(input) as Record<string, string | number | boolean | undefined>) });
            return { breakdown: response.data, calculated: { line_count: response.data.length }, errors: [], input, warnings: [] };
        },
        transition: (id: string, action: string) => httpClient<ApiResponse<unknown>>(`/api/sales/workflows/sales_return/${id}/transition`, { body: contextPayload({ action }), method: 'POST' }),
    },
    refunds: {
        list: async (): Promise<ApiCollectionResponse<CustomerRefund>> => ({ data: [] }),
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/sales/sales-refunds', { body: contextPayload(asRecord(input)), method: 'POST' }),
    },
    ledgerNotes: {
        list: async (query: SalesListQuery & { noteType?: string } = {}): Promise<ApiCollectionResponse<SalesLedgerNote>> => {
            const [orders, deliveries, returns] = await Promise.all([
                salesApi.orders.list({ perPage: 100, search: query.search, status: query.status }),
                salesApi.deliveries.list({ perPage: 100, search: query.search, status: query.status }),
                salesApi.returns.list({ perPage: 100, search: query.search, status: query.status }),
            ]);
            const data = [
                ...orders.data.flatMap((order) => ledgerNotesFromSource(order, 'sales_order', order.soNumber)),
                ...deliveries.data.flatMap((delivery) => ledgerNotesFromSource(delivery, 'gdn_header', delivery.gdnNumber)),
                ...returns.data.flatMap((salesReturn) => ledgerNotesFromSource(salesReturn, 'sales_return', salesReturn.returnNumber)),
            ].filter((note) => !query.noteType || note.noteType === query.noteType);

            return { data };
        },
    },
    settings: {
        get: async () => {
            const response = await httpClient<ApiResponse<BackendRecord | null>>('/api/sales/settings', { query: contextQuery() });
            return { ...response, data: normalizeSettings(asRecord(response.data)) };
        },
        update: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/sales/settings', { body: contextPayload(asRecord(input)), method: 'PUT' }),
        initialize: () => httpClient<ApiResponse<unknown>>('/api/sales/settings/initialize', { body: contextPayload(), method: 'POST' }),
    },
    previews: {
        document: (entityType: 'gdn_header' | 'sales_order' | 'sales_return', entityId: string) => salesApi.invoices.generateDocument(entityType, entityId),
        financePosting: (entityType: 'gdn_header' | 'sales_order' | 'sales_return', entityId: string) => httpClient<ApiResponse<SalesFinancePostingPreview>>(`/api/sales/workflows/${entityType}/${entityId}/finance/post`, { body: contextPayload({ preview_only: true }), method: 'POST' }),
        inventoryEffect: (entityType: 'gdn_header' | 'sales_order' | 'sales_return', entityId: string) => httpClient<ApiResponse<SalesInventoryEffect[]>>(`/api/sales/workflows/${entityType}/${entityId}/inventory/post`, { body: contextPayload({ preview_only: true }), method: 'POST' }),
        stockAvailability: (input: unknown) => httpClient<ApiPreviewResponse<unknown, SalesInventoryEffect>>('/api/sales/stock-availability', { method: 'GET', query: contextQuery(asRecord(input) as Record<string, string | number | boolean | undefined>) }),
    },
};
