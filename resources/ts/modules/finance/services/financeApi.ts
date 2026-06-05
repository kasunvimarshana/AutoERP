import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import type {
    Account,
    AccountFormValues,
    ApTransaction,
    ArTransaction,
    BankAccount,
    BankAccountFormValues,
    BankReconciliation,
    BankTransaction,
    Budget,
    BudgetFormValues,
    CostCenter,
    CostCenterFormValues,
    FinanceDashboardMetric,
    FinanceListQuery,
    FinancePostingPreview,
    FiscalPeriod,
    FiscalYear,
    JournalEntry,
    JournalEntryFormValues,
    PaymentTerm,
    PaymentTermFormValues,
    TaxGroup,
    TaxGroupFormValues,
    TaxPreviewResult,
    TaxRate,
    TaxRateFormValues,
    TaxRule,
    TaxRuleFormValues,
} from '../types/finance.types';

type BackendRecord = Record<string, unknown>;
type BackendCollection<T> = ApiCollectionResponse<T> & {
    meta?: ApiCollectionResponse<T>['meta'] & {
        page?: number;
        page_count?: number;
        has_more?: boolean;
    };
};

function asString(value: unknown, fallback = ''): string {
    return value === null || value === undefined || value === '' ? fallback : String(value);
}

function asNumberString(value: unknown, fallback = '0.0000'): string {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    return String(value);
}

function asBoolean(value: unknown): boolean {
    return value === true || value === 1 || value === '1' || value === 'true';
}

function normalizeCollection<T>(response: BackendCollection<BackendRecord>, mapper: (record: BackendRecord) => T): ApiCollectionResponse<T> {
    return {
        ...response,
        data: response.data.map(mapper),
        meta: response.meta
            ? {
                current_page: response.meta.current_page ?? response.meta.page ?? 1,
                from: response.meta.from ?? 0,
                last_page: response.meta.last_page ?? response.meta.page_count ?? 1,
                per_page: response.meta.per_page ?? response.data.length,
                to: response.meta.to ?? response.data.length,
                total: response.meta.total ?? response.data.length,
            }
            : undefined,
    };
}

function withTenant<T extends Record<string, unknown>>(values: T): T {
    const tenantId = getStoredTenantId();
    const organizationUnitId = getStoredOrganizationUnitId();

    return {
        ...values,
        ...(tenantId ? { tenant_id: Number(tenantId) } : {}),
        ...(organizationUnitId ? { organization_unit_id: Number(organizationUnitId) } : {}),
    };
}

function statusFromActive(value: unknown, fallback = 'active'): string {
    if (value === undefined || value === null) {
        return fallback;
    }

    return asBoolean(value) ? 'active' : 'inactive';
}

function accountLabel(raw: BackendRecord | undefined): string {
    if (!raw) {
        return '';
    }

    const code = asString(raw.code ?? raw.account_code);
    const name = asString(raw.name ?? raw.account_name);

    return [code, name].filter(Boolean).join(' - ');
}

function normalizeAccount(raw: BackendRecord): Account {
    const code = asString(raw.code ?? raw.account_code, `Account ${asString(raw.id)}`);
    const name = asString(raw.name ?? raw.account_name, 'Unnamed account');

    return {
        accountCode: code,
        accountName: name,
        accountGroup: asString(raw.account_group) as Account['accountGroup'],
        accountType: asString(raw.type ?? raw.account_type, 'ASSET').toLowerCase() as Account['accountType'],
        allowsManualPosting: asBoolean(raw.allows_manual_posting ?? true),
        description: asString(raw.description),
        id: asString(raw.id),
        isBankAccount: asBoolean(raw.is_bank_account),
        isCashAccount: asBoolean(raw.is_cash_account),
        isControlAccount: asBoolean(raw.is_control_account),
        normalBalance: asString(raw.normal_balance, 'DEBIT').toLowerCase() as Account['normalBalance'],
        parentAccount: accountLabel(raw.parent as BackendRecord | undefined) || asString(raw.parent_account_name),
        parentId: raw.parent_id === null || raw.parent_id === undefined ? '' : asString(raw.parent_id),
        status: statusFromActive(raw.is_active, 'active') as Account['status'],
        updatedAt: asString(raw.updated_at),
    };
}

function normalizeJournal(raw: BackendRecord): JournalEntry {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        currency: asString(raw.currency?.['code' as never] ?? raw.currency_code ?? raw.currency, 'Default currency'),
        description: asString(raw.description),
        entryType: asString(raw.entry_type, 'MANUAL'),
        id: asString(raw.id),
        journalDate: asString(raw.entry_date ?? raw.journal_date ?? raw.date),
        journalNumber: asString(raw.entry_number ?? raw.journal_number ?? raw.number, `Journal ${asString(raw.id)}`),
        lines: lines.map((line, index) => ({
            account: accountLabel(line.account as BackendRecord | undefined) || asString(line.account_label ?? line.account_name, 'Account not expanded'),
            accountId: asString(line.account_id),
            costCenter: asString(line.cost_center_label ?? line.cost_center_name),
            credit: asNumberString(line.credit_amount ?? line.credit),
            debit: asNumberString(line.debit_amount ?? line.debit),
            description: asString(line.description),
            id: asString(line.id, `line-${index}`),
            party: asString(line.party_label ?? line.party_type),
            taxRate: asString(line.tax_rate_label ?? line.tax_rate_name),
        })),
        reference: asString(raw.reference_number ?? raw.reference),
        sourceId: asString(raw.source_id),
        sourceModule: asString(raw.source_module),
        sourceReference: asString(raw.source_reference),
        sourceType: asString(raw.source_type),
        status: asString(raw.status, 'DRAFT').toLowerCase() as JournalEntry['status'],
        totalCredit: asNumberString(raw.total_credit),
        totalDebit: asNumberString(raw.total_debit),
    };
}

function normalizeFiscalYear(raw: BackendRecord): FiscalYear {
    return {
        endDate: asString(raw.end_date),
        id: asString(raw.id),
        name: asString(raw.name, 'Fiscal year'),
        startDate: asString(raw.start_date),
        status: asString(raw.status, 'OPEN').toLowerCase(),
    };
}

function normalizeFiscalPeriod(raw: BackendRecord): FiscalPeriod {
    return {
        endDate: asString(raw.end_date),
        fiscalYear: asString(raw.fiscal_year_label ?? raw.fiscal_year?.['name' as never] ?? raw.fiscal_year_id),
        id: asString(raw.id),
        name: asString(raw.name, `Period ${asString(raw.period_number)}`),
        startDate: asString(raw.start_date),
        status: asString(raw.status, 'OPEN').toLowerCase(),
    };
}

function normalizePaymentTerm(raw: BackendRecord): PaymentTerm {
    return {
        code: asString(raw.code ?? raw.name),
        dueDays: asNumberString(raw.due_days, '0'),
        id: asString(raw.id),
        name: asString(raw.name, 'Payment term'),
        status: statusFromActive(raw.is_active, 'active'),
    };
}

function normalizeCostCenter(raw: BackendRecord): CostCenter {
    return {
        code: asString(raw.code, `CC-${asString(raw.id)}`),
        id: asString(raw.id),
        name: asString(raw.name, 'Cost center'),
        status: statusFromActive(raw.is_active, 'active'),
    };
}

function normalizeTaxGroup(raw: BackendRecord): TaxGroup {
    return {
        code: asString(raw.code ?? raw.name),
        id: asString(raw.id),
        name: asString(raw.name, 'Tax group'),
        status: statusFromActive(raw.is_active, 'active'),
    };
}

function normalizeTaxRate(raw: BackendRecord): TaxRate {
    return {
        code: asString(raw.code ?? raw.name),
        effectiveFrom: asString(raw.valid_from ?? raw.effective_from),
        id: asString(raw.id),
        name: asString(raw.name, 'Tax rate'),
        rate: asNumberString(raw.rate),
        status: statusFromActive(raw.is_active, 'active'),
    };
}

function normalizeTaxRule(raw: BackendRecord): TaxRule {
    return {
        appliesTo: asString(raw.applies_to ?? raw.scope_type, 'Generic'),
        id: asString(raw.id),
        name: asString(raw.name, 'Tax rule'),
        priority: asNumberString(raw.priority, '0'),
        status: statusFromActive(raw.is_active, 'active'),
        taxGroup: asString(raw.tax_group_label ?? raw.tax_group_name ?? raw.tax_group_id),
        taxRate: asString(raw.tax_rate_label ?? raw.tax_rate_name ?? raw.tax_rate_id),
    };
}

function normalizeApTransaction(raw: BackendRecord): ApTransaction {
    return {
        agingBucket: asString(raw.aging_bucket ?? raw.status),
        dueDate: asString(raw.due_date),
        id: asString(raw.id),
        originalAmount: asNumberString(raw.original_amount),
        outstandingAmount: asNumberString(raw.outstanding_amount),
        paidAmount: asNumberString(raw.paid_amount),
        party: asString(raw.party_label ?? raw.party_type ?? 'Generic party'),
        sourceReference: asString(raw.source_reference ?? raw.reference_id ?? 'Unlinked'),
        status: asString(raw.status, 'OPEN').toLowerCase(),
    };
}

const normalizeArTransaction = normalizeApTransaction;

function normalizeBankAccount(raw: BackendRecord): BankAccount {
    return {
        accountName: asString(raw.name ?? raw.account_name, 'Bank account'),
        accountNumber: asString(raw.account_number),
        bankName: asString(raw.bank_name),
        currency: asString(raw.currency_label ?? raw.currency_code ?? raw.currency_id),
        id: asString(raw.id),
        status: statusFromActive(raw.is_active, 'active'),
    };
}

function normalizeBankTransaction(raw: BackendRecord): BankTransaction {
    return {
        amount: asNumberString(raw.amount),
        bankAccount: asString(raw.bank_account_label ?? raw.bank_account_name ?? raw.bank_account_id),
        date: asString(raw.transaction_date ?? raw.date),
        id: asString(raw.id),
        reference: asString(raw.reference ?? raw.external_id ?? raw.source_reference),
        reconciliationStatus: asString(raw.status ?? raw.reconciliation_status),
        type: asString(raw.transaction_type ?? raw.type),
    };
}

function normalizeReconciliation(raw: BackendRecord): BankReconciliation {
    return {
        bankAccount: asString(raw.bank_account_label ?? raw.bank_account_name ?? raw.bank_account_id),
        id: asString(raw.id),
        period: [asString(raw.period_start), asString(raw.period_end)].filter(Boolean).join(' to '),
        status: asString(raw.status, 'draft').toLowerCase(),
        variance: asNumberString(raw.variance_amount ?? raw.difference_amount),
    };
}

function normalizeBudget(raw: BackendRecord): Budget {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        fiscalYear: asString(raw.fiscal_year_label ?? raw.fiscal_year_name ?? raw.fiscal_year_id),
        id: asString(raw.id),
        lines: lines.map((line) => ({
            account: asString(line.account_label ?? line.account_name ?? line.account_id),
            budgetAmount: asNumberString(line.budget_amount ?? line.amount),
            id: asString(line.id),
            usage: asNumberString(line.used_amount ?? line.actual_amount),
            variance: asNumberString(line.variance_amount),
        })),
        name: asString(raw.name, 'Budget'),
        status: asString(raw.status, 'draft').toLowerCase(),
        variance: asNumberString(raw.variance_amount),
    };
}

function normalizePostingPreview(raw: unknown, input: Record<string, unknown> = {}): FinancePostingPreview {
    const payload = (raw && typeof raw === 'object' && 'data' in raw ? (raw as { data: unknown }).data : raw) as BackendRecord;
    const totals = (payload.totals ?? payload.calculated ?? {}) as BackendRecord;

    return {
        breakdown: Array.isArray(payload.breakdown) ? payload.breakdown as Array<{ label: string; value: string }> : [],
        calculated: {
            balanced: asString(payload.can_post ?? totals.balanced, 'requires backend preview'),
            eligibility: asString(payload.posting_eligibility ?? payload.can_post ?? totals.eligibility, 'requires backend preview'),
            totalCredit: asNumberString(totals.base_credit_total ?? totals.credit_total ?? totals.totalCredit),
            totalDebit: asNumberString(totals.base_debit_total ?? totals.debit_total ?? totals.totalDebit),
        },
        errors: Array.isArray(payload.errors) ? payload.errors.map(String) : [],
        input,
        journalLines: Array.isArray(payload.journal_lines) ? payload.journal_lines as FinancePostingPreview['journalLines'] : [],
        warnings: Array.isArray(payload.warnings) ? payload.warnings.map(String) : [],
    };
}

function toAccountPayload(values: AccountFormValues): Record<string, unknown> {
    return withTenant({
        account_group: values.accountGroup || null,
        allows_manual_posting: values.allowsManualPosting,
        code: values.accountCode,
        description: values.description || null,
        is_active: values.status === 'active',
        is_bank_account: values.isBankAccount,
        is_cash_account: values.isCashAccount,
        is_control_account: values.isControlAccount,
        name: values.accountName,
        normal_balance: values.normalBalance.toUpperCase(),
        parent_id: values.parentId ? Number(values.parentId) : null,
        type: values.accountType.toUpperCase(),
    });
}

function toJournalPayload(values: JournalEntryFormValues): Record<string, unknown> {
    return withTenant({
        description: values.description || null,
        entry_date: values.journalDate,
        entry_number: values.journalNumber,
        entry_type: values.entryType || 'MANUAL',
        source_module: values.sourceModule || null,
        source_reference: values.sourceReference || null,
        source_type: values.sourceType || null,
        status: values.status.toUpperCase(),
        lines: values.lines.map((line, index) => ({
            account_id: Number(line.accountId),
            credit_amount: line.credit ? Number(line.credit) : 0,
            debit_amount: line.debit ? Number(line.debit) : 0,
            description: line.description || null,
            line_number: index + 1,
        })),
    });
}

export const financeApi = {
    async listDashboardMetrics(): Promise<ApiCollectionResponse<FinanceDashboardMetric>> {
        const [accounts, journals, ap, ar, bank] = await Promise.all([
            financeApi.listAccounts({ per_page: 1 }),
            financeApi.listJournalEntries({ per_page: 1 }),
            financeApi.listApTransactions({ per_page: 1 }),
            financeApi.listArTransactions({ per_page: 1 }),
            financeApi.listBankTransactions({ per_page: 1 }),
        ]);

        return {
            data: [
                { label: 'Accounts', status: 'active', value: String(accounts.meta?.total ?? accounts.data.length) },
                { label: 'Journals', status: 'active', value: String(journals.meta?.total ?? journals.data.length) },
                { label: 'AP transactions', status: 'readonly', value: String(ap.meta?.total ?? ap.data.length) },
                { label: 'AR transactions', status: 'readonly', value: String(ar.meta?.total ?? ar.data.length) },
                { label: 'Bank transactions', status: 'readonly', value: String(bank.meta?.total ?? bank.data.length) },
            ],
        };
    },

    async listAccounts(query?: FinanceListQuery): Promise<ApiCollectionResponse<Account>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/accounts', { query });
        return normalizeCollection(response, normalizeAccount);
    },
    async getAccount(id: string): Promise<ApiResponse<Account>> {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/finance/accounts/${id}`);
        return { ...response, data: normalizeAccount(response.data) };
    },
    createAccount: (input: AccountFormValues) => httpClient<ApiResponse<BackendRecord>>('/api/finance/accounts', { body: toAccountPayload(input), method: 'POST' }),
    updateAccount: (id: string, input: AccountFormValues) => httpClient<ApiResponse<BackendRecord>>(`/api/finance/accounts/${id}`, { body: toAccountPayload(input), method: 'PUT' }),
    activateAccount: (id: string) => httpClient<ApiResponse<BackendRecord>>(`/api/finance/accounts/${id}/activate`, { method: 'PATCH' }),
    deactivateAccount: (id: string) => httpClient<ApiResponse<BackendRecord>>(`/api/finance/accounts/${id}/deactivate`, { method: 'PATCH' }),

    async listFiscalYears(query?: FinanceListQuery): Promise<ApiCollectionResponse<FiscalYear>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/fiscal-years', { query });
        return normalizeCollection(response, normalizeFiscalYear);
    },
    createFiscalYear: (input: Record<string, unknown>) => httpClient<ApiResponse<unknown>>('/api/finance/fiscal-years', { body: withTenant(input), method: 'POST' }),
    updateFiscalYear: (id: string, input: Record<string, unknown>) => httpClient<ApiResponse<unknown>>(`/api/finance/fiscal-years/${id}`, { body: withTenant(input), method: 'PUT' }),
    async listFiscalPeriods(query?: FinanceListQuery): Promise<ApiCollectionResponse<FiscalPeriod>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/fiscal-periods', { query });
        return normalizeCollection(response, normalizeFiscalPeriod);
    },
    openFiscalPeriod: (id: string) => httpClient<ApiResponse<FiscalPeriod>>(`/api/finance/fiscal-periods/${id}/open`, { method: 'PATCH' }),
    closeFiscalPeriod: (id: string) => httpClient<ApiResponse<FiscalPeriod>>(`/api/finance/fiscal-periods/${id}/close`, { method: 'PATCH' }),

    async listJournalEntries(query?: FinanceListQuery): Promise<ApiCollectionResponse<JournalEntry>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/journal-entries', { query });
        return normalizeCollection(response, normalizeJournal);
    },
    async getJournalEntry(id: string): Promise<ApiResponse<JournalEntry>> {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/finance/journal-entries/${id}`);
        return { ...response, data: normalizeJournal(response.data) };
    },
    createJournalEntry: (input: JournalEntryFormValues) => httpClient<ApiResponse<BackendRecord>>('/api/finance/journal-entries', { body: toJournalPayload(input), method: 'POST' }),
    updateJournalEntry: (id: string, input: JournalEntryFormValues) => httpClient<ApiResponse<BackendRecord>>(`/api/finance/journal-entries/${id}`, { body: toJournalPayload(input), method: 'PUT' }),
    async previewJournalPosting(id: string, input: Record<string, unknown>): Promise<FinancePostingPreview> {
        const response = await httpClient<unknown>(`/api/finance/journal-entries/${id}/engines/preview-posting`, { body: input, method: 'POST' });
        return normalizePostingPreview(response, input);
    },
    postJournalEntry: (id: string, input: Record<string, unknown> = {}) => httpClient<ApiResponse<unknown>>(`/api/finance/journal-entries/${id}/engines/post`, { body: input, method: 'POST' }),
    reverseJournalEntry: (id: string, input: Record<string, unknown> = {}) => httpClient<ApiResponse<unknown>>(`/api/finance/journal-entries/${id}/engines/reverse`, { body: input, method: 'POST' }),
    async previewSourcePosting(input: Record<string, unknown>): Promise<FinancePostingPreview> {
        const response = await httpClient<unknown>('/api/finance/posting-preview', { body: input, method: 'POST' });
        return normalizePostingPreview(response, input);
    },

    async listApTransactions(query?: FinanceListQuery): Promise<ApiCollectionResponse<ApTransaction>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/ap-transactions', { query });
        return normalizeCollection(response, normalizeApTransaction);
    },
    async listArTransactions(query?: FinanceListQuery): Promise<ApiCollectionResponse<ArTransaction>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/ar-transactions', { query });
        return normalizeCollection(response, normalizeArTransaction);
    },

    async listTaxGroups(query?: FinanceListQuery): Promise<ApiCollectionResponse<TaxGroup>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/tax-groups', { query });
        return normalizeCollection(response, normalizeTaxGroup);
    },
    createTaxGroup: (input: TaxGroupFormValues) => httpClient<ApiResponse<unknown>>('/api/finance/tax-groups', { body: withTenant({ is_active: input.status === 'active', name: input.name }), method: 'POST' }),
    updateTaxGroup: (id: string, input: TaxGroupFormValues) => httpClient<ApiResponse<unknown>>(`/api/finance/tax-groups/${id}`, { body: withTenant({ is_active: input.status === 'active', name: input.name }), method: 'PUT' }),
    async listTaxRates(query?: FinanceListQuery): Promise<ApiCollectionResponse<TaxRate>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/tax-rates', { query });
        return normalizeCollection(response, normalizeTaxRate);
    },
    createTaxRate: (input: TaxRateFormValues) => httpClient<ApiResponse<unknown>>('/api/finance/tax-rates', { body: withTenant({ is_active: input.status === 'active', name: input.name, rate: Number(input.rate), tax_group_id: Number(input.taxGroupId), type: input.type, valid_from: input.effectiveFrom || null }), method: 'POST' }),
    updateTaxRate: (id: string, input: TaxRateFormValues) => httpClient<ApiResponse<unknown>>(`/api/finance/tax-rates/${id}`, { body: withTenant({ is_active: input.status === 'active', name: input.name, rate: Number(input.rate), tax_group_id: Number(input.taxGroupId), type: input.type, valid_from: input.effectiveFrom || null }), method: 'PUT' }),
    async listTaxRules(query?: FinanceListQuery): Promise<ApiCollectionResponse<TaxRule>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/tax-rules', { query });
        return normalizeCollection(response, normalizeTaxRule);
    },
    createTaxRule: (input: TaxRuleFormValues) => httpClient<ApiResponse<unknown>>('/api/finance/tax-rules', { body: withTenant(input as unknown as Record<string, unknown>), method: 'POST' }),
    updateTaxRule: (id: string, input: TaxRuleFormValues) => httpClient<ApiResponse<unknown>>(`/api/finance/tax-rules/${id}`, { body: withTenant(input as unknown as Record<string, unknown>), method: 'PUT' }),
    async previewTaxCalculation(input: Record<string, unknown>): Promise<TaxPreviewResult> {
        const response = await httpClient<TaxPreviewResult>('/api/finance/tax/preview-calculate', { body: input, method: 'POST' });
        return response;
    },

    async listPaymentTerms(query?: FinanceListQuery): Promise<ApiCollectionResponse<PaymentTerm>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/payment-terms', { query });
        return normalizeCollection(response, normalizePaymentTerm);
    },
    createPaymentTerm: (input: PaymentTermFormValues) => httpClient<ApiResponse<unknown>>('/api/finance/payment-terms', { body: withTenant({ due_days: Number(input.dueDays), is_active: input.status === 'active', name: input.name, payment_type: 'net' }), method: 'POST' }),
    updatePaymentTerm: (id: string, input: PaymentTermFormValues) => httpClient<ApiResponse<unknown>>(`/api/finance/payment-terms/${id}`, { body: withTenant({ due_days: Number(input.dueDays), is_active: input.status === 'active', name: input.name, payment_type: 'net' }), method: 'PUT' }),
    async listCostCenters(query?: FinanceListQuery): Promise<ApiCollectionResponse<CostCenter>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/cost-centers', { query });
        return normalizeCollection(response, normalizeCostCenter);
    },
    createCostCenter: (input: CostCenterFormValues) => httpClient<ApiResponse<unknown>>('/api/finance/cost-centers', { body: withTenant({ code: input.code, is_active: input.status === 'active', name: input.name }), method: 'POST' }),
    updateCostCenter: (id: string, input: CostCenterFormValues) => httpClient<ApiResponse<unknown>>(`/api/finance/cost-centers/${id}`, { body: withTenant({ code: input.code, is_active: input.status === 'active', name: input.name }), method: 'PUT' }),

    async listBankAccounts(query?: FinanceListQuery): Promise<ApiCollectionResponse<BankAccount>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/bank-accounts', { query });
        return normalizeCollection(response, normalizeBankAccount);
    },
    createBankAccount: (input: BankAccountFormValues) => httpClient<ApiResponse<unknown>>('/api/finance/bank-accounts', { body: withTenant(input as unknown as Record<string, unknown>), method: 'POST' }),
    updateBankAccount: (id: string, input: BankAccountFormValues) => httpClient<ApiResponse<unknown>>(`/api/finance/bank-accounts/${id}`, { body: withTenant(input as unknown as Record<string, unknown>), method: 'PUT' }),
    async listBankTransactions(query?: FinanceListQuery): Promise<ApiCollectionResponse<BankTransaction>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/bank-transactions', { query });
        return normalizeCollection(response, normalizeBankTransaction);
    },
    async listReconciliations(query?: FinanceListQuery): Promise<ApiCollectionResponse<BankReconciliation>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/bank-reconciliations', { query });
        return normalizeCollection(response, normalizeReconciliation);
    },
    async listBudgets(query?: FinanceListQuery): Promise<ApiCollectionResponse<Budget>> {
        const response = await httpClient<BackendCollection<BackendRecord>>('/api/finance/budgets', { query });
        return normalizeCollection(response, normalizeBudget);
    },
    createBudget: (input: BudgetFormValues) => httpClient<ApiResponse<unknown>>('/api/finance/budgets', { body: withTenant({ fiscal_year_id: Number(input.fiscalYearId), name: input.name, status: input.status.toUpperCase() }), method: 'POST' }),
    updateBudget: (id: string, input: BudgetFormValues) => httpClient<ApiResponse<unknown>>(`/api/finance/budgets/${id}`, { body: withTenant({ fiscal_year_id: Number(input.fiscalYearId), name: input.name, status: input.status.toUpperCase() }), method: 'PUT' }),
};
