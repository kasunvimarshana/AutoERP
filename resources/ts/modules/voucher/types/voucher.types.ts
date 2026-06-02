export type VoucherTypeStatus = 'active' | 'inactive';
export type VoucherStatus = 'draft' | 'submitted' | 'approved' | 'rejected' | 'posted' | 'cancelled' | 'reversed';
export type VoucherApprovalStatus = 'not_required' | 'pending' | 'approved' | 'rejected';
export type VoucherPostingStatus = 'draft' | 'previewed' | 'posted' | 'reversed';
export type VoucherPaymentStatus = 'not_applicable' | 'pending' | 'linked' | 'allocated' | 'reversed';
export type VoucherDirection = 'payment' | 'receipt' | 'journal' | 'contra' | 'adjustment';
export type VoucherTypeCategory = 'payment' | 'receipt' | 'journal' | 'contra' | 'expense' | 'advance' | 'refund' | 'write_off' | 'adjustment' | string;

export type VoucherSourceReference = {
    sourceId?: string;
    sourceModule: string;
    sourceNumber?: string;
    sourceType: string;
};

export type VoucherType = {
    activeFlag: boolean;
    category: VoucherTypeCategory;
    code: string;
    defaultDocumentDefinition: string;
    defaultSequence: string;
    direction: VoucherDirection;
    id: string;
    name: string;
    requiresApproval: boolean;
    requiresBalancedLines: boolean;
    requiresPaymentMethod: boolean;
    status: VoucherTypeStatus;
    updatedAt: string;
};

export type VoucherLine = {
    account: string;
    amountPreview: string;
    costCenter: string;
    creditAmount: string;
    debitAmount: string;
    description: string;
    id: string;
    lineNo: string;
    party: string;
    sourceReference: string;
    taxPreview: string;
};

export type VoucherAllocation = {
    allocatedAmount: string;
    id: string;
    status: string;
    targetModule: string;
    targetReference: string;
    targetType: string;
};

export type VoucherApproval = {
    actor: string;
    id: string;
    level: string;
    remarks: string;
    status: string;
    timestamp: string;
};

export type VoucherPostingPreview = {
    breakdown: Array<{ account: string; effect: string }>;
    calculated: {
        balanced: string;
        creditTotal: string;
        debitTotal: string;
        eligibility: string;
        journalImpact: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type VoucherPaymentImpactPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        allocationBalance: string;
        paymentImpact: string;
        paymentMethodValidation: string;
        settlementStatus: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type VoucherDocument = {
    documentNumber: string;
    status: string;
    template: string;
};

export type VoucherAuditEntry = {
    actor: string;
    id: string;
    note: string;
    timestamp: string;
    type: string;
};

export type VoucherWorkflowAction = {
    action: string;
    label: string;
    tone?: 'default' | 'danger' | 'primary';
};

export type Voucher = {
    activity: VoucherAuditEntry[];
    allocations: VoucherAllocation[];
    approvalStatus: VoucherApprovalStatus;
    approvals: VoucherApproval[];
    currency: string;
    description: string;
    document: VoucherDocument;
    id: string;
    lines: VoucherLine[];
    party: string;
    partyType: string;
    paymentImpact: VoucherPaymentImpactPreview;
    paymentMethod: string;
    paymentStatus: VoucherPaymentStatus;
    postingPreview: VoucherPostingPreview;
    postingStatus: VoucherPostingStatus;
    referenceNumber: string;
    sourceReference: VoucherSourceReference;
    status: VoucherStatus;
    totalAmount: string;
    totalCredit: string;
    totalDebit: string;
    updatedAt: string;
    availableActions?: VoucherWorkflowAction[];
    voucherDate: string;
    voucherNumber: string;
    voucherType: string;
};

export type VoucherDashboardMetric = {
    label: string;
    tone: string;
    value: string;
};

export type VoucherSettings = {
    _raw?: Record<string, unknown>;
    allowDirectPosting: boolean;
    allowPartialAllocation: boolean;
    defaultDocumentDefinition: string;
    defaultPaymentMethod: string;
    defaultSequence: string;
    requireApproval: boolean;
};
