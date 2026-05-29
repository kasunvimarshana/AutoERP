import type {
    AdvancePayment,
    CashRegister,
    CheckPayment,
    Payment,
    PaymentAllocation,
    PaymentAllocationPreview,
    PaymentAuditEntry,
    PaymentGroup,
    PaymentMethod,
    PaymentPostingPreview,
    PaymentSourceReference,
    Refund,
    WriteOff,
} from '../types/payment.types';

export const paymentDashboardMetrics = [
    { label: 'Pending payments', value: '18', tone: 'warning' },
    { label: 'Posted payments', value: '124', tone: 'success' },
    { label: 'Unallocated payments', value: '9', tone: 'info' },
    { label: 'Advances', value: '14', tone: 'default' },
    { label: 'Refunds', value: '5', tone: 'danger' },
    { label: 'Pending checks', value: '7', tone: 'warning' },
];

export const payments: Payment[] = [
    {
        allocatedAmount: 'Backend calculated',
        amount: '185,000.00',
        currency: 'LKR',
        direction: 'customer_receipt',
        id: 'pay-001',
        methodName: 'Bank Transfer',
        party: 'Kavinda Motors',
        partyType: 'customer',
        paymentDate: '2026-05-23',
        paymentNumber: 'RCPT-2026-00041',
        reference: 'BANK/TRN/8891',
        sourceModule: 'sales',
        sourceReference: 'SINV-2026-00119',
        status: 'posted',
        unallocatedAmount: 'Backend calculated',
        updatedAt: '2026-05-23 14:10',
    },
    {
        allocatedAmount: 'Backend calculated',
        amount: '92,500.00',
        currency: 'LKR',
        direction: 'supplier_payment',
        id: 'pay-002',
        methodName: 'Check',
        party: 'Prime Auto Parts',
        partyType: 'supplier',
        paymentDate: '2026-05-24',
        paymentNumber: 'PAY-2026-00088',
        reference: 'CHK-004812',
        sourceModule: 'purchase',
        sourceReference: 'PINV-2026-00072',
        status: 'pending',
        unallocatedAmount: 'Backend calculated',
        updatedAt: '2026-05-24 09:00',
    },
    {
        allocatedAmount: 'Backend calculated',
        amount: '45,000.00',
        currency: 'LKR',
        direction: 'generic_receipt',
        id: 'pay-003',
        methodName: 'Cash',
        party: 'Walk-in Service Customer',
        partyType: 'other',
        paymentDate: '2026-05-25',
        paymentNumber: 'RCPT-2026-00042',
        reference: 'CASH-D1',
        sourceModule: 'vehicle_service',
        sourceReference: 'JOB-2026-00512',
        status: 'partially_allocated',
        unallocatedAmount: 'Backend calculated',
        updatedAt: '2026-05-25 11:40',
    },
    {
        allocatedAmount: 'Backend calculated',
        amount: '250,000.00',
        currency: 'LKR',
        direction: 'customer_receipt',
        id: 'pay-004',
        methodName: 'Card',
        party: 'Fleet Lanka',
        partyType: 'customer',
        paymentDate: '2026-05-26',
        paymentNumber: 'ADV-REC-2026-00012',
        reference: 'CARD/9021',
        sourceModule: 'vehicle_rental',
        sourceReference: 'AGR-2026-00034',
        status: 'fully_allocated',
        unallocatedAmount: 'Backend calculated',
        updatedAt: '2026-05-26 16:22',
    },
    {
        allocatedAmount: 'Backend calculated',
        amount: '62,000.00',
        currency: 'LKR',
        direction: 'generic_payment',
        id: 'pay-005',
        methodName: 'Online Transfer',
        party: 'Insurance Refund',
        partyType: 'other',
        paymentDate: '2026-05-27',
        paymentNumber: 'PAY-2026-00089',
        reference: 'REV-REQUESTED',
        status: 'reversed',
        unallocatedAmount: 'Backend calculated',
        updatedAt: '2026-05-27 13:05',
    },
];

export const paymentMethods: PaymentMethod[] = [
    { accountName: 'Main Cash', code: 'CASH', id: 'method-001', isActive: true, name: 'Cash', type: 'cash' },
    { accountName: 'Commercial Bank Current', code: 'BANK', id: 'method-002', isActive: true, name: 'Bank Transfer', type: 'bank_transfer' },
    { accountName: 'Card Clearing', code: 'CARD', id: 'method-003', isActive: true, name: 'Card', type: 'card' },
    { accountName: 'Checks Clearing', code: 'CHECK', id: 'method-004', isActive: true, name: 'Check', type: 'check' },
    { accountName: 'Online Gateway', code: 'ONLINE', id: 'method-005', isActive: false, name: 'Online Transfer', type: 'online' },
];

export const paymentGroups: PaymentGroup[] = [
    { direction: 'inbound', groupType: 'bank_deposit', id: 'group-001', reference: 'DEP-2026-051', status: 'posted', totalAmount: 'Backend provided', transactionNumber: 'PGRP-2026-0008' },
    { direction: 'outbound', groupType: 'batch_supplier', id: 'group-002', reference: 'SUP-BATCH-MAY', status: 'draft', totalAmount: 'Backend provided', transactionNumber: 'PGRP-2026-0009' },
];

export const paymentAllocations: PaymentAllocation[] = [
    { allocatedAmount: 'Backend calculated', allocationDate: '2026-05-23', documentNumber: 'SINV-2026-00119', documentType: 'sales_invoice', id: 'alloc-001', paymentId: 'pay-001', reference: 'Source allocation', status: 'active' },
    { allocatedAmount: 'Backend calculated', allocationDate: '2026-05-24', documentNumber: 'PINV-2026-00072', documentType: 'purchase_invoice', id: 'alloc-002', paymentId: 'pay-002', reference: 'Pending check settlement', status: 'active' },
];

export const allocationPreview: PaymentAllocationPreview = {
    breakdown: [
        { label: 'Payment amount', value: 'Backend preview' },
        { label: 'Requested targets', value: 'Backend preview' },
        { label: 'Settlement policy', value: 'Backend preview' },
    ],
    calculated: {
        allocatedAmount: 'Backend calculated',
        remainingUnallocatedAmount: 'Backend calculated',
        targetRemainingBalance: 'Backend calculated',
    },
    errors: [],
    input: {},
    warnings: ['Preview values are returned by backend/mock service.'],
};

export const advancePayments: AdvancePayment[] = [
    { advanceDate: '2026-05-18', advanceNumber: 'CADV-2026-00014', amount: '120,000.00', currency: 'LKR', id: 'adv-001', party: 'Fleet Lanka', partyType: 'customer', remainingAmount: 'Backend calculated', status: 'open', type: 'customer' },
    { advanceDate: '2026-05-20', advanceNumber: 'SADV-2026-00007', amount: '80,000.00', currency: 'LKR', id: 'adv-002', party: 'Prime Auto Parts', partyType: 'supplier', remainingAmount: 'Backend calculated', status: 'partially_applied', type: 'supplier' },
];

export const refunds: Refund[] = [
    { amount: 'Backend calculated', id: 'refund-001', methodName: 'Bank Transfer', paymentNumber: 'RCPT-2026-00039', reason: 'Customer overpayment', refundDate: '2026-05-25', status: 'pending' },
    { amount: 'Backend calculated', id: 'refund-002', methodName: 'Cash', paymentNumber: 'RCPT-2026-00031', reason: 'Cancelled service booking', refundDate: '2026-05-26', status: 'posted' },
];

export const writeOffs: WriteOff[] = [
    { amount: 'Backend calculated', documentNumber: 'SINV-2026-00102', documentType: 'sales_invoice', id: 'wo-001', reason: 'Approved rounding/settlement difference', reference: 'WO-MAY-01', status: 'approved' },
    { amount: 'Backend calculated', documentNumber: 'PINV-2026-00065', documentType: 'purchase_invoice', id: 'wo-002', reason: 'Vendor settlement variance', reference: 'WO-MAY-02', status: 'draft' },
];

export const cashRegisters: CashRegister[] = [
    { assignedUser: 'Front Desk', code: 'CR-01', currentBalance: 'Backend provided', id: 'cash-001', name: 'Main Counter Register', openingBalance: 'Backend provided', status: 'open' },
    { assignedUser: 'Service Desk', code: 'CR-02', currentBalance: 'Backend provided', id: 'cash-002', name: 'Service Counter Register', openingBalance: 'Backend provided', status: 'closed' },
];

export const checks: CheckPayment[] = [
    { amount: '92,500.00', bank: 'Commercial Bank', checkNumber: 'CHK-004812', dueDate: '2026-06-03', id: 'check-001', linkedPayment: 'PAY-2026-00088', party: 'Prime Auto Parts', status: 'pending', type: 'outbound' },
    { amount: '210,000.00', bank: 'Sampath Bank', checkNumber: 'CHK-778210', dueDate: '2026-06-07', id: 'check-002', linkedPayment: 'RCPT-2026-00038', party: 'Fleet Lanka', status: 'deposited', type: 'inbound' },
];

export const sourceReferences: PaymentSourceReference[] = [
    { id: 'src-pay-001', label: 'Sales invoice', sourceModule: 'sales', sourceReference: 'SINV-2026-00119', sourceType: 'sales_invoice' },
    { id: 'src-pay-002', label: 'Supplier invoice', sourceModule: 'purchase', sourceReference: 'PINV-2026-00072', sourceType: 'purchase_invoice' },
    { id: 'src-pay-003', label: 'Service invoice', sourceModule: 'vehicle_service', sourceReference: 'SINV-SVC-2026-00512', sourceType: 'service_invoice' },
];

export const postingPreview: PaymentPostingPreview = {
    breakdown: [
        { label: 'Posting mode', value: 'Backend preview' },
        { label: 'Settlement effect', value: 'Backend preview' },
    ],
    errors: [],
    journalImpact: [
        { account: 'Cash/Bank account', amount: 'Backend calculated', direction: 'Debit/Credit by backend' },
        { account: 'Receivable/Payable clearing', amount: 'Backend calculated', direction: 'Debit/Credit by backend' },
    ],
    warnings: ['Finance posting is preview-only here and must be confirmed by backend.'],
};

export const paymentActivity: PaymentAuditEntry[] = [
    { actor: 'Cashier', description: 'Payment captured as draft.', id: 'act-001', time: '2026-05-23 13:50' },
    { actor: 'System', description: 'Backend posting preview requested.', id: 'act-002', time: '2026-05-23 14:00' },
    { actor: 'Finance Manager', description: 'Payment posted through backend workflow.', id: 'act-003', time: '2026-05-23 14:10' },
];

export function getPaymentById(paymentId: string) {
    return payments.find((payment) => payment.id === paymentId) ?? payments[0];
}
