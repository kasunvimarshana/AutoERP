import { apiClient, unwrapPaginated } from '../../api/client';
import type { ApiPaginatedEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    AccountFilters,
    AccountRecord,
    BalanceSheetFilters,
    BalanceSheetReport,
    GeneralLedgerFilters,
    GeneralLedgerReport,
    JournalEntryFilters,
    JournalEntryRecord,
    PaymentFilters,
    PaymentRecord,
    ProfitLossFilters,
    ProfitLossReport,
    TrialBalanceFilters,
    TrialBalanceReport,
} from './types';

export const financeApi = {
    listAccounts(filters: AccountFilters): Promise<PaginatedResult<AccountRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<AccountRecord>>('/accounts', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    listJournalEntries(filters: JournalEntryFilters): Promise<PaginatedResult<JournalEntryRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<JournalEntryRecord>>('/journal-entries', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
    listPayments(filters: PaymentFilters): Promise<PaginatedResult<PaymentRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<PaymentRecord>>('/payments', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getGeneralLedger(filters: GeneralLedgerFilters) {
        return apiClient.get<GeneralLedgerReport>('/reports/general-ledger', { query: toQuery(filters) });
    },
    getTrialBalance(filters: TrialBalanceFilters) {
        return apiClient.get<TrialBalanceReport>('/reports/trial-balance', { query: toQuery(filters) });
    },
    getBalanceSheet(filters: BalanceSheetFilters) {
        return apiClient.get<BalanceSheetReport>('/reports/balance-sheet', { query: toQuery(filters) });
    },
    getProfitLoss(filters: ProfitLossFilters) {
        return apiClient.get<ProfitLossReport>('/reports/profit-loss', { query: toQuery(filters) });
    },
};
