import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredAuthSession, getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import { documentApi } from '../../document/services/documentApi';
import { inventoryApi } from '../../inventory/services/inventoryApi';
import { itemApi } from '../../item/services/itemApi';
import { supplierApi } from '../../supplier/services/supplierApi';
import type {
    GoodsReceivedNote,
    GoodsReceivedNoteLine,
    GrnFormInput,
    PurchaseAdvance,
    PurchaseAuditEntry,
    PurchaseCalculationPreview,
    PurchaseDashboardMetric,
    PurchaseFinancePostingPreview,
    PurchaseInventoryEffect,
    PurchaseInvoice,
    PurchaseInvoiceFormInput,
    PurchaseInvoiceLine,
    PurchaseLedgerNote,
    PurchaseLineFormInput,
    PurchaseListQuery,
    PurchaseLookupOption,
    PurchaseOrder,
    PurchaseOrderFormInput,
    PurchaseOrderLine,
    PurchasePayment,
    PurchasePaymentAllocation,
    PurchasePaymentFormInput,
    PurchaseReturn,
    PurchaseReturnFormInput,
    PurchaseReturnLine,
    PurchaseSettings,
    SupplierRefund,
} from '../types/purchase.types';

type BackendRecord = Record<string, unknown>;
type LookupContext = {
    items: Map<string, PurchaseLookupOption>;
    suppliers: Map<string, PurchaseLookupOption>;
    uoms: Map<string, PurchaseLookupOption>;
    warehouses: Map<string, PurchaseLookupOption>;
};

const emptyLookupContext: LookupContext = {
    items: new Map(),
    suppliers: new Map(),
    uoms: new Map(),
    warehouses: new Map(),
};

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
        per_page: 25,
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

function lookupLabel(map: Map<string, PurchaseLookupOption>, id: unknown, fallback: string): string {
    const key = asString(id);
    return map.get(key)?.label ?? fallback;
}

function directLabel(raw: BackendRecord, keys: string[], fallback = ''): string {
    for (const key of keys) {
        const value = asString(raw[key]);
        if (value) return value;
    }

    return fallback;
}

function normalizeLookup(id: unknown, code: unknown, name: unknown, fallback: string): PurchaseLookupOption {
    const normalizedId = asString(id);
    const normalizedCode = asString(code);
    const normalizedName = asString(name, fallback);

    return {
        id: normalizedId,
        label: normalizedCode && normalizedName ? `${normalizedCode} - ${normalizedName}` : normalizedName || normalizedCode || fallback,
        secondary: normalizedCode,
    };
}

export function clearPurchaseLookupCache(): void {
    // Kept for callers that invalidate form lookups after writes.
}

function normalizeOrderLine(raw: BackendRecord, lookups: LookupContext, index = 0): PurchaseOrderLine {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);
    const ordered = Number(raw.ordered_qty ?? raw.quantity ?? 0);
    const received = Number(raw.received_qty ?? 0);
    const remaining = Math.max(0, ordered - received);

    return {
        backendConvertedQuantity: money(raw.base_quantity ?? raw.converted_quantity ?? raw.ordered_qty),
        discountAmount: money(raw.discount_amount),
        id: asString(raw.id, `line-${index}`),
        item: directLabel(raw, ['item_label', 'item_name', 'item_code']) || lookupLabel(lookups.items, itemId, 'Item not resolved'),
        itemId,
        lineTotal: money(raw.line_total ?? raw.line_total_with_tax),
        orderedQuantity: money(raw.ordered_qty ?? raw.quantity),
        receivedQuantity: money(raw.received_qty),
        remainingQuantity: money(raw.remaining_quantity ?? remaining),
        taxAmount: money(raw.tax_amount),
        unitPrice: money(raw.unit_price),
        uom: directLabel(raw, ['uom_label', 'uom_symbol', 'uom_code', 'uom_name']) || lookupLabel(lookups.uoms, uomId, 'UOM not resolved'),
        uomId,
    };
}

function normalizeOrder(raw: BackendRecord, lookups: LookupContext, lines: BackendRecord[] = []): PurchaseOrder {
    const supplierId = asString(raw.supplier_id);
    const warehouseId = asString(raw.warehouse_id);

    return {
        balance: money(raw.balance),
        creditNoteTotal: money(raw.credit_note_total),
        debitNoteTotal: money(raw.debit_note_total),
        expectedDate: asString(raw.expected_date),
        grandTotal: money(raw.grand_total),
        id: asString(raw.id),
        lines: lines.map((line, index) => normalizeOrderLine(line, lookups, index)),
        orderDate: asString(raw.order_date ?? raw.po_date),
        poNumber: asString(raw.po_number ?? raw.purchase_order_number, `PO-${asString(raw.id)}`),
        status: asString(raw.status, 'draft') as PurchaseOrder['status'],
        supplier: directLabel(raw, ['supplier_label', 'supplier_name', 'supplier_code']) || lookupLabel(lookups.suppliers, supplierId, 'Supplier not resolved'),
        supplierId,
        updatedAt: asString(raw.updated_at),
        warehouse: directLabel(raw, ['warehouse_label', 'warehouse_name', 'warehouse_code']) || lookupLabel(lookups.warehouses, warehouseId, 'Warehouse not resolved'),
        warehouseId,
        workflow: asString(raw.workflow ?? raw.status, 'draft'),
    };
}

function normalizeGrnLine(raw: BackendRecord, lookups: LookupContext, index = 0): GoodsReceivedNoteLine {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);

    return {
        acceptedQuantity: money(raw.accepted_qty ?? raw.received_qty),
        backendBaseQuantity: money(raw.base_quantity ?? raw.received_qty),
        id: asString(raw.id, `line-${index}`),
        item: directLabel(raw, ['item_label', 'item_name', 'item_code']) || lookupLabel(lookups.items, itemId, 'Item not resolved'),
        itemId,
        orderedQuantity: money(raw.ordered_quantity ?? raw.ordered_qty),
        rejectedQuantity: money(raw.rejected_qty),
        sourceLine: asString(raw.purchase_order_line_id),
        uom: directLabel(raw, ['uom_label', 'uom_symbol', 'uom_code', 'uom_name']) || lookupLabel(lookups.uoms, uomId, 'UOM not resolved'),
        uomId,
    };
}

function normalizeGrn(raw: BackendRecord, lookups: LookupContext, lines: BackendRecord[] = []): GoodsReceivedNote {
    const supplierId = asString(raw.supplier_id);
    const warehouseId = asString(raw.warehouse_id);

    return {
        creditNoteTotal: money(raw.credit_note_total),
        debitNoteTotal: money(raw.debit_note_total),
        grnDate: asString(raw.received_date ?? raw.grn_date),
        grnNumber: asString(raw.grn_number, `GRN-${asString(raw.id)}`),
        id: asString(raw.id),
        inventoryStatus: asString(raw.inventory_status ?? raw.putaway_status ?? raw.status, 'draft'),
        lines: lines.map((line, index) => normalizeGrnLine(line, lookups, index)),
        sourcePo: asString(raw.purchase_order_number ?? raw.purchase_order_id),
        status: asString(raw.status, 'draft') as GoodsReceivedNote['status'],
        supplier: directLabel(raw, ['supplier_label', 'supplier_name', 'supplier_code']) || lookupLabel(lookups.suppliers, supplierId, 'Supplier not resolved'),
        supplierId,
        updatedAt: asString(raw.updated_at),
        warehouse: directLabel(raw, ['warehouse_label', 'warehouse_name', 'warehouse_code']) || lookupLabel(lookups.warehouses, warehouseId, 'Warehouse not resolved'),
        warehouseId,
    };
}

function normalizeInvoiceLine(raw: BackendRecord, lookups: LookupContext, index = 0): PurchaseInvoiceLine {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);

    return {
        discountAmount: money(raw.discount_amount),
        id: asString(raw.id ?? raw.link_id, `line-${index}`),
        invoiceQuantity: money(raw.quantity ?? raw.linked_quantity),
        item: directLabel(raw, ['item_label', 'item_name', 'item_code']) || lookupLabel(lookups.items, itemId, 'Item not resolved'),
        itemId,
        lineTotal: money(raw.line_total ?? raw.linked_amount),
        sourceLine: asString(raw.source_line_id),
        taxAmount: money(raw.tax_amount),
        unitPrice: money(raw.unit_price),
        uom: directLabel(raw, ['uom_label', 'uom_symbol', 'uom_code', 'uom_name']) || lookupLabel(lookups.uoms, uomId, 'UOM not resolved'),
        uomId,
    };
}

function normalizeInvoice(raw: BackendRecord, lookups: LookupContext, lines: BackendRecord[] = []): PurchaseInvoice {
    const supplierId = asString(raw.supplier_id ?? raw.party_id);

    return {
        balance: money(raw.balance ?? raw.outstanding_amount),
        documentStatus: asString(raw.document_status ?? raw.status, 'draft'),
        dueDate: asString(raw.due_date),
        grandTotal: money(raw.grand_total ?? raw.linked_amount ?? raw.total_amount),
        id: asString(raw.id ?? raw.document_id),
        invoiceDate: asString(raw.invoice_date ?? raw.document_date ?? raw.created_at).slice(0, 10),
        invoiceNumber: asString(raw.invoice_number ?? raw.document_number ?? raw.source_reference, `PINV-${asString(raw.id ?? raw.document_id)}`),
        lines: lines.map((line, index) => normalizeInvoiceLine(line, lookups, index)),
        paidAmount: money(raw.paid_amount ?? raw.allocated_amount),
        sourceReference: asString(raw.source_reference ?? raw.source_type),
        status: asString(raw.status, 'draft') as PurchaseInvoice['status'],
        supplier: directLabel(raw, ['supplier_label', 'supplier_name', 'party_label']) || lookupLabel(lookups.suppliers, supplierId, 'Supplier not resolved'),
        supplierId,
        updatedAt: asString(raw.updated_at),
    };
}

function normalizePaymentAllocation(raw: BackendRecord, index = 0): PurchasePaymentAllocation {
    return {
        allocatedAmount: money(raw.allocated_amount),
        documentBalanceAfter: money(raw.remaining_after_allocation ?? raw.document_balance_after),
        id: asString(raw.id, `allocation-${index}`),
        sourceDocument: asString(raw.source_reference ?? raw.document_number ?? raw.document_id, 'Linked document'),
        status: asString(raw.status, 'active'),
    };
}

function normalizePayment(raw: BackendRecord, lookups: LookupContext, allocations: BackendRecord[] = []): PurchasePayment {
    const supplierId = asString(raw.party_id ?? raw.supplier_id);

    return {
        allocations: allocations.map(normalizePaymentAllocation),
        amount: money(raw.amount),
        id: asString(raw.id),
        method: asString(raw.payment_method_name ?? raw.method ?? raw.payment_method ?? raw.payment_method_id, 'Payment method'),
        paymentDate: asString(raw.payment_date ?? raw.transaction_date),
        paymentNumber: asString(raw.payment_number ?? raw.reference_number, `SPAY-${asString(raw.id)}`),
        reference: asString(raw.reference_number ?? raw.source_reference),
        status: asString(raw.status, 'draft') as PurchasePayment['status'],
        supplier: directLabel(raw, ['supplier_label', 'supplier_name', 'party_label']) || lookupLabel(lookups.suppliers, supplierId, 'Supplier not resolved'),
        supplierId,
        unallocatedAmount: money(raw.unallocated_amount ?? raw.remaining_amount),
    };
}

function normalizeReturnLine(raw: BackendRecord, lookups: LookupContext, index = 0): PurchaseReturnLine {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);

    return {
        backendReturnableQuantity: money(raw.available_return_qty ?? raw.returnable_qty),
        id: asString(raw.id, `line-${index}`),
        item: directLabel(raw, ['item_label', 'item_name', 'item_code']) || lookupLabel(lookups.items, itemId, 'Item not resolved'),
        itemId,
        returnQuantity: money(raw.return_qty ?? raw.quantity),
        sourceLine: asString(raw.original_grn_line_id ?? raw.source_line_id),
        uom: directLabel(raw, ['uom_label', 'uom_symbol', 'uom_code', 'uom_name']) || lookupLabel(lookups.uoms, uomId, 'UOM not resolved'),
        uomId,
    };
}

function normalizeReturn(raw: BackendRecord, lookups: LookupContext, lines: BackendRecord[] = []): PurchaseReturn {
    const supplierId = asString(raw.supplier_id);

    return {
        creditNoteTotal: money(raw.credit_note_total),
        debitNoteTotal: money(raw.debit_note_total),
        id: asString(raw.id),
        lines: lines.map((line, index) => normalizeReturnLine(line, lookups, index)),
        returnNumber: asString(raw.return_number, `PRET-${asString(raw.id)}`),
        returnTotal: money(raw.grand_total),
        sourceReference: asString(raw.source_reference ?? raw.original_document_id ?? raw.original_grn_id ?? raw.original_purchase_order_id),
        status: asString(raw.status, 'draft') as PurchaseReturn['status'],
        supplier: directLabel(raw, ['supplier_label', 'supplier_name', 'supplier_code']) || lookupLabel(lookups.suppliers, supplierId, 'Supplier not resolved'),
        supplierId,
        updatedAt: asString(raw.updated_at),
    };
}

function ledgerNotesFromSource(
    source: PurchaseOrder | GoodsReceivedNote | PurchaseReturn,
    sourceType: PurchaseLedgerNote['sourceType'],
    sourceReference: string,
): PurchaseLedgerNote[] {
    const debitAmount = Number(source.debitNoteTotal);
    const creditAmount = Number(source.creditNoteTotal);
    const notes: PurchaseLedgerNote[] = [];

    if (Number.isFinite(debitAmount) && debitAmount > 0) {
        notes.push({
            amount: money(debitAmount),
            id: `${sourceType}-${source.id}-debit`,
            noteType: 'debit',
            sourceId: source.id,
            sourceReference,
            sourceType,
            status: source.status,
            supplier: source.supplier,
            updatedAt: source.updatedAt,
        });
    }

    if (Number.isFinite(creditAmount) && creditAmount > 0) {
        notes.push({
            amount: money(creditAmount),
            id: `${sourceType}-${source.id}-credit`,
            noteType: 'credit',
            sourceId: source.id,
            sourceReference,
            sourceType,
            status: source.status,
            supplier: source.supplier,
            updatedAt: source.updatedAt,
        });
    }

    return notes;
}

function normalizeSettings(raw: BackendRecord): PurchaseSettings {
    return {
        allowDirectInvoice: raw.allow_direct_purchase_document !== false,
        allowGrnWithoutPo: raw.allow_direct_grn !== false,
        allowInvoiceWithoutGrn: raw.require_grn_before_invoice !== true,
        allowOverReceipt: raw.allow_over_receipt === true,
        defaultPayableAccount: asString(raw.default_payable_account_label ?? raw.default_payable_account_id, 'Not configured'),
        defaultPaymentTerm: asString(raw.default_payment_term_label ?? raw.default_payment_term_id, 'Not configured'),
        defaultTaxGroup: asString(raw.default_tax_group_label ?? raw.default_tax_group_id, 'Not configured'),
        defaultWarehouse: asString(raw.default_warehouse_label ?? raw.default_warehouse_id, 'Not configured'),
        grnSequence: asString(raw.grn_sequence_label ?? raw.grn_sequence_id, 'Backend sequence'),
        id: asString(raw.id),
        invoiceDocumentDefinition: asString(raw.purchase_invoice_document_definition_label ?? raw.purchase_invoice_document_definition_id, 'Document module'),
        invoiceMatchingRule: asString(raw.invoice_matching_rule ?? (raw.require_grn_before_invoice ? 'GRN required' : 'Flexible')),
        invoiceSequence: asString(raw.invoice_sequence_label ?? raw.purchase_invoice_sequence_id, 'Backend sequence'),
        poSequence: asString(raw.po_sequence_label ?? raw.purchase_order_sequence_id, 'Backend sequence'),
        returnSequence: asString(raw.return_sequence_label ?? raw.purchase_return_sequence_id, 'Backend sequence'),
        stockReceiveTiming: asString(raw.stock_receive_timing ?? 'Backend workflow'),
    };
}

function linePayload(line: PurchaseLineFormInput, quantityField: 'ordered_qty' | 'received_qty' | 'return_qty'): BackendRecord {
    return {
        discount_type: line.discountType || null,
        discount_value: line.discountValue ? Number(line.discountValue) : 0,
        item_id: Number(line.itemId),
        [quantityField]: Number(line.quantity),
        unit_price: Number(line.unitPrice || 0),
        uom_id: Number(line.uomId),
    };
}

function orderPayload(input: PurchaseOrderFormInput): BackendRecord {
    return contextPayload({
        expected_date: input.expectedDate || null,
        lines: input.lines.map((line) => linePayload(line, 'ordered_qty')),
        notes: input.notes || null,
        order_date: input.orderDate,
        po_number: input.poNumber,
        status: input.status || 'draft',
        supplier_id: Number(input.supplierId),
        warehouse_id: Number(input.warehouseId),
    });
}

function grnPayload(input: GrnFormInput): BackendRecord {
    return contextPayload({
        grn_number: input.grnNumber,
        lines: input.lines.map((line) => ({
            ...linePayload(line, 'received_qty'),
            accepted_qty: Number(line.quantity),
        })),
        notes: input.notes || null,
        purchase_order_id: asOptionalNumber(input.purchaseOrderId),
        received_date: input.grnDate,
        status: input.status || 'draft',
        supplier_id: Number(input.supplierId),
        warehouse_id: Number(input.warehouseId),
    });
}

function returnPayload(input: PurchaseReturnFormInput): BackendRecord {
    return contextPayload({
        lines: input.lines.map((line) => linePayload(line, 'return_qty')),
        notes: input.notes || null,
        original_document_id: input.sourceType === 'document' ? asOptionalNumber(input.sourceId) : undefined,
        original_grn_id: input.sourceType === 'grn_header' ? asOptionalNumber(input.sourceId) : undefined,
        original_purchase_order_id: input.sourceType === 'purchase_order' ? asOptionalNumber(input.sourceId) : undefined,
        return_date: input.returnDate,
        return_number: input.returnNumber,
        return_reason: input.returnReason || null,
        status: input.status || 'draft',
        supplier_id: Number(input.supplierId),
    });
}

async function orderLines(orderId: string): Promise<BackendRecord[]> {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-order-lines', {
        query: contextQuery({ purchase_order_id: Number(orderId) }),
    });
    return response.data;
}

async function grnLines(grnId: string): Promise<BackendRecord[]> {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/grn-lines', {
        query: contextQuery({ grn_header_id: Number(grnId) }),
    });
    return response.data;
}

async function returnLines(returnId: string): Promise<BackendRecord[]> {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-return-lines', {
        query: contextQuery({ purchase_return_id: Number(returnId) }),
    });
    return response.data;
}

export const purchaseApi = {
    dashboard: {
        summary: async (): Promise<ApiCollectionResponse<PurchaseDashboardMetric>> => {
            const [orders, grns, invoices, payments, returns] = await Promise.allSettled([
                purchaseApi.orders.list({ perPage: 1 }),
                purchaseApi.grns.list({ perPage: 1 }),
                purchaseApi.invoices.list({ perPage: 1 }),
                purchaseApi.payments.list({ perPage: 1 }),
                purchaseApi.returns.list({ perPage: 1 }),
            ]);
            const total = (result: PromiseSettledResult<ApiCollectionResponse<unknown>>) => result.status === 'fulfilled'
                ? String(result.value.meta?.total ?? result.value.data.length)
                : 'Unavailable';

            return {
                data: [
                    { label: 'Purchase Orders', status: 'active', value: total(orders) },
                    { label: 'GRNs', status: 'posted', value: total(grns) },
                    { label: 'Supplier Invoices', status: 'posted', value: total(invoices) },
                    { label: 'Supplier Payments', status: 'posted', value: total(payments) },
                    { label: 'Purchase Returns', status: 'draft', value: total(returns) },
                ],
            };
        },
    },
    lookups: {
        items: async () => {
            const response = await itemApi.listItems({ perPage: 25, status: 'active' });
            return { data: response.data.map((item) => normalizeLookup(item.id, item.code, item.name, 'Item')) };
        },
        itemUoms: async (itemId: string) => {
            const response = await itemApi.getItemUnits(itemId);
            const data = response.data.map((unit) => ({
                id: unit.id,
                label: unit.unit,
                secondary: unit.purpose,
            })).filter((unit) => unit.id && !unit.id.startsWith('base-uom'));
            return { data };
        },
        suppliers: async () => {
            const response = await supplierApi.listSuppliers({ perPage: 25, status: 'active' });
            return { data: response.data.map((supplier) => normalizeLookup(supplier.id, supplier.code, supplier.displayName || supplier.name, 'Supplier')) };
        },
        uoms: async () => {
            const response = await itemApi.listUoms();
            return { data: response.data.map((uom) => normalizeLookup(uom.id, uom.symbol, uom.name, 'UOM')) };
        },
        warehouses: async () => inventoryApi.listWarehouses(),
    },
    orders: {
        list: async (query: PurchaseListQuery = {}): Promise<ApiCollectionResponse<PurchaseOrder>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-orders', {
                query: contextQuery({ page: query.page, per_page: query.perPage ?? 50, po_number: query.search, status: query.status }),
            });
            return collection(response, (row) => normalizeOrder(row, emptyLookupContext));
        },
        get: async (id: string): Promise<ApiResponse<PurchaseOrder>> => {
            const [response, lines] = await Promise.all([
                httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-orders/${id}`),
                orderLines(id),
            ]);
            return { ...response, data: normalizeOrder(response.data, emptyLookupContext, lines) };
        },
        createWithLines: async (input: PurchaseOrderFormInput) => {
            clearPurchaseLookupCache();
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/purchase/purchase-orders/with-lines', { body: orderPayload(input), method: 'POST' });
            return { ...response, data: normalizeOrder(response.data, emptyLookupContext) };
        },
        updateWithLines: async (id: string, input: PurchaseOrderFormInput) => {
            clearPurchaseLookupCache();
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-orders/${id}/with-lines`, { body: orderPayload(input), method: 'PUT' });
            return { ...response, data: normalizeOrder(response.data, emptyLookupContext) };
        },
        history: async (id: string): Promise<ApiCollectionResponse<PurchaseAuditEntry>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/purchase/workflows/purchase_order/${id}/history`, { query: contextQuery() });
            return collection(response, (row) => ({
                actor: asString(row.actor_name ?? row.actor_id, 'System'),
                description: asString(row.description ?? row.status ?? row.transition, 'Purchase order activity'),
                id: asString(row.id),
                time: asString(row.created_at ?? row.updated_at),
                type: asString(row.type ?? row.status, 'workflow'),
            }));
        },
        transition: (id: string, action: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/workflows/purchase_order/${id}/transition`, { body: contextPayload({ action }), method: 'POST' }),
    },
    grns: {
        list: async (query: PurchaseListQuery = {}): Promise<ApiCollectionResponse<GoodsReceivedNote>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/grn-headers', {
                query: contextQuery({ grn_number: query.search, page: query.page, per_page: query.perPage ?? 50, status: query.status }),
            });
            return collection(response, (row) => normalizeGrn(row, emptyLookupContext));
        },
        get: async (id: string): Promise<ApiResponse<GoodsReceivedNote>> => {
            const [response, lines] = await Promise.all([
                httpClient<ApiResponse<BackendRecord>>(`/api/purchase/grn-headers/${id}`),
                grnLines(id),
            ]);
            return { ...response, data: normalizeGrn(response.data, emptyLookupContext, lines) };
        },
        createDirect: async (input: GrnFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/purchase/grn-headers/with-lines', { body: grnPayload(input), method: 'POST' });
            return { ...response, data: normalizeGrn(response.data, emptyLookupContext) };
        },
        updateWithLines: async (id: string, input: GrnFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/purchase/grn-headers/${id}/with-lines`, { body: grnPayload(input), method: 'PUT' });
            return { ...response, data: normalizeGrn(response.data, emptyLookupContext) };
        },
        history: async (id: string): Promise<ApiCollectionResponse<PurchaseAuditEntry>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/purchase/workflows/grn_header/${id}/history`, { query: contextQuery() });
            return collection(response, (row) => ({
                actor: asString(row.actor_name ?? row.actor_id, 'System'),
                description: asString(row.description ?? row.status ?? row.transition, 'GRN activity'),
                id: asString(row.id),
                time: asString(row.created_at ?? row.updated_at),
                type: asString(row.type ?? row.status, 'workflow'),
            }));
        },
        postInventory: (id: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/workflows/grn_header/${id}/inventory/post`, { body: contextPayload(), method: 'POST' }),
        transition: (id: string, action: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/workflows/grn_header/${id}/transition`, { body: contextPayload({ action }), method: 'POST' }),
    },
    invoices: {
        list: async (query: PurchaseListQuery = {}): Promise<ApiCollectionResponse<PurchaseInvoice>> => {
            const response = await documentApi.listDocuments({ page: query.page, per_page: query.perPage ?? 50, source_module: 'purchase', status: query.status });
            const purchaseDocuments = response.data.filter((document) => (
                document.sourceModule === 'purchase'
                || ['purchase_invoice', 'supplier_invoice'].includes(document.typeCode)
                || ['purchase_order', 'grn_header', 'purchase_return'].includes(document.sourceType)
            ));

            return {
                data: purchaseDocuments.map((document) => normalizeInvoice({
                    document_date: document.documentDate,
                    document_id: document.id,
                    document_number: document.documentNumber,
                    due_date: document.dueDate,
                    grand_total: document.grandTotal,
                    source_reference: document.sourceReference,
                    source_type: document.sourceType,
                    status: document.status,
                    updated_at: document.createdAt,
                }, emptyLookupContext)),
                meta: response.meta,
            };
        },
        get: async (id: string): Promise<ApiResponse<PurchaseInvoice>> => {
            const [document, lines] = await Promise.all([
                documentApi.getDocument(id),
                documentApi.listDocumentLines(id),
            ]);
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
                }, emptyLookupContext, lines.data.map((line) => ({
                    id: line.id,
                    item_label: line.itemLabel,
                    line_total: line.lineTotal,
                    quantity: line.quantity,
                    source_line_id: line.sourceLineId,
                    unit_price: line.unitPrice,
                    uom_label: line.uomLabel,
                }))),
            };
        },
        preview: async (input: unknown): Promise<ApiPreviewResponse<unknown, PurchaseCalculationPreview['calculated']>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/purchase/calculate-invoice', { body: contextPayload(asRecord(input)), method: 'POST' });
            return {
                breakdown: Array.isArray(response.data.lines) ? response.data.lines as BackendRecord[] : [],
                calculated: {
                    discountTotal: money(response.data.discount_total),
                    grandTotal: money(response.data.grand_total),
                    subtotal: money(response.data.subtotal),
                    taxTotal: money(response.data.tax_total),
                    uomConversion: 'Validated by backend',
                },
                errors: [],
                input,
                warnings: [],
            };
        },
        createDirect: (input: PurchaseInvoiceFormInput) => purchaseApi.invoices.createFromPo(input.sourceId, input),
        createFromPo: (purchaseOrderId: string, input: PurchaseInvoiceFormInput) => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-invoices/from-po', { body: contextPayload({ lines: input.lines.map((line) => ({ linked_quantity: Number(line.quantity), unit_price: Number(line.unitPrice) })), purchase_order_id: Number(purchaseOrderId) }), method: 'POST' }),
        createFromGrn: (grnHeaderId: string, input: PurchaseInvoiceFormInput) => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-invoices/from-grn', { body: contextPayload({ lines: input.lines.map((line) => ({ linked_quantity: Number(line.quantity), unit_price: Number(line.unitPrice) })), grn_header_id: Number(grnHeaderId) }), method: 'POST' }),
        post: (invoiceId: string, sourceType: string, sourceId: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-invoices/${invoiceId}/post`, { body: contextPayload({ source_id: Number(sourceId), source_type: sourceType }), method: 'POST' }),
        cancel: (invoiceId: string, sourceType: string, sourceId: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-invoices/${invoiceId}/cancel`, { body: contextPayload({ source_id: Number(sourceId), source_type: sourceType }), method: 'POST' }),
        reverse: (invoiceId: string, sourceType: string, sourceId: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-invoices/${invoiceId}/reverse`, { body: contextPayload({ source_id: Number(sourceId), source_type: sourceType }), method: 'POST' }),
        generateDocument: (entityType: 'grn_header' | 'purchase_order' | 'purchase_return', entityId: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/workflows/${entityType}/${entityId}/document`, { body: contextPayload(), method: 'POST' }),
    },
    payments: {
        list: async (query: PurchaseListQuery = {}): Promise<ApiCollectionResponse<PurchasePayment>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-payments', { query: contextQuery({ page: query.page, per_page: query.perPage ?? 50, status: query.status }) });
            return collection(response, (row) => normalizePayment(row, emptyLookupContext));
        },
        get: async (id: string): Promise<ApiResponse<PurchasePayment>> => {
            const [response, allocations] = await Promise.all([
                httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-payments/${id}`),
                httpClient<ApiCollectionResponse<BackendRecord>>(`/api/purchase/purchase-payments/${id}/allocations`, { query: contextQuery() }),
            ]);
            return { ...response, data: normalizePayment(response.data, emptyLookupContext, allocations.data) };
        },
        create: (input: PurchasePaymentFormInput) => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-payments', {
            body: contextPayload({
                amount: Number(input.amount),
                direction: 'supplier_payment',
                party_id: Number(input.supplierId),
                party_type: 'supplier',
                payment_date: input.paymentDate,
                payment_method: input.method,
                reference_number: input.reference || null,
                source_id: asOptionalNumber(input.sourceId),
                source_type: input.sourceType || null,
            }),
            method: 'POST',
        }),
        post: (id: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-payments/${id}/post`, { body: contextPayload(), method: 'POST' }),
        reverse: (id: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-payments/${id}/reverse`, { body: contextPayload(), method: 'POST' }),
        previewAllocation: async (input: unknown): Promise<ApiPreviewResponse<unknown, BackendRecord>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/purchase/preview-payment-allocation', { body: contextPayload(asRecord(input)), method: 'POST' });
            return { breakdown: [], calculated: response.data, errors: [], input, warnings: [] };
        },
        allocate: (paymentId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-payments/${paymentId}/allocate`, { body: contextPayload(asRecord(input)), method: 'POST' }),
    },
    advances: {
        list: async (): Promise<ApiCollectionResponse<PurchaseAdvance>> => {
            const response = await httpClient<ApiResponse<{ advances?: BackendRecord[] } | BackendRecord[]>>('/api/purchase/integrations/suppliers/advances', { query: contextQuery() });
            const raw = Array.isArray(response.data) ? response.data : Array.isArray(response.data.advances) ? response.data.advances : [];
            return {
                data: raw.map((row, index) => ({
                    advanceNumber: asString(row.payment_number ?? row.reference_number, `ADV-${index + 1}`),
                    amount: money(row.amount),
                    id: asString(row.id ?? index),
                    remainingAmount: money(row.remaining_amount ?? row.unallocated_amount),
                    status: asString(row.status, 'active'),
                    supplier: directLabel(row, ['supplier_label', 'supplier_name', 'party_label']) || 'Supplier not resolved',
                })),
            };
        },
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-advances', { body: contextPayload(asRecord(input)), method: 'POST' }),
        allocate: (id: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/purchase/purchase-advances/${id}/allocate`, { body: contextPayload(asRecord(input)), method: 'POST' }),
    },
    returns: {
        list: async (query: PurchaseListQuery = {}): Promise<ApiCollectionResponse<PurchaseReturn>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/purchase/purchase-returns', { query: contextQuery({ page: query.page, per_page: query.perPage ?? 50, return_number: query.search, status: query.status }) });
            return collection(response, (row) => normalizeReturn(row, emptyLookupContext));
        },
        get: async (id: string): Promise<ApiResponse<PurchaseReturn>> => {
            const [response, lines] = await Promise.all([
                httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-returns/${id}`),
                returnLines(id),
            ]);
            return { ...response, data: normalizeReturn(response.data, emptyLookupContext, lines) };
        },
        createWithLines: async (input: PurchaseReturnFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/purchase/purchase-returns/with-lines', { body: returnPayload(input), method: 'POST' });
            return { ...response, data: normalizeReturn(response.data, emptyLookupContext) };
        },
        updateWithLines: async (id: string, input: PurchaseReturnFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/purchase/purchase-returns/${id}/with-lines`, { body: returnPayload(input), method: 'PUT' });
            return { ...response, data: normalizeReturn(response.data, emptyLookupContext) };
        },
        previewEffect: async (input: unknown): Promise<ApiPreviewResponse<unknown, BackendRecord>> => {
            const response = await httpClient<ApiResponse<BackendRecord[]>>('/api/purchase/lookups/returnable-lines', { query: contextQuery(asRecord(input) as Record<string, string | number | boolean | undefined>) });
            return { breakdown: response.data, calculated: { line_count: response.data.length }, errors: [], input, warnings: [] };
        },
        transition: (id: string, action: string) => httpClient<ApiResponse<unknown>>(`/api/purchase/workflows/purchase_return/${id}/transition`, { body: contextPayload({ action }), method: 'POST' }),
    },
    refunds: {
        list: async (): Promise<ApiCollectionResponse<SupplierRefund>> => ({ data: [] }),
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/purchase/purchase-refunds', { body: contextPayload(asRecord(input)), method: 'POST' }),
    },
    ledgerNotes: {
        list: async (query: PurchaseListQuery & { noteType?: string } = {}): Promise<ApiCollectionResponse<PurchaseLedgerNote>> => {
            const [orders, grns, returns] = await Promise.all([
                purchaseApi.orders.list({ perPage: 25, search: query.search, status: query.status }),
                purchaseApi.grns.list({ perPage: 25, search: query.search, status: query.status }),
                purchaseApi.returns.list({ perPage: 25, search: query.search, status: query.status }),
            ]);
            const data = [
                ...orders.data.flatMap((order) => ledgerNotesFromSource(order, 'purchase_order', order.poNumber)),
                ...grns.data.flatMap((grn) => ledgerNotesFromSource(grn, 'grn_header', grn.grnNumber)),
                ...returns.data.flatMap((purchaseReturn) => ledgerNotesFromSource(purchaseReturn, 'purchase_return', purchaseReturn.returnNumber)),
            ].filter((note) => !query.noteType || note.noteType === query.noteType);

            return { data };
        },
    },
    settings: {
        get: async () => {
            const response = await httpClient<ApiResponse<BackendRecord | null>>('/api/purchase/settings', { query: contextQuery() });
            return { ...response, data: normalizeSettings(asRecord(response.data)) };
        },
        update: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/purchase/settings', { body: contextPayload(asRecord(input)), method: 'PUT' }),
        initialize: () => httpClient<ApiResponse<unknown>>('/api/purchase/settings/initialize', { body: contextPayload(), method: 'POST' }),
    },
    previews: {
        document: (entityType: 'grn_header' | 'purchase_order' | 'purchase_return', entityId: string) => purchaseApi.invoices.generateDocument(entityType, entityId),
        financePosting: (entityType: 'grn_header' | 'purchase_order' | 'purchase_return', entityId: string) => httpClient<ApiResponse<PurchaseFinancePostingPreview>>(`/api/purchase/workflows/${entityType}/${entityId}/finance/post`, { body: contextPayload({ preview_only: true }), method: 'POST' }),
        inventoryEffect: (entityType: 'grn_header' | 'purchase_order' | 'purchase_return', entityId: string) => httpClient<ApiResponse<PurchaseInventoryEffect[]>>(`/api/purchase/workflows/${entityType}/${entityId}/inventory/post`, { body: contextPayload({ preview_only: true }), method: 'POST' }),
    },
};
