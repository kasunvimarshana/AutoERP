import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createLocallyFilteredLookupLoader, createQueryCachedLookupLoader } from './lookupCache';
import { useLookupCacheStore } from '@/shared/state/lookupCacheStore';
import type { NamedResource } from '@/shared/types/common';

function lookupMeta(total: number, perPage: number, currentPage = 1, lastPage = 1) {
    return {
        current_page: currentPage,
        from: total > 0 ? 1 : null,
        last_page: lastPage,
        per_page: perPage,
        to: total > 0 ? Math.min(perPage, total) : null,
        total,
    };
}

describe('lookupCache', () => {
    beforeEach(() => {
        useLookupCacheStore.getState().clear();
        window.localStorage.clear();
        window.localStorage.setItem('autoerp.auth_session', 'session');
        window.localStorage.setItem('autoerp.auth_mode', 'tenant');
        window.localStorage.setItem('autoerp.tenant_id', '7');
    });

    it('reuses a locally cached dataset across searches and loader instances', async () => {
        const load = vi.fn(async ({ page, perPage }: { page: number; perPage: number }) => {
            const all: NamedResource[] = [
                { id: 1, code: 'PCS', name: 'Pieces' },
                { id: 2, code: 'KG', name: 'Kilogram' },
            ];
            const data = page === 1 ? [all[0]] : [all[1]];

            return {
                data,
                meta: lookupMeta(all.length, perPage, page, 2),
                links: undefined,
            };
        });

        const firstLoader = createLocallyFilteredLookupLoader<NamedResource>({
            key: 'lookup:uoms',
            load: load as never,
        });
        const secondLoader = createLocallyFilteredLookupLoader<NamedResource>({
            key: 'lookup:uoms',
            load: load as never,
        });

        const first = await firstLoader({
            search: '',
            page: 1,
            perPage: 20,
            signal: new AbortController().signal,
        });
        const filtered = await secondLoader({
            search: 'kg',
            page: 1,
            perPage: 20,
            signal: new AbortController().signal,
        });

        expect(load).toHaveBeenCalledTimes(2);
        expect(first.data).toHaveLength(2);
        expect(filtered.data).toEqual([{ id: 2, code: 'KG', name: 'Kilogram' }]);
    });

    it('reuses cached query results across loader instances with the same shared key', async () => {
        const load = vi.fn(async ({ search, perPage }: { search: string; perPage: number }) => ({
            data: [{ id: 1, code: 'CUS-001', name: `Customer ${search}` }],
            meta: lookupMeta(1, perPage),
            links: undefined,
        }));

        const firstLoader = createQueryCachedLookupLoader<NamedResource>({
            key: 'lookup:customers:active',
            load: load as never,
        });
        const secondLoader = createQueryCachedLookupLoader<NamedResource>({
            key: 'lookup:customers:active',
            load: load as never,
        });

        const params = {
            search: 'amal',
            page: 1,
            perPage: 20,
            signal: new AbortController().signal,
        };

        const first = await firstLoader(params);
        const second = await secondLoader(params);

        expect(load).toHaveBeenCalledTimes(1);
        expect(first.data).toEqual(second.data);
    });
});
