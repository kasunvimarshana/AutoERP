export type PaymentStatus = 'draft' | 'pending' | 'posted' | 'partially_allocated' | 'fully_allocated' | 'reconciled' | 'voided' | 'reversed' | 'failed';

export type PaymentDirection = 'customer_receipt' | 'supplier_payment' | 'generic_receipt' | 'generic_payment';

export type PaymentMethodType = 'cash' | 'bank_transfer' | 'card' | 'check' | 'online' | 'other';

export type Payment = {
    allocatedAmount: string;
    amount: string;
    currency: string;
    direction: PaymentDirection;
    id: string;
    methodName: string;
    party: string;
    partyType: string;
    paymentDate: string;
    paymentNumber: string;
    reference?: string;
    sourceModule?: string;
    sourceReference?: string;
    status: PaymentStatus;
    unallocatedAmount: string;
    updatedAt: string;
};

export type PaymentMethod = {
    accountName?: string;
    code: string;
    id: string;
    isActive: boolean;
    name: string;
    type: PaymentMethodType;
};

export type PaymentGroup = {
    direction: string;
    groupType: string;
    id: string;
    reference?: string;
    status: string;
    totalAmount: string;
    transactionNumber: string;
};

export type PaymentAllocationTarget = {
    documentId: string;
    documentNumber: string;
    documentType: string;
    party: string;
    readonlyBalance: string;
};

export type PaymentAllocation = {
    allocatedAmount: string;
    allocationDate: string;
    documentNumber: string;
    documentType: string;
    id: string;
    paymentId: string;
    reference?: string;
    status: string;
};

export type PaymentAllocationPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        allocatedAmount: string;
        remainingUnallocatedAmount: string;
        targetRemainingBalance: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type AdvancePayment = {
    advanceDate: string;
    advanceNumber: string;
    amount: string;
    currency: string;
    id: string;
    party: string;
    partyType: string;
    remainingAmount: string;
    status: string;
    type: string;
};

export type AdvanceAllocation = PaymentAllocation & {
    advancePaymentId: string;
};

export type Refund = {
    amount: string;
    id: string;
    methodName: string;
    paymentNumber: string;
    reason: string;
    refundDate: string;
    status: string;
};

export type WriteOff = {
    amount: string;
    documentNumber: string;
    documentType: string;
    id: string;
    reason: string;
    reference?: string;
    status: string;
};

export type CashRegister = {
    assignedUser: string;
    code: string;
    currentBalance: string;
    id: string;
    name: string;
    openingBalance: string;
    status: string;
};

export type CheckPayment = {
    amount: string;
    bank: string;
    checkNumber: string;
    dueDate: string;
    id: string;
    linkedPayment?: string;
    party: string;
    status: string;
    type: string;
};

export type PaymentSourceReference = {
    id: string;
    label: string;
    sourceModule: string;
    sourceReference: string;
    sourceType: string;
};

export type PaymentPostingPreview = {
    breakdown: Array<{ label: string; value: string }>;
    errors: string[];
    journalImpact: Array<{ account: string; direction: string; amount: string }>;
    warnings: string[];
};

export type PaymentAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
};

export type PaymentFormInput = {
    accountId?: string;
    amount: string;
    currency: string;
    direction: PaymentDirection;
    notes?: string;
    partyId?: string;
    partyType: string;
    paymentDate: string;
    paymentMethodId: string;
    reference?: string;
    sourceId?: string;
    sourceModule?: string;
    sourceReference?: string;
    sourceType?: string;
};
