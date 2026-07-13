export interface FinanceAccount extends Record<string, unknown> {
    id: number;
    code: string;
    name: string;
    description?: string | null;
    normal_balance: 'debit' | 'credit';
    is_control_account: boolean;
    is_posting_account: boolean;
    is_cash_account: boolean;
    is_bank_account: boolean;
    is_tax_account: boolean;
    is_system: boolean;
    is_active: boolean;
    account_type?: FinanceLookup;
    account_category?: FinanceLookup;
    parent?: FinanceLookup | null;
    children?: FinanceAccount[];
    can_edit?: boolean;
}

export interface FinanceLookup extends Record<string, unknown> {
    id: number;
    code: string;
    name: string;
    account_type_id?: number;
    normal_balance?: 'debit' | 'credit';
    statement_type?: string;
    is_posting_account?: boolean;
    is_active?: boolean;
}

export interface FinanceAccountRole extends FinanceLookup {
    description?: string | null;
    is_active: boolean;
}

export interface FinanceAccountAssignment extends Record<string, unknown> {
    id: number;
    organization_unit_id?: number | null;
    account_role_id: number;
    account_id: number;
    effective_from: string;
    effective_to?: string | null;
    is_active: boolean;
    role?: FinanceAccountRole;
    account?: FinanceLookup;
}

export interface PostingProfile extends FinanceLookup {
    organization_unit_id?: number | null;
    scope?: 'tenant_default' | 'organization';
    rules?: Array<{
        id: number;
        line_key: string;
        account_role_id: number;
        effective_from: string;
        effective_to?: string | null;
        is_active: boolean;
        role?: FinanceAccountRole;
        description?: string | null;
    }>;
}

export interface FinanceLookups {
    types: FinanceLookup[];
    categories: FinanceLookup[];
    accounts: FinanceLookup[];
    profiles: PostingProfile[];
    account_roles: FinanceAccountRole[];
    account_assignments: FinanceAccountAssignment[];
    dimensions?: FinanceLookup[];
    bank_accounts?: FinanceLookup[];
}

export interface AccountPayload {
    account_type_id: number | null;
    account_category_id?: number | null;
    parent_id?: number | null;
    code: string;
    name: string;
    description?: string | null;
    normal_balance: 'debit' | 'credit';
    is_control_account: boolean;
    is_posting_account: boolean;
    is_cash_account: boolean;
    is_bank_account: boolean;
    is_tax_account: boolean;
    is_active: boolean;
}

export interface JournalLine extends Record<string, unknown> {
    id?: number;
    line_number: number;
    account_id: number | null;
    account?: FinanceLookup;
    description?: string | null;
    debit: string;
    credit: string;
    dimension_id?: number | null;
}

export interface JournalEntry extends Record<string, unknown> {
    id: number;
    journal_number: string;
    journal_date: string;
    journal_type: string;
    status: string;
    description?: string | null;
    total_debit: string;
    total_credit: string;
    exchange_rate: string;
    source_module?: string | null;
    source_type?: string | null;
    source_id?: number | null;
    source_number?: string | null;
    source_date?: string | null;
    reversal_reason?: string | null;
    posting_profile?: PostingProfile | null;
    lines?: JournalLine[];
    ledger_entries?: LedgerEntry[];
    reversal_of?: Pick<JournalEntry, 'id' | 'journal_number'> | null;
    reversals?: Array<Pick<JournalEntry, 'id' | 'journal_number' | 'status'>>;
    can_edit: boolean;
    can_post: boolean;
    can_cancel: boolean;
    can_reverse: boolean;
}

export interface JournalPayload {
    journal_date: string;
    journal_type: string;
    posting_profile_id?: number | null;
    description?: string | null;
    currency_id?: number | null;
    exchange_rate: string;
    lines: Array<{
        account_id: number | null;
        line_number: number;
        description?: string | null;
        debit: string;
        credit: string;
    }>;
}

export interface LedgerEntry extends Record<string, unknown> {
    id: number;
    entry_date: string;
    debit: string;
    credit: string;
    balance_after: string;
    source_module?: string | null;
    source_type?: string | null;
    source_id?: number | null;
    source_number?: string | null;
    source_date?: string | null;
    account?: FinanceLookup;
    journal_entry?: { id: number; journal_number?: string; description?: string };
    journal_line?: JournalLine;
}

export interface AccountBalanceRow extends Record<string, unknown> {
    account_id: number;
    account_code: string;
    account_name: string;
    normal_balance: string;
    opening_debit: string;
    opening_credit: string;
    period_debit: string;
    period_credit: string;
    closing_debit: string;
    closing_credit: string;
    balance: string;
}

export interface TrialBalance {
    totalDebit: string;
    totalCredit: string;
    isBalanced: boolean;
    accountBalances: Array<{
        accountId: number;
        accountCode: string;
        accountName: string;
        closingDebit: string;
        closingCredit: string;
    }>;
}

export interface PostingProfilePayload {
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
    rules: Array<{
        line_key: string;
        account_role_id: number;
        effective_from?: string | null;
        effective_to?: string | null;
        is_active?: boolean;
        description?: string | null;
    }>;
}

export interface AccountRolePayload {
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
}

export interface AccountAssignmentPayload {
    account_role_id: number;
    account_id: number;
    effective_from: string;
    effective_to?: string | null;
}

export interface AgingReport {
    as_of_date: string;
    direction: string;
    buckets: Record<string, string>;
    total: string;
    rows: Array<Record<string, unknown>>;
}

export interface BankReconciliation extends Record<string, unknown> {
    id: number;
    statement_reference: string;
    statement_date: string;
    status: string;
    opening_balance: string;
    closing_balance: string;
    reconciled_balance: string;
    matched_count?: number;
    unmatched_count?: number;
    bank_account?: FinanceLookup | null;
    lines?: Array<Record<string, unknown>>;
}

export interface BankReconciliationPayload {
    bank_account_id: number | null;
    statement_reference: string;
    statement_date: string;
    opening_balance: string;
    closing_balance: string;
    statement_lines: Array<{
        statement_date?: string;
        reference?: string | null;
        description?: string | null;
        debit: string;
        credit: string;
    }>;
}

export interface Budget extends Record<string, unknown> {
    id: number;
    name: string;
    budget_year: number;
    status: string;
    lines?: Array<Record<string, unknown>>;
}

export interface BudgetPayload {
    name: string;
    budget_year: number;
    status: string;
    description?: string | null;
    lines: Array<{ account_id: number | null; budget_month?: number | null; amount: string }>;
}
