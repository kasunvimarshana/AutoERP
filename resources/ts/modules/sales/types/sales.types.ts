export type SalesQuotationStatus = 'draft' | 'sent' | 'accepted' | 'converted' | 'expired' | 'cancelled';
export type SalesOrderStatus = 'draft' | 'submitted' | 'approved' | 'partially_delivered' | 'delivered' | 'closed' | 'cancelled' | 'reversed';
export type GdnStatus = 'draft' | 'confirmed' | 'picked' | 'delivered' | 'posted' | 'cancelled' | 'reversed';
export type SalesInvoiceStatus = 'draft' | 'posted' | 'partially_paid' | 'paid' | 'cancelled' | 'reversed';
export type SalesReturnStatus = 'draft' | 'approved' | 'posted' | 'refunded' | 'cancelled' | 'reversed';
export type SalesPaymentStatus = 'draft' | 'posted' | 'allocated' | 'voided' | 'reversed';

export type SalesSourceReference = {
    id: string;
    sourceId?: string;
    sourceModule: string;
    sourceReference: string;
    sourceType: string;
};

export type SalesQuotationLine = {
    discountAmount: string;
    id: string;
    itemId?: string;
    item: string;
    lineTotal: string;
    quantity: string;
    taxAmount: string;
    unitPrice: string;
    uomId?: string;
    uom: string;
};

export type SalesQuotation = {
    customer: string;
    expiryDate: string;
    grandTotal: string;
    id: string;
    quotationDate: string;
    quotationNumber: string;
    lines: SalesQuotationLine[];
    status: SalesQuotationStatus;
    updatedAt: string;
};

export type SalesOrderLine = {
    backendConvertedQuantity: string;
    deliveredQuantity: string;
    discountAmount: string;
    id: string;
    itemId?: string;
    item: string;
    lineTotal: string;
    orderedQuantity: string;
    remainingQuantity: string;
    stockAvailability: string;
    taxAmount: string;
    unitPrice: string;
    uomId?: string;
    uom: string;
};

export type SalesOrder = {
    balance: string;
    creditNoteTotal: string;
    customerId?: string;
    customer: string;
    debitNoteTotal: string;
    expectedDate: string;
    grandTotal: string;
    id: string;
    lines: SalesOrderLine[];
    orderDate: string;
    soNumber: string;
    status: SalesOrderStatus;
    updatedAt: string;
    workflow: string;
    warehouse?: string;
    warehouseId?: string;
};

export type GoodsDeliveryNoteLine = {
    backendBaseQuantity: string;
    deliveredQuantity: string;
    id: string;
    itemId?: string;
    item: string;
    orderedQuantity?: string;
    pickedQuantity: string;
    rejectedQuantity: string;
    sourceLine?: string;
    uomId?: string;
    uom: string;
};

export type GoodsDeliveryNote = {
    creditNoteTotal: string;
    customerId?: string;
    customer: string;
    debitNoteTotal: string;
    deliveryDate: string;
    gdnNumber: string;
    id: string;
    inventoryStatus: string;
    lines: GoodsDeliveryNoteLine[];
    sourceOrder?: string;
    status: GdnStatus;
    updatedAt: string;
    warehouse?: string;
    warehouseId?: string;
};

export type SalesInvoiceLine = {
    discountAmount: string;
    id: string;
    invoiceQuantity: string;
    item: string;
    lineTotal: string;
    sourceLine?: string;
    taxAmount: string;
    unitPrice: string;
    itemId?: string;
    uomId?: string;
    uom: string;
};

export type SalesInvoice = {
    balance: string;
    customerId?: string;
    customer: string;
    documentStatus: string;
    dueDate: string;
    grandTotal: string;
    id: string;
    invoiceDate: string;
    invoiceNumber: string;
    lines: SalesInvoiceLine[];
    paidAmount: string;
    sourceReference?: string;
    status: SalesInvoiceStatus;
    updatedAt: string;
};

export type SalesPaymentAllocation = {
    allocatedAmount: string;
    documentBalanceAfter: string;
    id: string;
    sourceDocument: string;
    status: string;
};

export type SalesPayment = {
    allocations: SalesPaymentAllocation[];
    amount: string;
    customerId?: string;
    customer: string;
    id: string;
    method: string;
    paymentDate: string;
    paymentNumber: string;
    reference: string;
    status: SalesPaymentStatus;
    unallocatedAmount: string;
};

export type CustomerAdvance = {
    advanceNumber: string;
    amount: string;
    customer: string;
    id: string;
    remainingAmount: string;
    status: string;
};

export type SalesReturnLine = {
    backendReturnableQuantity: string;
    id: string;
    itemId?: string;
    item: string;
    returnQuantity: string;
    sourceLine: string;
    uomId?: string;
    uom: string;
};

export type SalesReturn = {
    creditNoteTotal: string;
    customerId?: string;
    customer: string;
    debitNoteTotal: string;
    id: string;
    lines: SalesReturnLine[];
    returnNumber: string;
    returnTotal: string;
    sourceReference: string;
    status: SalesReturnStatus;
    updatedAt: string;
};

export type CustomerRefund = {
    amount: string;
    customer: string;
    id: string;
    method: string;
    refundNumber: string;
    sourceReference: string;
    status: string;
};

export type SalesSettings = {
    allowDeliveryWithoutOrder: boolean;
    allowDirectInvoice: boolean;
    allowInvoiceWithoutDelivery: boolean;
    allowNegativeStock: boolean;
    creditCheckBehavior: string;
    defaultCogsAccount: string;
    defaultIncomeAccount: string;
    defaultInventoryAccount: string;
    defaultPaymentTerm: string;
    defaultReceivableAccount: string;
    defaultTaxGroup: string;
    defaultWarehouse: string;
    deliverySequence: string;
    id: string;
    invoiceDocumentDefinition: string;
    invoiceMatchingRule: string;
    invoiceSequence: string;
    quotationSequence: string;
    returnSequence: string;
    salesOrderSequence: string;
    stockDeductionTiming: string;
};

export type SalesCalculationPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        discountTotal: string;
        grandTotal: string;
        pricing: string;
        subtotal: string;
        taxTotal: string;
        uomConversion: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type SalesInventoryEffect = {
    decision: string;
    item: string;
    quantityEffect: string;
    sourceReference: string;
    warehouse: string;
};

export type SalesStockAvailabilityPreview = {
    calculated: {
        availableQuantity: string;
        decision: string;
        requestedQuantity: string;
        reservedQuantity: string;
    };
    warnings: string[];
};

export type SalesCreditCheckResult = {
    calculated: {
        creditLimit: string;
        currentExposure: string;
        decision: string;
        projectedExposure: string;
    };
    warnings: string[];
};

export type SalesFinancePostingPreview = {
    calculated: {
        arImpact: string;
        cogsImpact: string;
        eligibility: string;
        journalImpact: string;
        taxImpact: string;
    };
    lines: Array<{ account: string; credit: string; debit: string; description: string }>;
};

export type SalesAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
    type: string;
};

export type SalesDashboardMetric = {
    label: string;
    status: string;
    value: string;
};

export type SalesLookupOption = {
    id: string;
    label: string;
    secondary?: string;
};

export type SalesListQuery = {
    page?: number;
    perPage?: number;
    search?: string;
    status?: string;
};

export type SalesLineFormInput = {
    discountType?: string;
    discountValue?: string;
    itemId: string;
    quantity: string;
    unitPrice: string;
    uomId: string;
};

export type SalesOrderFormInput = {
    customerId: string;
    expectedDate?: string;
    lines: SalesLineFormInput[];
    notes?: string;
    orderDate: string;
    soNumber: string;
    status?: string;
    warehouseId: string;
};

export type GdnFormInput = {
    customerId: string;
    deliveryDate: string;
    gdnNumber: string;
    lines: SalesLineFormInput[];
    notes?: string;
    salesOrderId?: string;
    status?: string;
    warehouseId: string;
};

export type SalesInvoiceFormInput = {
    dueDate?: string;
    invoiceDate: string;
    lines: SalesLineFormInput[];
    sourceId: string;
    sourceType: 'gdn_header' | 'sales_order';
    customerReference?: string;
};

export type SalesPaymentFormInput = {
    amount: string;
    customerId: string;
    method: string;
    paymentDate: string;
    reference?: string;
    sourceId?: string;
    sourceType?: string;
};

export type SalesReturnFormInput = {
    customerId: string;
    lines: SalesLineFormInput[];
    notes?: string;
    returnDate: string;
    returnNumber: string;
    returnReason?: string;
    sourceId?: string;
    sourceType?: string;
    status?: string;
};

export type SalesLedgerNote = {
    amount: string;
    id: string;
    noteType: 'credit' | 'debit';
    sourceId: string;
    sourceReference: string;
    sourceType: 'gdn_header' | 'sales_order' | 'sales_return';
    status: string;
    customer: string;
    updatedAt: string;
};
