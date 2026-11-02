import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    AccountAssignmentPayload,
    AccountBalanceRow,
    AccountPayload,
    AccountRolePayload,
    AgingReport,
    BankReconciliation,
    BankReconciliationPayload,
    Budget,
    BudgetPayload,
    FinanceAccount,
    FinanceAccountAssignment,
    FinanceAccountRole,
    FinanceLookups,
    JournalEntry,
    JournalPayload,
    LedgerEntry,
    PostingProfile,
    PostingProfilePayload,
    TrialBalance,
} from './financeTypes';

export type {
    AccountAssignmentPayload,
    AccountBalanceRow,
    AccountPayload,
    AccountRolePayload,
    AgingReport,
    BankReconciliation,
    BankReconciliationPayload,
    Budget,
    BudgetPayload,
    FinanceAccount,
    FinanceAccountAssignment,
    FinanceAccountRole,
    FinanceLookup,
    FinanceLookups,
    JournalEntry,
    JournalLine,
    JournalPayload,
    LedgerEntry,
    PostingProfile,
    PostingProfilePayload,
    TrialBalance,
} from './financeTypes';

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

export async function listAccountRoles(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<FinanceAccountRole>>(`${endpoints.finance}/account-roles`, { params, signal });
    return response.data;
}

export async function createAccountRole(payload: AccountRolePayload) {
    const response = await apiClient.post<ApiResource<FinanceAccountRole>>(`${endpoints.finance}/account-roles`, payload);
    return response.data.data;
}

export async function updateAccountRole(id: number, payload: AccountRolePayload) {
    const response = await apiClient.patch<ApiResource<FinanceAccountRole>>(`${endpoints.finance}/account-roles/${id}`, payload);
    return response.data.data;
}

export async function listAccountAssignments(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<FinanceAccountAssignment>>(`${endpoints.finance}/account-assignments`, { params, signal });
    return response.data;
}

export async function createAccountAssignment(payload: AccountAssignmentPayload) {
    const response = await apiClient.post<ApiResource<FinanceAccountAssignment>>(`${endpoints.finance}/account-assignments`, payload);
    return response.data.data;
}

export async function endAccountAssignment(id: number, effectiveTo: string) {
    const response = await apiClient.post<ApiResource<FinanceAccountAssignment>>(`${endpoints.finance}/account-assignments/${id}/end`, {
        effective_to: effectiveTo,
    });
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
