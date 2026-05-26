export type AccountRecord = {
    id: number;
    tenant_id: number;
    parent_id: number | null;
    code: string;
    name: string;
    type: 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
    sub_type: string | null;
    normal_balance: 'debit' | 'credit';
    is_system: boolean;
    is_bank_account: boolean;
    is_credit_card: boolean;
    currency_id: number | null;
    description: string | null;
    is_active: boolean;
    path: string | null;
    depth: number;
    created_at: string | null;
    updated_at: string | null;
};

export type AccountFilters = {
    tenant_id?: number;
    code?: string;
    name?: string;
    type?: AccountRecord['type'];
    sub_type?: string;
    normal_balance?: AccountRecord['normal_balance'];
    is_active?: boolean;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type JournalEntryLineRecord = {
    id: number;
    account_id: number;
    description: string | null;
    debit_amount: number | string;
    credit_amount: number | string;
    currency_id: number | null;
    exchange_rate: number | string | null;
    base_debit_amount: number | string;
    base_credit_amount: number | string;
    cost_center_id: number | null;
    metadata: Record<string, unknown> | null;
};

export type JournalEntryRecord = {
    id: number;
    tenant_id: number;
    fiscal_period_id: number | null;
    entry_number: string;
    entry_type: 'manual' | 'auto' | 'system' | string;
    reference_type: string | null;
    reference_id: number | null;
    description: string | null;
    entry_date: string;
    posting_date: string | null;
    status: 'draft' | 'posted' | 'reversed' | string;
    is_reversed: boolean;
    reversal_entry_id: number | null;
    created_by: number | null;
    posted_by: number | null;
    posted_at: string | null;
    lines: JournalEntryLineRecord[];
    created_at: string | null;
    updated_at: string | null;
};

export type JournalEntryFilters = {
    tenant_id?: number;
    fiscal_period_id?: number;
    entry_type?: JournalEntryRecord['entry_type'];
    status?: JournalEntryRecord['status'];
    entry_number?: string;
    reference_type?: string;
    reference_id?: number;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type PaymentRecord = {
    id: number;
    tenant_id: number;
    payment_number: string;
    direction: 'inbound' | 'outbound' | string;
    party_type: 'customer' | 'supplier' | string;
    party_id: number | null;
    payment_method_id: number | null;
    account_id: number | null;
    amount: number | string;
    currency_id: number | null;
    exchange_rate: number | string | null;
    base_amount: number | string;
    payment_date: string;
    status: 'draft' | 'posted' | 'reconciled' | 'voided' | string;
    reference: string | null;
    notes: string | null;
    journal_entry_id: number | null;
    created_at: string | null;
    updated_at: string | null;
};

export type PaymentFilters = {
    tenant_id?: number;
    direction?: 'inbound' | 'outbound';
    party_type?: 'customer' | 'supplier';
    party_id?: number;
    status?: PaymentRecord['status'];
    per_page?: number;
    page?: number;
    sort?: string;
};

export type GeneralLedgerLine = {
    id: number;
    journal_entry_id: number;
    entry_number: string;
    entry_date: string;
    posting_date: string | null;
    entry_description: string | null;
    account_id: number;
    account_code: string;
    account_name: string;
    account_type: string;
    line_description: string | null;
    debit_amount: number | string;
    credit_amount: number | string;
    currency_id: number | null;
    exchange_rate: number | string | null;
    base_debit_amount: number | string;
    base_credit_amount: number | string;
    cost_center_id: number | null;
    cost_center_name: string | null;
    fiscal_period_name: string | null;
};

export type GeneralLedgerFilters = {
    tenant_id: number;
    account_id?: number;
    fiscal_period_id?: number;
    date_from?: string;
    date_to?: string;
    cost_center_id?: number;
    per_page?: number;
    page?: number;
};

export type GeneralLedgerReport = {
    data: GeneralLedgerLine[];
    meta: {
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
};

export type TrialBalanceRow = {
    account_id: number;
    account_code: string;
    account_name: string;
    account_type: string;
    normal_balance: string;
    total_debit: number | string;
    total_credit: number | string;
    net_balance: number | string;
};

export type TrialBalanceReport = {
    data: TrialBalanceRow[];
    summary: {
        total_debit: number;
        total_credit: number;
        is_balanced: boolean;
    };
};

export type BalanceSheetRow = {
    account_id: number;
    account_code: string;
    account_name: string;
    account_type: string;
    account_sub_type: string | null;
    normal_balance: string;
    balance: number | string;
};

export type BalanceSheetReport = {
    assets: BalanceSheetRow[];
    liabilities: BalanceSheetRow[];
    equity: BalanceSheetRow[];
    summary: {
        total_assets: number;
        total_liabilities: number;
        total_equity: number;
        total_liabilities_and_equity: number;
        is_balanced: boolean;
    };
};

export type ProfitLossReport = {
    revenues: BalanceSheetRow[];
    expenses: BalanceSheetRow[];
    summary: {
        total_revenue: number;
        total_expenses: number;
        net_income: number;
    };
};

export type TrialBalanceFilters = {
    tenant_id: number;
    fiscal_period_id?: number;
    date_from?: string;
    date_to?: string;
};

export type BalanceSheetFilters = {
    tenant_id: number;
    as_of_date?: string;
};

export type ProfitLossFilters = {
    tenant_id: number;
    fiscal_period_id?: number;
    date_from?: string;
    date_to?: string;
};
