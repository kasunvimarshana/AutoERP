import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    accounts,
    apTransactions,
    arTransactions,
    bankAccounts,
    bankTransactions,
    budgets,
    costCenters,
    financeActivity,
    financeDashboardMetrics,
    fiscalPeriods,
    fiscalYears,
    getAccountById,
    getJournalById,
    journalEntries,
    paymentTerms,
    postingPreview,
    reconciliations,
    taxGroups,
    taxPreview,
    taxRates,
    taxRules,
} from '../mock/financeMock';
import type {
    Account,
    ApTransaction,
    ArTransaction,
    BankAccount,
    BankReconciliation,
    BankTransaction,
    Budget,
    CostCenter,
    FinanceAuditEntry,
    FinancePostingPreview,
    FiscalPeriod,
    FiscalYear,
    JournalEntry,
    PaymentTerm,
    TaxGroup,
    TaxPreviewResult,
    TaxRate,
    TaxRule,
} from '../types/finance.types';

type BackendRecord = Record<string, unknown>;

const FINANCE_API_MODE = import.meta.env.VITE_FINANCE_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return FINANCE_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419, 422]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (FINANCE_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function normalizeAccount(raw: BackendRecord): Account {
    return {
        accountCode: asString(raw.account_code ?? raw.code, `ACC-${asString(raw.id)}`),
        accountName: asString(raw.account_name ?? raw.name, 'Backend account'),
        accountGroup: asString(raw.account_group) as Account['accountGroup'],
        accountType: asString(raw.account_type ?? raw.type, 'asset') as Account['accountType'],
        id: asString(raw.id),
        normalBalance: asString(raw.normal_balance, 'debit') as Account['normalBalance'],
        parentAccount: asString(raw.parent_account_name ?? raw.parent_account_id),
        status: asString(raw.status, 'active') as Account['status'],
        updatedAt: asString(raw.updated_at, 'Backend timestamp'),
    };
}

function normalizeJournal(raw: BackendRecord): JournalEntry {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        currency: asString(raw.currency_code ?? raw.currency, 'Backend currency'),
        description: asString(raw.description),
        id: asString(raw.id),
        journalDate: asString(raw.journal_date ?? raw.date, 'Backend date'),
        journalNumber: asString(raw.journal_number ?? raw.number, `JE-${asString(raw.id)}`),
        lines: lines.map((line, index) => ({
            account: asString(line.account_name ?? line.account_id, 'Backend account'),
            costCenter: asString(line.cost_center_name ?? line.cost_center_id),
            credit: asString(line.credit_amount ?? line.credit, 'Backend calculated'),
            debit: asString(line.debit_amount ?? line.debit, 'Backend calculated'),
            description: asString(line.description),
            id: asString(line.id, `line-${index}`),
            party: asString(line.party_name ?? line.party_id),
            taxRate: asString(line.tax_rate_name ?? line.tax_rate_id),
        })),
        reference: asString(raw.reference_number ?? raw.reference),
        sourceModule: asString(raw.source_module),
        sourceType: asString(raw.source_type),
        sourceId: asString(raw.source_id),
        sourceReference: asString(raw.source_reference),
        status: asString(raw.status, 'draft') as JournalEntry['status'],
    };
}

export const financeApi = {
    listDashboardMetrics: () => mockCollectionResponse(financeDashboardMetrics),

    listAccounts: (): Promise<ApiCollectionResponse<Account>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/finance/accounts');
            return { ...response, data: response.data.map(normalizeAccount) };
        },
        () => mockCollectionResponse(accounts),
    ),
    getAccount: (id: string): Promise<ApiResponse<Account>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/finance/accounts/${id}`);
            return { ...response, data: normalizeAccount(response.data) };
        },
        () => mockResponse(getAccountById(id)),
    ),
    createAccount: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/accounts', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateAccount: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/accounts/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    activateAccount: (id: string) => withMockFallback(() => httpClient<ApiResponse<Account>>(`/api/finance/accounts/${id}/activate`, { method: 'PATCH' }), () => mockResponse({ ...getAccountById(id), status: 'active' })),
    deactivateAccount: (id: string) => withMockFallback(() => httpClient<ApiResponse<Account>>(`/api/finance/accounts/${id}/deactivate`, { method: 'PATCH' }), () => mockResponse({ ...getAccountById(id), status: 'inactive' })),

    listFiscalYears: (): Promise<ApiCollectionResponse<FiscalYear>> => withMockFallback(() => httpClient<ApiCollectionResponse<FiscalYear>>('/api/finance/fiscal-years'), () => mockCollectionResponse(fiscalYears)),
    createFiscalYear: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/fiscal-years', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateFiscalYear: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/fiscal-years/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    listFiscalPeriods: (): Promise<ApiCollectionResponse<FiscalPeriod>> => withMockFallback(() => httpClient<ApiCollectionResponse<FiscalPeriod>>('/api/finance/fiscal-periods'), () => mockCollectionResponse(fiscalPeriods)),
    openFiscalPeriod: (id: string) => withMockFallback(() => httpClient<ApiResponse<FiscalPeriod>>(`/api/finance/fiscal-periods/${id}/open`, { method: 'PATCH' }), () => mockResponse({ ...(fiscalPeriods.find((period) => period.id === id) ?? fiscalPeriods[0]), status: 'open' })),
    closeFiscalPeriod: (id: string) => withMockFallback(() => httpClient<ApiResponse<FiscalPeriod>>(`/api/finance/fiscal-periods/${id}/close`, { method: 'PATCH' }), () => mockResponse({ ...(fiscalPeriods.find((period) => period.id === id) ?? fiscalPeriods[0]), status: 'closed' })),

    listJournalEntries: (): Promise<ApiCollectionResponse<JournalEntry>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/finance/journal-entries');
            return { ...response, data: response.data.map(normalizeJournal) };
        },
        () => mockCollectionResponse(journalEntries),
    ),
    getJournalEntry: (id: string): Promise<ApiResponse<JournalEntry>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/finance/journal-entries/${id}`);
            return { ...response, data: normalizeJournal(response.data) };
        },
        () => mockResponse(getJournalById(id)),
    ),
    createJournalEntry: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/journal-entries', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateJournalEntry: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/journal-entries/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    previewJournalPosting: (id: string, input: unknown): Promise<ApiPreviewResponse<unknown, FinancePostingPreview['calculated']>> => withMockFallback(
        () => httpClient<ApiPreviewResponse<unknown, FinancePostingPreview['calculated']>>(`/api/finance/journal-entries/${id}/engines/preview-posting`, { body: input, method: 'POST' }),
        () => mockPreviewResponse(input, postingPreview.calculated, postingPreview.breakdown, postingPreview.warnings),
    ),
    postJournalEntry: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/journal-entries/${id}/engines/post`, { method: 'POST' }), () => mockResponse({ action: 'post-journal-entry', id })),
    reverseJournalEntry: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/journal-entries/${id}/engines/reverse`, { method: 'POST' }), () => mockResponse({ action: 'reverse-journal-entry', id })),
    previewSourcePosting: (input: unknown): Promise<ApiPreviewResponse<unknown, FinancePostingPreview['calculated']>> => withMockFallback(
        () => httpClient<ApiPreviewResponse<unknown, FinancePostingPreview['calculated']>>('/api/finance/posting-preview', { body: input, method: 'POST' }),
        () => mockPreviewResponse(input, postingPreview.calculated, postingPreview.breakdown, postingPreview.warnings),
    ),

    listApTransactions: (): Promise<ApiCollectionResponse<ApTransaction>> => withMockFallback(() => httpClient<ApiCollectionResponse<ApTransaction>>('/api/finance/ap-transactions'), () => mockCollectionResponse(apTransactions)),
    listArTransactions: (): Promise<ApiCollectionResponse<ArTransaction>> => withMockFallback(() => httpClient<ApiCollectionResponse<ArTransaction>>('/api/finance/ar-transactions'), () => mockCollectionResponse(arTransactions)),

    listTaxGroups: (): Promise<ApiCollectionResponse<TaxGroup>> => withMockFallback(() => httpClient<ApiCollectionResponse<TaxGroup>>('/api/finance/tax-groups'), () => mockCollectionResponse(taxGroups)),
    createTaxGroup: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/tax-groups', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateTaxGroup: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/tax-groups/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    listTaxRates: (): Promise<ApiCollectionResponse<TaxRate>> => withMockFallback(() => httpClient<ApiCollectionResponse<TaxRate>>('/api/finance/tax-rates'), () => mockCollectionResponse(taxRates)),
    createTaxRate: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/tax-rates', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateTaxRate: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/tax-rates/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    listTaxRules: (): Promise<ApiCollectionResponse<TaxRule>> => withMockFallback(() => httpClient<ApiCollectionResponse<TaxRule>>('/api/finance/tax-rules'), () => mockCollectionResponse(taxRules)),
    createTaxRule: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/tax-rules', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateTaxRule: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/tax-rules/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    previewTaxCalculation: (input: unknown): Promise<ApiPreviewResponse<unknown, TaxPreviewResult['calculated']>> => withMockFallback(
        () => httpClient<ApiPreviewResponse<unknown, TaxPreviewResult['calculated']>>('/api/finance/tax/preview-calculate', { body: input, method: 'POST' }),
        () => mockPreviewResponse(input, taxPreview.calculated, taxPreview.breakdown, taxPreview.warnings),
    ),

    listPaymentTerms: (): Promise<ApiCollectionResponse<PaymentTerm>> => withMockFallback(() => httpClient<ApiCollectionResponse<PaymentTerm>>('/api/finance/payment-terms'), () => mockCollectionResponse(paymentTerms)),
    createPaymentTerm: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/payment-terms', { body: input, method: 'POST' }), () => mockResponse(input)),
    updatePaymentTerm: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/payment-terms/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    listCostCenters: (): Promise<ApiCollectionResponse<CostCenter>> => withMockFallback(() => httpClient<ApiCollectionResponse<CostCenter>>('/api/finance/cost-centers'), () => mockCollectionResponse(costCenters)),
    createCostCenter: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/cost-centers', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateCostCenter: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/cost-centers/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),

    listBankAccounts: (): Promise<ApiCollectionResponse<BankAccount>> => withMockFallback(() => httpClient<ApiCollectionResponse<BankAccount>>('/api/finance/bank-accounts'), () => mockCollectionResponse(bankAccounts)),
    createBankAccount: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/bank-accounts', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateBankAccount: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/bank-accounts/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    listBankTransactions: (): Promise<ApiCollectionResponse<BankTransaction>> => withMockFallback(() => httpClient<ApiCollectionResponse<BankTransaction>>('/api/finance/bank-transactions'), () => mockCollectionResponse(bankTransactions)),
    listReconciliations: (): Promise<ApiCollectionResponse<BankReconciliation>> => withMockFallback(() => httpClient<ApiCollectionResponse<BankReconciliation>>('/api/finance/bank-reconciliations'), () => mockCollectionResponse(reconciliations)),
    listBudgets: (): Promise<ApiCollectionResponse<Budget>> => withMockFallback(() => httpClient<ApiCollectionResponse<Budget>>('/api/finance/budgets'), () => mockCollectionResponse(budgets)),
    createBudget: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/finance/budgets', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateBudget: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/finance/budgets/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),

    getFinanceActivity: (): Promise<ApiCollectionResponse<FinanceAuditEntry>> => mockCollectionResponse(financeActivity),
};
