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
    rules?: Array<{ id: number; line_key: string; account?: FinanceLookup }>;
}

export interface FinanceLookups {
    types: FinanceLookup[];
    categories: FinanceLookup[];
    accounts: FinanceLookup[];
    periods: FiscalPeriod[];
    profiles: PostingProfile[];
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
