import { create } from 'zustand';
import { AUTH_SESSION_INVALIDATED_EVENT } from '@/shared/api/authSessionStorage';
import type { LookupResult } from '@/shared/types/lookup';

interface LookupQueryCacheEntry {
    result: LookupResult<unknown>;
    fetchedAt: number;
}

interface LookupDatasetCacheEntry {
    data: unknown[];
    fetchedAt: number;
}

interface LookupRecentResultsEntry {
    options: unknown[];
    updatedAt: number;
}

interface LookupCacheState {
    queryEntries: Record<string, LookupQueryCacheEntry>;
    datasetEntries: Record<string, LookupDatasetCacheEntry>;
    recentEntries: Record<string, LookupRecentResultsEntry>;
    setQueryEntry: (key: string, result: LookupResult<unknown>) => void;
    setDatasetEntry: (key: string, data: unknown[]) => void;
    setRecentEntry: (key: string, options: unknown[]) => void;
    clear: () => void;
}

export const useLookupCacheStore = create<LookupCacheState>((set) => ({
    queryEntries: {},
    datasetEntries: {},
    recentEntries: {},
    setQueryEntry: (key, result) => set((state) => ({
        queryEntries: {
            ...state.queryEntries,
            [key]: { result, fetchedAt: Date.now() },
        },
    })),
    setDatasetEntry: (key, data) => set((state) => ({
        datasetEntries: {
            ...state.datasetEntries,
            [key]: { data, fetchedAt: Date.now() },
        },
    })),
    setRecentEntry: (key, options) => set((state) => ({
        recentEntries: {
            ...state.recentEntries,
            [key]: { options, updatedAt: Date.now() },
        },
    })),
    clear: () => set({ queryEntries: {}, datasetEntries: {}, recentEntries: {} }),
}));

let listenersRegistered = false;

export function registerLookupCacheLifecycle(): void {
    if (listenersRegistered || typeof window === 'undefined') return;

    window.addEventListener(AUTH_SESSION_INVALIDATED_EVENT, () => {
        useLookupCacheStore.getState().clear();
    });

    listenersRegistered = true;
}
