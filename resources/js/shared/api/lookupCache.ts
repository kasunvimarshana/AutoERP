import { getStoredApiContext } from './authSessionStorage';
import { registerLookupCacheLifecycle, useLookupCacheStore } from '@/shared/state/lookupCacheStore';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupLoader, LookupResult } from '@/shared/types/lookup';
import type { PaginationMeta } from '@/shared/types/pagination';

const DEFAULT_LOCAL_CACHE_TTL_MS = 15 * 60 * 1000;
const DEFAULT_QUERY_CACHE_TTL_MS = 60 * 1000;
const DEFAULT_FULL_LOAD_PAGE_SIZE = 100;

const inFlightQueries = new Map<string, Promise<LookupResult<unknown>>>();
const inFlightDatasets = new Map<string, Promise<unknown[]>>();

registerLookupCacheLifecycle();

export function createQueryCachedLookupLoader<T>({
    key,
    load,
    ttlMs = DEFAULT_QUERY_CACHE_TTL_MS,
}: {
    key: string;
    load: LookupLoader<T>;
    ttlMs?: number;
}): LookupLoader<T> {
    return async (params: LookupLoadParams): Promise<LookupResult<T>> => {
        const cacheKey = `${scopedKey(key)}::${queryKey(params)}`;
        const cached = useLookupCacheStore.getState().queryEntries[cacheKey];
        if (cached && !isExpired(cached.fetchedAt, ttlMs)) {
            return cached.result as LookupResult<T>;
        }

        const existing = inFlightQueries.get(cacheKey);
        if (existing) {
            return existing as Promise<LookupResult<T>>;
        }

        const request = load(params).then((result) => {
            useLookupCacheStore.getState().setQueryEntry(cacheKey, result as LookupResult<unknown>);
            inFlightQueries.delete(cacheKey);

            return result;
        }).catch((error) => {
            inFlightQueries.delete(cacheKey);
            throw error;
        });

        inFlightQueries.set(cacheKey, request as Promise<LookupResult<unknown>>);

        return request;
    };
}

export function createLocallyFilteredLookupLoader<T extends NamedResource>({
    key,
    load,
    ttlMs = DEFAULT_LOCAL_CACHE_TTL_MS,
    pageSize = DEFAULT_FULL_LOAD_PAGE_SIZE,
}: {
    key: string;
    load: LookupLoader<T>;
    ttlMs?: number;
    pageSize?: number;
}): LookupLoader<T> {
    return async (params: LookupLoadParams): Promise<LookupResult<T>> => {
        const datasetKey = scopedKey(key);
        const cached = useLookupCacheStore.getState().datasetEntries[datasetKey];
        const data = cached && !isExpired(cached.fetchedAt, ttlMs)
            ? cached.data as T[]
            : await ensureDataset(datasetKey, load, pageSize, params.signal);

        return paginateLocally(filterNamedResources(data, params.search), params.page, params.perPage);
    };
}

export async function prefetchLocallyFilteredLookupDataset<T extends NamedResource>({
    key,
    load,
    signal,
    ttlMs = DEFAULT_LOCAL_CACHE_TTL_MS,
    pageSize = DEFAULT_FULL_LOAD_PAGE_SIZE,
}: {
    key: string;
    load: LookupLoader<T>;
    signal: AbortSignal;
    ttlMs?: number;
    pageSize?: number;
}): Promise<T[]> {
    const datasetKey = scopedKey(key);
    const cached = useLookupCacheStore.getState().datasetEntries[datasetKey];
    if (cached && !isExpired(cached.fetchedAt, ttlMs)) {
        return cached.data as T[];
    }

    return ensureDataset(datasetKey, load, pageSize, signal);
}

function scopedKey(key: string): string {
    const context = getStoredApiContext();
    const scope = context.authMode === 'platform'
        ? 'platform'
        : `tenant:${context.tenantId ?? 'none'}`;

    return `${scope}::${key}`;
}

function queryKey(params: LookupLoadParams): string {
    return [
        `search=${params.search.trim().toLowerCase()}`,
        `page=${params.page}`,
        `perPage=${params.perPage}`,
    ].join('&');
}

async function ensureDataset<T extends NamedResource>(
    datasetKey: string,
    load: LookupLoader<T>,
    pageSize: number,
    signal: AbortSignal,
): Promise<T[]> {
    const existing = inFlightDatasets.get(datasetKey);
    if (existing) {
        return existing as Promise<T[]>;
    }

    const request = fetchAllLookupPages(load, pageSize, signal)
        .then((data) => {
            useLookupCacheStore.getState().setDatasetEntry(datasetKey, data as unknown[]);
            inFlightDatasets.delete(datasetKey);

            return data;
        })
        .catch((error) => {
            inFlightDatasets.delete(datasetKey);
            throw error;
        });

    inFlightDatasets.set(datasetKey, request as Promise<unknown[]>);

    return request;
}

async function fetchAllLookupPages<T extends NamedResource>(
    load: LookupLoader<T>,
    pageSize: number,
    signal: AbortSignal,
): Promise<T[]> {
    const rows: T[] = [];
    let page = 1;
    let lastPage = 1;

    do {
        const result = await load({ search: '', page, perPage: pageSize, signal });
        rows.push(...result.data);
        lastPage = result.meta?.last_page ?? 1;
        page += 1;
    } while (page <= lastPage);

    return dedupeById(rows);
}

function filterNamedResources<T extends NamedResource>(options: T[], search: string): T[] {
    const term = search.trim().toLowerCase();
    if (term === '') return options;

    return options.filter((option) => [
        option.code,
        option.name,
        option.symbol,
    ].some((value) => typeof value === 'string' && value.toLowerCase().includes(term)));
}

function paginateLocally<T>(data: T[], page: number, perPage: number): LookupResult<T> {
    const currentPage = Math.max(page, 1);
    const start = (currentPage - 1) * perPage;
    const paged = data.slice(start, start + perPage);
    const from = paged.length > 0 ? start + 1 : null;
    const meta: PaginationMeta = {
        current_page: currentPage,
        from,
        last_page: Math.max(1, Math.ceil(data.length / perPage)),
        per_page: perPage,
        to: from === null ? null : from + paged.length - 1,
        total: data.length,
    };

    return {
        data: paged,
        meta,
        links: undefined,
    };
}

function dedupeById<T extends { id: number | string }>(rows: T[]): T[] {
    const seen = new Set<string>();

    return rows.filter((row) => {
        const key = String(row.id);
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
    });
}

function isExpired(fetchedAt: number, ttlMs: number): boolean {
    return (Date.now() - fetchedAt) > ttlMs;
}
