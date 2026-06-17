import type { ApiCollection } from './api';

export interface LookupLoadParams {
    search: string;
    page: number;
    perPage: number;
    signal: AbortSignal;
}

export type LookupResult<T> = Pick<ApiCollection<T>, 'data' | 'links' | 'meta'>;

export type LookupLoader<T> = (params: LookupLoadParams) => Promise<LookupResult<T>>;

export interface LookupBehaviorOptions {
    minSearchLength?: number;
    loadOnOpen?: boolean;
    perPage?: number;
    debounceMs?: number;
}
