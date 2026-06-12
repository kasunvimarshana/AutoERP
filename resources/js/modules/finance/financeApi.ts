import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface FinanceAccount extends Record<string, unknown> {
    id: number;
    code: string;
    name: string;
    description?: string | null;
    normal_balance: 'debit' | 'credit';
    opening_balance: string;
    current_balance: string;
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

export interface FiscalPeriod extends Record<string, unknown> {
    id: number;
    name: string;
    period_number: number;
    start_date: string;
    end_date: string;
    status: string;
    fiscal_year?: { id: number; name: string; status: string };
}

export interface PostingProfile extends FinanceLookup {
    rules?: Array<{ id: number; line_key: string; account_id?: number; account?: FinanceLookup; description?: string | null }>;
}

export interface FinanceLookups {
    types: FinanceLookup[];
    categories: FinanceLookup[];
    accounts: FinanceLookup[];
    periods: FiscalPeriod[];
    profiles: PostingProfile[];
    dimensions?: FinanceLookup[];
    bankAccounts?: FinanceLookup[];
}

export interface AccountPayload {
    account_type_id: number | null;
    account_category_id?: number | null;
    parent_id?: number | null;
    code: string;
    name: string;
    description?: string | null;
    normal_balance: 'debit' | 'credit';
    opening_balance: string;
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
    fiscal_period?: FiscalPeriod;
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
    fiscal_period_id?: number | null;
    posting_profile_id?: number | null;
    source_module?: string | null;
    source_type?: string | null;
    source_id?: number | null;
    source_number?: string | null;
    source_date?: string | null;
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
    account?: FinanceLookup;
    journal_entry?: { id: number; journal_number?: string; description?: string };
    journal_line?: JournalLine;
    fiscal_period?: FiscalPeriod;
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
    rules: Array<{ line_key: string; account_id: number | null; description?: string | null }>;
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
    fiscal_year_id?: number | null;
    lines: Array<{ account_id: number | null; budget_month?: number | null; amount: string }>;
}

export async function listAccounts(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<FinanceAccount>>(`${endpoints.finance}/accounts`, { params, signal });
    return response.data;
}

export async function getAccount(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<FinanceAccount>>(`${endpoints.finance}/accounts/${id}`, { signal });
    return response.data.data;
}

export async function createAccount(payload: AccountPayload) {
    const response = await apiClient.post<ApiResource<FinanceAccount>>(`${endpoints.finance}/accounts`, payload);
    return response.data.data;
}

export async function updateAccount(id: number, payload: AccountPayload) {
    const response = await apiClient.patch<ApiResource<FinanceAccount>>(`${endpoints.finance}/accounts/${id}`, payload);
    return response.data.data;
}

export async function getFinanceLookups(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<FinanceLookups>>(`${endpoints.finance}/lookups`, { signal });
    return response.data.data;
}

export async function getAccountBalance(id: number, params: ListParams = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<AccountBalanceRow>>(`${endpoints.finance}/accounts/${id}/balance`, { params, signal });
    return response.data.data;
}

export async function listAccountBalances(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<AccountBalanceRow>>(`${endpoints.finance}/account-balances`, { params, signal });
    return response.data;
}

export async function listJournals(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<JournalEntry>>(`${endpoints.finance}/journals`, { params, signal });
    return response.data;
}

export async function getJournal(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<JournalEntry>>(`${endpoints.finance}/journals/${id}`, { signal });
    return response.data.data;
}

export async function createJournal(payload: JournalPayload) {
    const response = await apiClient.post<ApiResource<JournalEntry>>(`${endpoints.finance}/journals`, payload);
    return response.data.data;
}

export async function updateJournal(id: number, payload: JournalPayload) {
    const response = await apiClient.patch<ApiResource<JournalEntry>>(`${endpoints.finance}/journals/${id}`, payload);
    return response.data.data;
}

export async function postJournal(id: number) {
    await apiClient.post(`${endpoints.finance}/journals/${id}/post`);
}

export async function cancelJournal(id: number) {
    const response = await apiClient.post<ApiResource<JournalEntry>>(`${endpoints.finance}/journals/${id}/cancel`);
    return response.data.data;
}

export async function reverseJournal(id: number, reversalDate: string, reversalReason: string) {
    const response = await apiClient.post<ApiResource<JournalEntry>>(`${endpoints.finance}/journals/${id}/reverse`, {
        reversal_date: reversalDate,
        reversal_reason: reversalReason,
    });
    return response.data.data;
}

export async function listLedgerEntries(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<LedgerEntry>>(`${endpoints.finance}/ledger-entries`, { params, signal });
    return response.data;
}

export async function getTrialBalance(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<TrialBalance>>(`${endpoints.finance}/trial-balance`, { params, signal });
    return response.data.data;
}

export async function getProfitAndLoss(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/profit-and-loss`, { params, signal });
    return response.data.data;
}

export async function getBalanceSheet(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/balance-sheet`, { params, signal });
    return response.data.data;
}

export async function getCashFlow(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/cash-flow`, { params, signal });
    return response.data.data;
}

export async function getArAging(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<AgingReport>>(`${endpoints.finance}/ar-aging`, { params, signal });
    return response.data.data;
}

export async function getApAging(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<AgingReport>>(`${endpoints.finance}/ap-aging`, { params, signal });
    return response.data.data;
}

export async function getTaxLiability(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/tax-liability`, { params, signal });
    return response.data.data;
}

export async function getTaxReconciliation(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/tax-reconciliation`, { params, signal });
    return response.data.data;
}

export async function listPostingProfiles(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PostingProfile>>(`${endpoints.finance}/posting-profiles`, { params, signal });
    return response.data;
}

export async function createPostingProfile(payload: PostingProfilePayload) {
    const response = await apiClient.post<ApiResource<PostingProfile>>(`${endpoints.finance}/posting-profiles`, payload);
    return response.data.data;
}

export async function updatePostingProfile(id: number, payload: PostingProfilePayload) {
    const response = await apiClient.patch<ApiResource<PostingProfile>>(`${endpoints.finance}/posting-profiles/${id}`, payload);
    return response.data.data;
}

export async function listFiscalPeriods(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<FiscalPeriod>>(`${endpoints.finance}/fiscal-periods`, { params, signal });
    return response.data;
}

export async function updateFiscalPeriodStatus(id: number, status: string) {
    const response = await apiClient.patch<ApiResource<FiscalPeriod>>(`${endpoints.finance}/fiscal-periods/${id}/status`, { status });
    return response.data.data;
}

export async function listBankReconciliations(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<BankReconciliation>>(`${endpoints.finance}/bank-reconciliations`, { params, signal });
    return response.data;
}

export async function getBankReconciliation(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<BankReconciliation>>(`${endpoints.finance}/bank-reconciliations/${id}`, { signal });
    return response.data.data;
}

export async function createBankReconciliation(payload: BankReconciliationPayload) {
    const response = await apiClient.post<ApiResource<BankReconciliation>>(`${endpoints.finance}/bank-reconciliations`, payload);
    return response.data.data;
}

export async function completeBankReconciliation(id: number) {
    const response = await apiClient.post<ApiResource<BankReconciliation>>(`${endpoints.finance}/bank-reconciliations/${id}/complete`);
    return response.data.data;
}

export async function matchBankStatementLine(reconciliationId: number, lineId: number, ledgerEntryId: number) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/bank-reconciliations/${reconciliationId}/lines/${lineId}/match`, {
        ledger_entry_id: ledgerEntryId,
    });
    return response.data.data;
}

export async function unmatchBankStatementLine(reconciliationId: number, lineId: number) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/bank-reconciliations/${reconciliationId}/lines/${lineId}/unmatch`);
    return response.data.data;
}

export async function listBudgets(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Budget>>(`${endpoints.finance}/budgets`, { params, signal });
    return response.data;
}

export async function createBudget(payload: BudgetPayload) {
    const response = await apiClient.post<ApiResource<Budget>>(`${endpoints.finance}/budgets`, payload);
    return response.data.data;
}

export async function getBudgetActuals(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/budgets/${id}/actuals`, { signal });
    return response.data.data;
}
