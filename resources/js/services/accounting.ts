import http from '@/services/http';
import type { ChartOfAccount, JournalEntry, AccountingPeriod, PaginatedResponse } from '@/types/index';

export type { AccountingPeriod };

export interface AccountPayload {
  code: string;
  name: string;
  type: 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
  parent_id?: number | null;
  is_active?: boolean;
}

export interface JournalEntryLinePayload {
  account_id: number;
  debit?: string;
  credit?: string;
  description?: string | null;
}

export interface JournalEntryPayload {
  reference?: string;
  description?: string | null;
  entry_date?: string;
  lines: JournalEntryLinePayload[];
}

export const accountingService = {
  listAccounts(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<ChartOfAccount> | ChartOfAccount[]>('/accounting/accounts', { params });
  },
  createAccount(payload: AccountPayload) {
    return http.post<ChartOfAccount>('/accounting/accounts', payload);
  },
  updateAccount(id: number, payload: Partial<AccountPayload>) {
    return http.put<ChartOfAccount>(`/accounting/accounts/${id}`, payload);
  },

  listPeriods(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<AccountingPeriod> | AccountingPeriod[]>('/accounting/periods', { params });
  },
  closePeriod(id: number) {
    return http.patch(`/accounting/periods/${id}/close`);
  },

  listJournalEntries(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<JournalEntry> | JournalEntry[]>('/accounting/journal-entries', { params });
  },
  createJournalEntry(payload: JournalEntryPayload) {
    return http.post<JournalEntry>('/accounting/journal-entries', payload);
  },
  postJournalEntry(id: number) {
    return http.patch<JournalEntry>(`/accounting/journal-entries/${id}/post`);
  },
};
