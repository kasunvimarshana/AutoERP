import { useQuery } from '@tanstack/react-query';
import { financeApi } from './api';
import type {
    AccountFilters,
    BalanceSheetFilters,
    GeneralLedgerFilters,
    JournalEntryFilters,
    PaymentFilters,
    ProfitLossFilters,
    TrialBalanceFilters,
} from './types';

const financeKeys = {
    all: ['finance'] as const,
    accounts: (filters: AccountFilters) => [...financeKeys.all, 'accounts', filters] as const,
    journalEntries: (filters: JournalEntryFilters) => [...financeKeys.all, 'journal-entries', filters] as const,
    payments: (filters: PaymentFilters) => [...financeKeys.all, 'payments', filters] as const,
    generalLedger: (filters: GeneralLedgerFilters) => [...financeKeys.all, 'reports', 'general-ledger', filters] as const,
    trialBalance: (filters: TrialBalanceFilters) => [...financeKeys.all, 'reports', 'trial-balance', filters] as const,
    balanceSheet: (filters: BalanceSheetFilters) => [...financeKeys.all, 'reports', 'balance-sheet', filters] as const,
    profitLoss: (filters: ProfitLossFilters) => [...financeKeys.all, 'reports', 'profit-loss', filters] as const,
};

export function useAccounts(filters: AccountFilters) {
    return useQuery({
        queryKey: financeKeys.accounts(filters),
        queryFn: () => financeApi.listAccounts(filters),
    });
}

export function useJournalEntries(filters: JournalEntryFilters) {
    return useQuery({
        queryKey: financeKeys.journalEntries(filters),
        queryFn: () => financeApi.listJournalEntries(filters),
    });
}

export function usePayments(filters: PaymentFilters) {
    return useQuery({
        queryKey: financeKeys.payments(filters),
        queryFn: () => financeApi.listPayments(filters),
    });
}

export function useGeneralLedgerReport(filters: GeneralLedgerFilters) {
    return useQuery({
        queryKey: financeKeys.generalLedger(filters),
        queryFn: () => financeApi.getGeneralLedger(filters),
    });
}

export function useTrialBalanceReport(filters: TrialBalanceFilters) {
    return useQuery({
        queryKey: financeKeys.trialBalance(filters),
        queryFn: () => financeApi.getTrialBalance(filters),
    });
}

export function useBalanceSheetReport(filters: BalanceSheetFilters) {
    return useQuery({
        queryKey: financeKeys.balanceSheet(filters),
        queryFn: () => financeApi.getBalanceSheet(filters),
    });
}

export function useProfitLossReport(filters: ProfitLossFilters) {
    return useQuery({
        queryKey: financeKeys.profitLoss(filters),
        queryFn: () => financeApi.getProfitLoss(filters),
    });
}
