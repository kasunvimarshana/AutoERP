export type AccountType = 'asset' | 'liability' | 'equity' | 'income' | 'expense';
export type AccountGroup = 'cash_bank' | 'receivables' | 'payables' | 'inventory' | 'income' | 'expense' | 'equity' | 'tax' | 'other' | '';
export type AccountStatus = 'active' | 'inactive';
export type JournalStatus = 'draft' | 'posted' | 'reversed' | 'voided';

export type FinanceListQuery = {
    search?: string;
    per_page?: number;
    page?: number;
    status?: string;
    type?: string;
    account_group?: string;
    normal_balance?: string;
    is_active?: boolean;
};

export type FinanceDashboardMetric = {
    label: string;
    status: string;
    value: string;
};

export type Account = {
    accountCode: string;
    accountName: string;
    accountType: AccountType;
    accountGroup?: AccountGroup;
    allowsManualPosting: boolean;
    description?: string;
    id: string;
    isBankAccount: boolean;
    isCashAccount: boolean;
    isControlAccount: boolean;
    normalBalance: 'debit' | 'credit';
    parentAccount?: string;
    parentId?: string;
    status: AccountStatus;
    updatedAt: string;
};

export type AccountFormValues = {
    accountCode: string;
    accountName: string;
    accountType: AccountType;
    accountGroup: AccountGroup;
    allowsManualPosting: boolean;
    description: string;
    isBankAccount: boolean;
    isCashAccount: boolean;
    isControlAccount: boolean;
    normalBalance: 'debit' | 'credit';
    parentId: string;
    status: AccountStatus;
};

export type FiscalYear = { endDate: string; id: string; name: string; startDate: string; status: string };
export type FiscalPeriod = { endDate: string; fiscalYear: string; id: string; name: string; startDate: string; status: string };

export type JournalEntryLine = {
    account: string;
    accountId?: string;
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
    entryType: string;
    id: string;
    journalDate: string;
    journalNumber: string;
    lines: JournalEntryLine[];
    reference?: string;
    sourceModule?: string;
    sourceType?: string;
    sourceId?: string;
    sourceReference?: string;
    status: JournalStatus;
    totalCredit: string;
    totalDebit: string;
};

export type JournalLineFormValues = {
    accountId: string;
    credit: string;
    debit: string;
    description: string;
};

export type JournalEntryFormValues = {
    description: string;
    entryType: string;
    journalDate: string;
    journalNumber: string;
    lines: JournalLineFormValues[];
    sourceModule: string;
    sourceReference: string;
    sourceType: string;
    status: JournalStatus;
};

export type ApTransaction = { agingBucket: string; dueDate: string; id: string; originalAmount: string; outstandingAmount: string; paidAmount: string; party: string; sourceDocument: string; status: string };
export type ArTransaction = ApTransaction;

export type TaxGroup = { code: string; id: string; name: string; status: string };
export type TaxRate = { code: string; effectiveFrom: string; id: string; name: string; rate: string; status: string };
export type TaxRule = { appliesTo: string; id: string; name: string; priority: string; status: string; taxGroup: string; taxRate: string };
export type TaxPreviewResult = { breakdown: Array<{ label: string; value: string }>; calculated: { appliedRule: string; taxAmount: string; taxableAmount: string }; errors: string[]; input: Record<string, unknown>; warnings: string[] };

export type TaxGroupFormValues = { name: string; status: AccountStatus };
export type TaxRateFormValues = { effectiveFrom: string; name: string; rate: string; status: AccountStatus; taxGroupId: string; type: string };
export type TaxRuleFormValues = { applies_to?: string; is_active?: boolean; name: string; priority?: number; tax_group_id?: number; tax_rate_id?: number };

export type PaymentTerm = { code: string; dueDays: string; id: string; name: string; status: string };
export type PaymentTermFormValues = { dueDays: string; name: string; status: AccountStatus };
export type CostCenter = { code: string; id: string; name: string; status: string };
export type CostCenterFormValues = { code: string; name: string; status: AccountStatus };
export type BankAccount = { accountName: string; accountNumber: string; bankName: string; currency: string; id: string; status: string };
export type BankAccountFormValues = { account_id: number; account_number: string; bank_name: string; is_active: boolean; name: string };
export type BankTransaction = { amount: string; bankAccount: string; date: string; id: string; reference: string; reconciliationStatus: string; type: string };
export type BankReconciliation = { bankAccount: string; id: string; period: string; status: string; variance: string };

export type BudgetLine = { account: string; budgetAmount: string; id: string; usage: string; variance: string };
export type Budget = { fiscalYear: string; id: string; lines: BudgetLine[]; name: string; status: string; variance: string };
export type BudgetFormValues = { fiscalYearId: string; name: string; status: string };
export type BudgetUsage = { budgetAmount: string; id: string; usedAmount: string; varianceAmount: string };

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
