export type PurchaseOrderStatus = 'draft' | 'submitted' | 'approved' | 'partially_received' | 'received' | 'closed' | 'cancelled' | 'reversed';
export type GrnStatus = 'draft' | 'submitted' | 'inspected' | 'confirmed' | 'posted' | 'cancelled' | 'reversed';
export type PurchaseInvoiceStatus = 'draft' | 'posted' | 'partially_paid' | 'paid' | 'cancelled' | 'reversed';
export type PurchaseReturnStatus = 'draft' | 'approved' | 'posted' | 'refunded' | 'cancelled' | 'reversed';
export type PurchasePaymentStatus = 'draft' | 'posted' | 'allocated' | 'voided' | 'reversed';

export type PurchaseSourceReference = {
    id: string;
    sourceId?: string;
    sourceModule: string;
    sourceReference: string;
    sourceType: string;
};

export type PurchaseOrderLine = {
    backendConvertedQuantity: string;
    discountAmount: string;
    id: string;
    item: string;
    lineTotal: string;
    orderedQuantity: string;
    receivedQuantity: string;
    remainingQuantity: string;
    taxAmount: string;
    unitPrice: string;
    uom: string;
};

export type PurchaseOrder = {
    balance: string;
    expectedDate: string;
    grandTotal: string;
    id: string;
    lines: PurchaseOrderLine[];
    orderDate: string;
    poNumber: string;
    status: PurchaseOrderStatus;
    supplier: string;
    updatedAt: string;
    workflow: string;
};

export type GoodsReceivedNoteLine = {
    acceptedQuantity: string;
    backendBaseQuantity: string;
    id: string;
    item: string;
    orderedQuantity?: string;
    rejectedQuantity: string;
    sourceLine?: string;
    uom: string;
};

export type GoodsReceivedNote = {
    grnDate: string;
    grnNumber: string;
    id: string;
    inventoryStatus: string;
    lines: GoodsReceivedNoteLine[];
    sourcePo?: string;
    status: GrnStatus;
    supplier: string;
    updatedAt: string;
};

export type PurchaseInvoiceLine = {
    discountAmount: string;
    id: string;
    invoiceQuantity: string;
    item: string;
    lineTotal: string;
    sourceLine?: string;
    taxAmount: string;
    unitPrice: string;
    uom: string;
};

export type PurchaseInvoice = {
    balance: string;
    documentStatus: string;
    dueDate: string;
    grandTotal: string;
    id: string;
    invoiceDate: string;
    invoiceNumber: string;
    lines: PurchaseInvoiceLine[];
    paidAmount: string;
    sourceReference?: string;
    status: PurchaseInvoiceStatus;
    supplier: string;
    updatedAt: string;
};

export type PurchasePaymentAllocation = {
    allocatedAmount: string;
    documentBalanceAfter: string;
    id: string;
    sourceDocument: string;
    status: string;
};

export type PurchasePayment = {
    allocations: PurchasePaymentAllocation[];
    amount: string;
    id: string;
    method: string;
    paymentDate: string;
    paymentNumber: string;
    reference: string;
    status: PurchasePaymentStatus;
    supplier: string;
    unallocatedAmount: string;
};

export type PurchaseAdvance = {
    advanceNumber: string;
    amount: string;
    id: string;
    remainingAmount: string;
    status: string;
    supplier: string;
};

export type PurchaseReturnLine = {
    backendReturnableQuantity: string;
    id: string;
    item: string;
    returnQuantity: string;
    sourceLine: string;
    uom: string;
};

export type PurchaseReturn = {
    id: string;
    returnNumber: string;
    returnTotal: string;
    sourceReference: string;
    status: PurchaseReturnStatus;
    supplier: string;
    updatedAt: string;
    lines: PurchaseReturnLine[];
};

export type SupplierRefund = {
    amount: string;
    id: string;
    method: string;
    refundNumber: string;
    sourceReference: string;
    status: string;
    supplier: string;
};

export type PurchaseSettings = {
    allowDirectInvoice: boolean;
    allowGrnWithoutPo: boolean;
    allowInvoiceWithoutGrn: boolean;
    allowOverReceipt: boolean;
    defaultPayableAccount: string;
    defaultPaymentTerm: string;
    defaultTaxGroup: string;
    defaultWarehouse: string;
    grnSequence: string;
    id: string;
    invoiceDocumentDefinition: string;
    invoiceMatchingRule: string;
    invoiceSequence: string;
    poSequence: string;
    returnSequence: string;
    stockReceiveTiming: string;
};

export type PurchaseCalculationPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        discountTotal: string;
        grandTotal: string;
        subtotal: string;
        taxTotal: string;
        uomConversion: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type PurchaseInventoryEffect = {
    decision: string;
    item: string;
    quantityEffect: string;
    sourceReference: string;
    warehouse: string;
};

export type PurchaseFinancePostingPreview = {
    calculated: {
        apImpact: string;
        eligibility: string;
        journalImpact: string;
        taxImpact: string;
    };
    lines: Array<{ account: string; credit: string; debit: string; description: string }>;
};

export type PurchaseAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
    type: string;
};

export type PurchaseDashboardMetric = {
    label: string;
    status: string;
    value: string;
};
