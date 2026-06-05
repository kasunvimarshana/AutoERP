import type {
    Voucher,
    VoucherDashboardMetric,
    VoucherPaymentImpactPreview,
    VoucherPostingPreview,
    VoucherSettings,
    VoucherType,
} from '../types/voucher.types';

export const voucherDashboardMetrics: VoucherDashboardMetric[] = [
    { label: 'Draft vouchers', tone: 'default', value: '12' },
    { label: 'Submitted', tone: 'warning', value: '7' },
    { label: 'Approved awaiting post', tone: 'info', value: '5' },
    { label: 'Posted vouchers', tone: 'success', value: '31' },
    { label: 'Rejected', tone: 'danger', value: '2' },
    { label: 'Reversed', tone: 'default', value: '3' },
];

export const voucherTypes: VoucherType[] = [
    { activeFlag: true, category: 'cash_bank', code: 'PAY-VOU', defaultDocumentDefinition: 'payment_voucher_default', defaultSequence: 'PV-{YYYY}-{####}', direction: 'payment', id: 'vtype-001', name: 'Payment Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: true, status: 'active', updatedAt: '2026-05-29' },
    { activeFlag: true, category: 'cash_bank', code: 'REC-VOU', defaultDocumentDefinition: 'receipt_voucher_default', defaultSequence: 'RV-{YYYY}-{####}', direction: 'receipt', id: 'vtype-002', name: 'Receipt Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: true, status: 'active', updatedAt: '2026-05-29' },
    { activeFlag: true, category: 'journal', code: 'JRN-VOU', defaultDocumentDefinition: 'journal_voucher_default', defaultSequence: 'JV-{YYYY}-{####}', direction: 'journal', id: 'vtype-003', name: 'Journal Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: false, status: 'active', updatedAt: '2026-05-29' },
    { activeFlag: true, category: 'contra', code: 'CONTRA', defaultDocumentDefinition: 'contra_voucher_default', defaultSequence: 'CV-{YYYY}-{####}', direction: 'contra', id: 'vtype-004', name: 'Contra Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: true, status: 'active', updatedAt: '2026-05-29' },
    { activeFlag: true, category: 'expense', code: 'EXP-VOU', defaultDocumentDefinition: 'expense_voucher_default', defaultSequence: 'EV-{YYYY}-{####}', direction: 'payment', id: 'vtype-005', name: 'Expense Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: true, status: 'active', updatedAt: '2026-05-28' },
    { activeFlag: true, category: 'advance', code: 'ADV-VOU', defaultDocumentDefinition: 'advance_voucher_default', defaultSequence: 'AV-{YYYY}-{####}', direction: 'payment', id: 'vtype-006', name: 'Advance Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: true, status: 'active', updatedAt: '2026-05-28' },
    { activeFlag: true, category: 'refund', code: 'REF-VOU', defaultDocumentDefinition: 'refund_voucher_default', defaultSequence: 'RFV-{YYYY}-{####}', direction: 'receipt', id: 'vtype-007', name: 'Refund Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: true, status: 'active', updatedAt: '2026-05-28' },
    { activeFlag: true, category: 'write_off', code: 'WO-VOU', defaultDocumentDefinition: 'write_off_voucher_default', defaultSequence: 'WOV-{YYYY}-{####}', direction: 'adjustment', id: 'vtype-008', name: 'Write-off Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: false, status: 'active', updatedAt: '2026-05-27' },
    { activeFlag: true, category: 'adjustment', code: 'ADJ-VOU', defaultDocumentDefinition: 'adjustment_voucher_default', defaultSequence: 'AJV-{YYYY}-{####}', direction: 'adjustment', id: 'vtype-009', name: 'Adjustment Voucher', requiresApproval: true, requiresBalancedLines: true, requiresPaymentMethod: false, status: 'active', updatedAt: '2026-05-27' },
];

export const postingPreview: VoucherPostingPreview = {
    breakdown: [
        { account: 'Cash / Bank', effect: 'Backend posting preview' },
        { account: 'Expense / Payable / Receivable', effect: 'Backend posting preview' },
    ],
    calculated: {
        balanced: 'Backend validated',
        creditTotal: 'Backend calculated',
        debitTotal: 'Backend calculated',
        eligibility: 'Backend checks fiscal period, status, approval, and accounts',
        journalImpact: 'Backend journal preview',
    },
    errors: [],
    input: { sourceModule: 'voucher' },
    warnings: ['Frontend does not calculate debit/credit balance.'],
};

export const paymentImpactPreview: VoucherPaymentImpactPreview = {
    breakdown: [
        { label: 'Payment method', value: 'Backend validated' },
        { label: 'Allocation target', value: 'Backend checked' },
        { label: 'Settlement', value: 'Backend preview' },
    ],
    calculated: {
        allocationBalance: 'Backend calculated',
        paymentImpact: 'Backend payment/receipt impact',
        paymentMethodValidation: 'Backend validation',
        settlementStatus: 'Backend workflow status',
    },
    errors: [],
    input: { sourceModule: 'voucher' },
    warnings: [],
};

const activity = [
    { actor: 'Kasun Perera', id: 'act-001', note: 'Voucher created as draft.', timestamp: '2026-05-29 09:00', type: 'created' },
    { actor: 'System', id: 'act-002', note: 'Posting preview generated by backend/mock contract.', timestamp: '2026-05-29 09:12', type: 'preview' },
    { actor: 'Nimal Fernando', id: 'act-003', note: 'Approval workflow action recorded.', timestamp: '2026-05-29 10:30', type: 'approval' },
];

export const vouchers: Voucher[] = [
    {
        activity,
        allocations: [
            { allocatedAmount: 'Backend calculated', id: 'alloc-001', status: 'preview', targetModule: 'payment', targetReference: 'PAY-MOCK-001', targetType: 'payment_request' },
        ],
        approvalStatus: 'pending',
        approvals: [
            { actor: 'Nimal Fernando', id: 'appr-001', level: '1', remarks: 'Awaiting review', status: 'pending', timestamp: 'Backend timestamp' },
        ],
        currency: 'LKR',
        description: 'Expense settlement voucher with backend posting/payment preview.',
        document: { documentNumber: 'VDOC-MOCK-001', status: 'Preview only', template: 'Payment voucher' },
        id: 'vou-001',
        lines: [
            { account: 'Office expenses', amountPreview: 'Backend calculated', costCenter: 'Admin', creditAmount: 'Backend value', debitAmount: 'Backend value', description: 'Expense line', id: 'line-001', lineNo: '1', party: 'Supplier / Employee', sourceReference: 'Manual', taxPreview: 'Backend calculated' },
            { account: 'Cash / Bank', amountPreview: 'Backend calculated', costCenter: 'Treasury', creditAmount: 'Backend value', debitAmount: 'Backend value', description: 'Payment line', id: 'line-002', lineNo: '2', party: 'Supplier / Employee', sourceReference: 'Manual', taxPreview: 'Backend calculated' },
        ],
        party: 'City Office Supplies',
        partyType: 'supplier',
        paymentImpact: paymentImpactPreview,
        paymentMethod: 'Bank Transfer',
        paymentStatus: 'pending',
        postingPreview,
        postingStatus: 'previewed',
        referenceNumber: 'EXP-REF-001',
        sourceReference: { sourceId: 'manual-001', sourceModule: 'voucher', sourceNumber: 'EXP-REF-001', sourceType: 'manual' },
        status: 'submitted',
        totalAmount: 'Backend calculated',
        totalCredit: 'Backend calculated',
        totalDebit: 'Backend calculated',
        updatedAt: '2026-05-29',
        availableActions: [
            { action: 'approve', label: 'Approve' },
            { action: 'reject', label: 'Reject', tone: 'danger' },
            { action: 'preview-posting', label: 'Preview Posting' },
        ],
        voucherDate: '2026-05-29',
        voucherNumber: 'PV-MOCK-001',
        voucherType: 'Payment Voucher',
    },
    {
        activity,
        allocations: [],
        approvalStatus: 'approved',
        approvals: [
            { actor: 'Kasun Perera', id: 'appr-002', level: '1', remarks: 'Approved', status: 'approved', timestamp: '2026-05-29 11:00' },
        ],
        currency: 'LKR',
        description: 'Receipt voucher for customer settlement.',
        document: { documentNumber: 'VDOC-MOCK-002', status: 'Generated by backend placeholder', template: 'Receipt voucher' },
        id: 'vou-002',
        lines: [
            { account: 'Cash / Bank', amountPreview: 'Backend calculated', costCenter: 'Treasury', creditAmount: 'Backend value', debitAmount: 'Backend value', description: 'Receipt line', id: 'line-003', lineNo: '1', party: 'Customer', sourceReference: 'Customer receipt', taxPreview: 'Backend calculated' },
        ],
        party: 'Northline Logistics',
        partyType: 'customer',
        paymentImpact: paymentImpactPreview,
        paymentMethod: 'Cash',
        paymentStatus: 'linked',
        postingPreview,
        postingStatus: 'posted',
        referenceNumber: 'REC-REF-002',
        sourceReference: { sourceId: 'payment-002', sourceModule: 'payment', sourceNumber: 'PAY-MOCK-002', sourceType: 'receipt' },
        status: 'posted',
        totalAmount: 'Backend calculated',
        totalCredit: 'Backend calculated',
        totalDebit: 'Backend calculated',
        updatedAt: '2026-05-29',
        availableActions: [
            { action: 'reverse', label: 'Reverse', tone: 'danger' },
            { action: 'document', label: 'View Document' },
        ],
        voucherDate: '2026-05-29',
        voucherNumber: 'RV-MOCK-002',
        voucherType: 'Receipt Voucher',
    },
    {
        activity,
        allocations: [],
        approvalStatus: 'approved',
        approvals: [],
        currency: 'LKR',
        description: 'Journal voucher adjustment.',
        document: { documentNumber: 'VDOC-MOCK-003', status: 'Preview only', template: 'Journal voucher' },
        id: 'vou-003',
        lines: [
            { account: 'Accrued expense', amountPreview: 'Backend calculated', costCenter: 'Finance', creditAmount: 'Backend value', debitAmount: 'Backend value', description: 'Accrual', id: 'line-004', lineNo: '1', party: 'Internal', sourceReference: 'Finance adjustment', taxPreview: 'Backend rule' },
        ],
        party: 'Internal',
        partyType: 'internal',
        paymentImpact: paymentImpactPreview,
        paymentMethod: 'Not required',
        paymentStatus: 'not_applicable',
        postingPreview,
        postingStatus: 'reversed',
        referenceNumber: 'JRN-REF-003',
        sourceReference: { sourceId: 'finance-003', sourceModule: 'finance', sourceNumber: 'JRN-REF-003', sourceType: 'adjustment' },
        status: 'reversed',
        totalAmount: 'Backend calculated',
        totalCredit: 'Backend calculated',
        totalDebit: 'Backend calculated',
        updatedAt: '2026-05-28',
        availableActions: [
            { action: 'history', label: 'View History' },
        ],
        voucherDate: '2026-05-28',
        voucherNumber: 'JV-MOCK-003',
        voucherType: 'Journal Voucher',
    },
];

export const voucherSettings: VoucherSettings = {
    allowDirectPosting: false,
    allowPartialAllocation: true,
    defaultDocumentDefinition: 'voucher_default',
    defaultPaymentMethod: 'Bank Transfer',
    defaultSequence: 'VOU-{YYYY}-{####}',
    requireApproval: true,
};

export function getVoucherById(id: string) {
    return vouchers.find((voucher) => voucher.id === id || voucher.voucherNumber === id) ?? vouchers[0];
}

export function getVoucherTypeById(id: string) {
    return voucherTypes.find((type) => type.id === id || type.code === id) ?? voucherTypes[0];
}
