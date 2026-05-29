export type AccountType = 'asset' | 'liability' | 'equity' | 'income' | 'expense';
export type AccountStatus = 'active' | 'inactive';
export type JournalStatus = 'draft' | 'posted' | 'reversed' | 'voided';

export type Account = {
    accountCode: string;
    accountName: string;
    accountType: AccountType;
    id: string;
    normalBalance: 'debit' | 'credit';
    parentAccount?: string;
    status: AccountStatus;
    updatedAt: string;
};

export type FiscalYear = { endDate: string; id: string; name: string; startDate: string; status: string };
export type FiscalPeriod = { endDate: string; fiscalYear: string; id: string; name: string; startDate: string; status: string };

export type JournalEntryLine = {
    account: string;
    costCenter?: string;
    credit: string;
    debit: string;
    description: string;
    id: string;
    party?: string;
    taxRate?: string;
};

export type JournalEntry = {
    currency: string;
    description: string;
    id: string;
    journalDate: string;
    journalNumber: string;
    lines: JournalEntryLine[];
    reference?: string;
    sourceModule?: string;
    sourceReference?: string;
    status: JournalStatus;
};

export type ApTransaction = { agingBucket: string; dueDate: string; id: string; originalAmount: string; outstandingAmount: string; paidAmount: string; party: string; sourceDocument: string; status: string };
export type ArTransaction = ApTransaction;

export type TaxGroup = { code: string; id: string; name: string; status: string };
export type TaxRate = { code: string; effectiveFrom: string; id: string; name: string; rate: string; status: string };
export type TaxRule = { appliesTo: string; id: string; name: string; priority: string; status: string; taxGroup: string; taxRate: string };
export type TaxPreviewResult = { breakdown: Array<{ label: string; value: string }>; calculated: { appliedRule: string; taxAmount: string; taxableAmount: string }; errors: string[]; input: Record<string, unknown>; warnings: string[] };

export type PaymentTerm = { code: string; dueDays: string; id: string; name: string; status: string };
export type CostCenter = { code: string; id: string; name: string; status: string };
export type BankAccount = { accountName: string; accountNumber: string; bankName: string; currency: string; id: string; status: string };
export type BankTransaction = { amount: string; bankAccount: string; date: string; id: string; reference: string; reconciliationStatus: string; type: string };
export type BankReconciliation = { bankAccount: string; id: string; period: string; status: string; variance: string };

export type BudgetLine = { account: string; budgetAmount: string; id: string; usage: string; variance: string };
export type Budget = { fiscalYear: string; id: string; lines: BudgetLine[]; name: string; status: string; variance: string };

export type FinancePostingPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: { balanced: string; eligibility: string; totalCredit: string; totalDebit: string };
    errors: string[];
    input: Record<string, unknown>;
    journalLines: Array<{ account: string; credit: string; debit: string; description: string }>;
    warnings: string[];
};

export type FinanceSourceReference = { id: string; sourceModule: string; sourceReference: string; sourceType: string };
export type FinanceAuditEntry = { actor: string; description: string; id: string; time: string; type: string };
