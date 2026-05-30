import type {
    CustomerAdvance,
    CustomerRefund,
    GoodsDeliveryNote,
    SalesAuditEntry,
    SalesCalculationPreview,
    SalesCreditCheckResult,
    SalesDashboardMetric,
    SalesFinancePostingPreview,
    SalesInventoryEffect,
    SalesInvoice,
    SalesOrder,
    SalesQuotation,
    SalesReturn,
    SalesSettings,
    SalesStockAvailabilityPreview,
} from '../types/sales.types';

export const salesDashboardMetrics: SalesDashboardMetric[] = [
    { label: 'Open Sales Orders', status: 'Backend Read Model', value: '24' },
    { label: 'Deliveries Awaiting Invoice', status: 'Backend Read Model', value: '9' },
    { label: 'Invoices Awaiting Payment', status: 'Backend Read Model', value: '15' },
    { label: 'Returns In Progress', status: 'Backend Read Model', value: '4' },
];

export const salesQuotations: SalesQuotation[] = [
    {
        customer: 'Northline Logistics',
        expiryDate: '2026-06-15',
        grandTotal: 'Backend calculated',
        id: 'sq-001',
        lines: [
            { discountAmount: 'Backend calculated', id: 'sql-001', item: 'Fleet Maintenance Package', lineTotal: 'Backend calculated', quantity: '1 Package', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'Package' },
        ],
        quotationDate: '2026-05-28',
        quotationNumber: 'SQ-2026-0004',
        status: 'sent',
        updatedAt: '2026-05-29 08:40',
    },
];

export const salesOrders: SalesOrder[] = [
    {
        balance: 'Backend calculated',
        customer: 'Northline Logistics',
        expectedDate: '2026-06-03',
        grandTotal: 'LKR 628,000.00',
        id: 'so-001',
        lines: [
            { backendConvertedQuantity: '12 PCS', deliveredQuantity: '6 PCS', discountAmount: 'Backend calculated', id: 'sol-001', item: 'Oil Filter Kit', lineTotal: 'Backend calculated', orderedQuantity: '1 BOX', remainingQuantity: 'Backend calculated', stockAvailability: 'Backend checked', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'BOX' },
            { backendConvertedQuantity: '40 L', deliveredQuantity: '20 L', discountAmount: 'Backend calculated', id: 'sol-002', item: 'Synthetic Engine Oil', lineTotal: 'Backend calculated', orderedQuantity: '40 L', remainingQuantity: 'Backend calculated', stockAvailability: 'Backend checked', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'L' },
        ],
        orderDate: '2026-05-24',
        soNumber: 'SO-2026-0001',
        status: 'partially_delivered',
        updatedAt: '2026-05-29 09:45',
        workflow: 'Flexible: SO -> Delivery -> Customer Invoice -> Payment',
    },
    {
        balance: 'Backend calculated',
        customer: 'Metro Rent-a-Car',
        expectedDate: '2026-06-07',
        grandTotal: 'LKR 214,000.00',
        id: 'so-002',
        lines: [
            { backendConvertedQuantity: '2 Service', deliveredQuantity: '0', discountAmount: 'Backend calculated', id: 'sol-003', item: 'Express Inspection Service', lineTotal: 'Backend calculated', orderedQuantity: '2 Service', remainingQuantity: 'Backend calculated', stockAvailability: 'No stock impact', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'Service' },
        ],
        orderDate: '2026-05-28',
        soNumber: 'SO-2026-0002',
        status: 'approved',
        updatedAt: '2026-05-29 14:30',
        workflow: 'SO -> Customer Invoice allowed by setting',
    },
    {
        balance: 'Backend calculated',
        customer: 'Summit Fleet Services',
        expectedDate: '2026-05-30',
        grandTotal: 'LKR 86,400.00',
        id: 'so-003',
        lines: [
            { backendConvertedQuantity: '24 PCS', deliveredQuantity: '24 PCS', discountAmount: 'Backend calculated', id: 'sol-004', item: 'Brake Pads', lineTotal: 'Backend calculated', orderedQuantity: '24 PCS', remainingQuantity: 'Backend calculated', stockAvailability: 'Backend checked', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'PCS' },
        ],
        orderDate: '2026-05-20',
        soNumber: 'SO-2026-0003',
        status: 'delivered',
        updatedAt: '2026-05-28 16:55',
        workflow: 'Fully delivered, awaiting invoice match',
    },
];

export const gdns: GoodsDeliveryNote[] = [
    {
        customer: 'Northline Logistics',
        deliveryDate: '2026-05-29',
        gdnNumber: 'GDN-2026-0007',
        id: 'gdn-001',
        inventoryStatus: 'Stock issue preview returned by backend',
        lines: [
            { backendBaseQuantity: 'Backend converted', deliveredQuantity: '6 PCS', id: 'gdnl-001', item: 'Oil Filter Kit', orderedQuantity: '1 BOX', pickedQuantity: '6 PCS', rejectedQuantity: '0', sourceLine: 'SO-2026-0001 / line 1', uom: 'PCS' },
            { backendBaseQuantity: 'Backend converted', deliveredQuantity: '20 L', id: 'gdnl-002', item: 'Synthetic Engine Oil', orderedQuantity: '40 L', pickedQuantity: '20 L', rejectedQuantity: '0', sourceLine: 'SO-2026-0001 / line 2', uom: 'L' },
        ],
        sourceOrder: 'SO-2026-0001',
        status: 'posted',
        updatedAt: '2026-05-29 15:20',
    },
    {
        customer: 'Walk-in Customer',
        deliveryDate: '2026-05-30',
        gdnNumber: 'GDN-2026-0008',
        id: 'gdn-002',
        inventoryStatus: 'Draft, no stock issue yet',
        lines: [
            { backendBaseQuantity: 'Backend converted', deliveredQuantity: '8 PCS', id: 'gdnl-003', item: 'Wiper Blade Set', pickedQuantity: '8 PCS', rejectedQuantity: '0', sourceLine: 'Direct delivery', uom: 'PCS' },
        ],
        status: 'draft',
        updatedAt: '2026-05-30 08:20',
    },
];

export const salesInvoices: SalesInvoice[] = [
    {
        balance: 'Backend calculated',
        customer: 'Northline Logistics',
        documentStatus: 'Generated by Document module',
        dueDate: '2026-06-28',
        grandTotal: 'LKR 314,000.00',
        id: 'sinv-001',
        invoiceDate: '2026-05-29',
        invoiceNumber: 'SINV-2026-0042',
        lines: [
            { discountAmount: 'Backend calculated', id: 'sinvl-001', invoiceQuantity: '6 PCS', item: 'Oil Filter Kit', lineTotal: 'Backend calculated', sourceLine: 'GDN-2026-0007 / line 1', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'PCS' },
            { discountAmount: 'Backend calculated', id: 'sinvl-002', invoiceQuantity: '20 L', item: 'Synthetic Engine Oil', lineTotal: 'Backend calculated', sourceLine: 'GDN-2026-0007 / line 2', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'L' },
        ],
        paidAmount: 'Backend calculated',
        sourceReference: 'GDN-2026-0007',
        status: 'partially_paid',
        updatedAt: '2026-05-30 10:15',
    },
    {
        balance: 'Backend calculated',
        customer: 'Metro Rent-a-Car',
        documentStatus: 'Pending generation',
        dueDate: '2026-06-14',
        grandTotal: 'LKR 118,000.00',
        id: 'sinv-002',
        invoiceDate: '2026-05-30',
        invoiceNumber: 'SINV-2026-0043',
        lines: [
            { discountAmount: 'Backend calculated', id: 'sinvl-003', invoiceQuantity: '1 Service', item: 'Express Inspection Service', lineTotal: 'Backend calculated', sourceLine: 'Direct invoice', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'Service' },
        ],
        paidAmount: 'Backend calculated',
        status: 'draft',
        updatedAt: '2026-05-30 11:55',
    },
];

export const salesPayments = [
    {
        allocations: [
            { allocatedAmount: 'Backend allocated', documentBalanceAfter: 'Backend calculated', id: 'sa-001', sourceDocument: 'SINV-2026-0042', status: 'active' },
        ],
        amount: 'LKR 150,000.00',
        customer: 'Northline Logistics',
        id: 'spay-001',
        method: 'Bank Transfer',
        paymentDate: '2026-05-30',
        paymentNumber: 'CREC-2026-0012',
        reference: 'BANK-REC-4482',
        status: 'allocated' as const,
        unallocatedAmount: 'Backend calculated',
    },
];

export const customerAdvances: CustomerAdvance[] = [
    { advanceNumber: 'CADV-2026-0003', amount: 'LKR 95,000.00', customer: 'Summit Fleet Services', id: 'cadv-001', remainingAmount: 'Backend calculated', status: 'posted' },
];

export const salesReturns: SalesReturn[] = [
    {
        customer: 'Summit Fleet Services',
        id: 'sret-001',
        lines: [
            { backendReturnableQuantity: 'Backend calculated', id: 'sretl-001', item: 'Brake Pads', returnQuantity: '2 PCS', sourceLine: 'GDN-2026-0006 / line 2', uom: 'PCS' },
        ],
        returnNumber: 'SRET-2026-0002',
        returnTotal: 'Backend calculated',
        sourceReference: 'SINV-2026-0039',
        status: 'posted',
        updatedAt: '2026-05-29 17:45',
    },
];

export const customerRefunds: CustomerRefund[] = [
    { amount: 'Backend calculated', customer: 'Summit Fleet Services', id: 'cref-001', method: 'Credit note / bank', refundNumber: 'CREF-2026-0001', sourceReference: 'SRET-2026-0002', status: 'pending' },
];

export const salesSettings: SalesSettings = {
    allowDeliveryWithoutOrder: true,
    allowDirectInvoice: true,
    allowInvoiceWithoutDelivery: true,
    allowNegativeStock: false,
    creditCheckBehavior: 'Warn and require approval',
    defaultCogsAccount: 'COGS - Parts',
    defaultIncomeAccount: 'Sales Revenue',
    defaultInventoryAccount: 'Inventory - Parts',
    defaultPaymentTerm: 'Net 30',
    defaultReceivableAccount: 'Accounts Receivable - Trade',
    defaultTaxGroup: 'VAT Sales',
    defaultWarehouse: 'Main Parts Store',
    deliverySequence: 'GDN-{YYYY}-{####}',
    id: 'sales-settings',
    invoiceDocumentDefinition: 'Customer Invoice Standard',
    invoiceMatchingRule: 'Delivery match when GDN exists',
    invoiceSequence: 'SINV-{YYYY}-{####}',
    quotationSequence: 'SQ-{YYYY}-{####}',
    returnSequence: 'SRET-{YYYY}-{####}',
    salesOrderSequence: 'SO-{YYYY}-{####}',
    stockDeductionTiming: 'On GDN post',
};

export const invoiceCalculationPreview: SalesCalculationPreview = {
    breakdown: [
        { label: 'Pricing', value: 'Resolved by Pricing module' },
        { label: 'Tax', value: 'Resolved by Finance tax rules' },
        { label: 'UOM', value: 'Converted by UOM module' },
        { label: 'Credit', value: 'Checked by Customer/Sales backend' },
    ],
    calculated: {
        discountTotal: 'Backend calculated',
        grandTotal: 'Backend calculated',
        pricing: 'Backend resolved',
        subtotal: 'Backend calculated',
        taxTotal: 'Backend calculated',
        uomConversion: 'Backend converted quantities returned',
    },
    errors: [],
    input: {},
    warnings: ['Mock preview. Real backend remains authoritative.'],
};

export const inventoryEffects: SalesInventoryEffect[] = [
    { decision: 'Available to post', item: 'Oil Filter Kit', quantityEffect: 'Backend stock issue effect', sourceReference: 'GDN-2026-0007', warehouse: 'Main Parts Store' },
    { decision: 'Return stock movement required', item: 'Brake Pads', quantityEffect: 'Backend stock return effect', sourceReference: 'SRET-2026-0002', warehouse: 'Main Parts Store' },
];

export const stockAvailabilityPreview: SalesStockAvailabilityPreview = {
    calculated: {
        availableQuantity: 'Backend calculated',
        decision: 'Backend decision',
        requestedQuantity: 'Input only',
        reservedQuantity: 'Backend calculated',
    },
    warnings: ['Stock availability preview is backend-owned.'],
};

export const creditCheckPreview: SalesCreditCheckResult = {
    calculated: {
        creditLimit: 'Backend returned',
        currentExposure: 'Backend calculated',
        decision: 'Backend credit decision',
        projectedExposure: 'Backend calculated',
    },
    warnings: ['Credit exposure is not calculated in the frontend.'],
};

export const financePostingPreview: SalesFinancePostingPreview = {
    calculated: {
        arImpact: 'Backend AR impact',
        cogsImpact: 'Backend COGS impact',
        eligibility: 'Backend posting decision',
        journalImpact: 'Backend journal preview',
        taxImpact: 'Backend tax posting',
    },
    lines: [
        { account: 'Accounts Receivable', credit: 'Backend calculated', debit: 'Backend calculated', description: 'Customer receivable effect' },
        { account: 'Sales Revenue', credit: 'Backend calculated', debit: 'Backend calculated', description: 'Revenue effect' },
        { account: 'COGS / Inventory', credit: 'Backend calculated', debit: 'Backend calculated', description: 'COGS and inventory effect' },
    ],
};

export const paymentAllocationPreview = {
    allocatedAmount: 'Backend calculated',
    documentBalanceAfter: 'Backend calculated',
    remainingUnallocated: 'Backend calculated',
    warnings: ['Allocation preview is backend-owned.'],
};

export const documentPreview = {
    documentNumber: 'Backend sequence preview',
    status: 'Rendered by Document module',
    template: 'Customer Invoice Standard',
};

export const salesActivity: SalesAuditEntry[] = [
    { actor: 'Sales Manager', description: 'Sales order approved after credit check warning.', id: 'act-001', time: '2026-05-29 09:45', type: 'workflow' },
    { actor: 'Dispatch Lead', description: 'GDN posted; inventory issue effect requested from Inventory module.', id: 'act-002', time: '2026-05-29 15:20', type: 'inventory' },
    { actor: 'Accounts Receivable', description: 'Customer invoice preview requested; totals returned by backend.', id: 'act-003', time: '2026-05-30 10:15', type: 'finance' },
];

export function getSalesQuotationById(id: string) {
    return salesQuotations.find((quotation) => quotation.id === id) ?? salesQuotations[0];
}

export function getSalesOrderById(id: string) {
    return salesOrders.find((order) => order.id === id) ?? salesOrders[0];
}

export function getGdnById(id: string) {
    return gdns.find((gdn) => gdn.id === id) ?? gdns[0];
}

export function getSalesInvoiceById(id: string) {
    return salesInvoices.find((invoice) => invoice.id === id) ?? salesInvoices[0];
}

export function getSalesPaymentById(id: string) {
    return salesPayments.find((payment) => payment.id === id) ?? salesPayments[0];
}

export function getSalesReturnById(id: string) {
    return salesReturns.find((record) => record.id === id) ?? salesReturns[0];
}
