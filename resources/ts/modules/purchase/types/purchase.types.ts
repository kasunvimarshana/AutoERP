export type PurchaseStatus = 'draft' | 'confirmed' | 'partially_received' | 'received' | 'closed' | 'cancelled' | string;

export type PurchaseLineInput = {
    acceptedQty?: string;
    description?: string;
    discountAmount?: string;
    discountType?: 'percentage' | 'fixed' | '';
    discountValue?: string;
    itemId: number;
    purchaseOrderLineId?: number;
    receivedQty?: string;
    returnQty?: string;
    originalGrnLineId?: number;
    taxAmount?: string;
    unitPrice: string;
    uomId: number;
    warehouseId?: number;
    locationId?: number;
};

export type PurchaseOrderInput = {
    expectedDate?: string;
    lines: PurchaseLineInput[];
    notes?: string;
    orderDate: string;
    poNumber?: string;
    reference?: string;
    supplierId: number;
    warehouseId: number;
};

export type GrnInput = {
    grnNumber?: string;
    lines: PurchaseLineInput[];
    notes?: string;
    purchaseOrderId?: number;
    receivedDate: string;
    reference?: string;
    supplierId?: number;
    warehouseId?: number;
};

export type PurchaseReturnInput = {
    lines: PurchaseLineInput[];
    notes?: string;
    originalGrnId: number;
    originalInvoiceId?: number;
    reference?: string;
    returnDate: string;
    returnNumber?: string;
    returnReason?: string;
};

export type PurchaseOrder = {
    balance: string;
    expectedDate?: string | null;
    grandTotal: string;
    grns?: Array<Record<string, unknown>>;
    id: number;
    invoiceStatus: string;
    lines?: PurchaseLine[];
    notes?: string | null;
    orderDate: string;
    paidAmount: string;
    poNumber: string;
    reference?: string | null;
    status: PurchaseStatus;
    supplierBalance?: string | null;
    supplierId: number;
    supplierName?: string | null;
    warehouseId: number;
    warehouseName?: string | null;
};

export type Grn = {
    grandTotal: string;
    grnNumber: string;
    id: number;
    invoiceLinks?: InvoiceLink[];
    invoiceStatus: string;
    lines?: PurchaseLine[];
    notes?: string | null;
    poNumber?: string | null;
    purchaseOrderId?: number | null;
    receivedDate: string;
    reference?: string | null;
    status: string;
    supplierId: number;
    supplierName?: string | null;
    warehouseId: number;
};

export type PurchaseReturn = {
    grandTotal: string;
    grnNumber?: string | null;
    id: number;
    invoiceLinks?: InvoiceLink[];
    lines?: PurchaseLine[];
    notes?: string | null;
    originalGrnId: number;
    originalInvoiceId?: number | null;
    reference?: string | null;
    returnDate: string;
    returnNumber: string;
    returnReason?: string | null;
    status: string;
    supplierId: number;
    supplierName?: string | null;
};

export type PurchaseLine = {
    accepted_qty?: string;
    description?: string | null;
    discount_amount: string;
    id: number;
    invoiced_qty?: string;
    item_code?: string | null;
    item_id: number;
    item_name?: string | null;
    line_total_with_tax: string;
    original_grn_line_id?: number | null;
    purchase_order_line_id?: number | null;
    received_qty?: string;
    returned_qty?: string;
    return_qty?: string;
    tax_amount: string;
    unit_price: string;
    uom_code?: string | null;
    uom_id: number;
    warehouse_id?: number | null;
};

export type InvoiceLink = { balance_total: string; grand_total: string; id: number; invoice_id: number; invoice_number: string; linked_amount: string; status: string };
export type PurchasePage<T> = { items: T[]; meta: { currentPage: number; lastPage: number; perPage: number; total: number } };
export type PurchaseLookup = { base_uom_id?: number; code?: string | null; cost_price?: string; id: number; name: string; outstanding_balance?: string; payment_terms_days?: number; purchase_uom_id?: number | null; symbol?: string | null; type?: string };
export type PurchaseDashboard = { open_purchase_orders: number; pending_grns: number; posted_grns: number; purchase_order_total: string; supplier_outstanding: string; unpaid_purchase_invoices: { amount: string; count: number } };
