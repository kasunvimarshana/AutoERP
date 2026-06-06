import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface FinanceAccount extends Record<string, unknown> {
    id: number;
    code?: string;
    name?: string;
    normal_balance?: string;
    is_active?: boolean;
    account_type?: { id: number; name: string; code?: string };
    account_category?: { id: number; name: string; code?: string };
    parent?: { id: number; name: string; code?: string };
    children?: Record<string, unknown>[];
    balances?: Record<string, unknown>[];
}

export interface LedgerEntry extends Record<string, unknown> {
    id: number;
    entry_date?: string;
    debit?: string;
    credit?: string;
    account?: { id: number; name: string; code?: string };
    journal_entry?: { id: number; journal_number?: string; description?: string };
}

export async function listAccounts(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<FinanceAccount>>(`${endpoints.finance}/accounts`, { params, signal });
    return response.data;
}

export async function getAccount(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<FinanceAccount>>(`${endpoints.finance}/accounts/${id}`, { signal });
    return response.data.data;
}

export async function getAccountBalance(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.finance}/accounts/${id}/balance`, { signal });
    return response.data.data;
}

export async function listLedgerEntries(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<LedgerEntry>>(`${endpoints.finance}/ledger-entries`, { params, signal });
    return response.data;
}
