import { apiClient } from './apiClient';
import type { ApiCollection } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';

type LookupQueryValue = string | number | boolean | null | undefined;
type LookupQueryParams = Record<string, LookupQueryValue>;

export async function requestLookup<T>(
    url: string,
    { search, page, perPage, signal }: LookupLoadParams,
    params: LookupQueryParams = {},
): Promise<LookupResult<T>> {
    const response = await apiClient.get<ApiCollection<T>>(url, {
        params: { ...params, search, page, per_page: perPage },
        signal,
    });

    return response.data;
}

export function mapLookupResult<TSource, TTarget>(
    result: LookupResult<TSource>,
    map: (resource: TSource) => TTarget,
): LookupResult<TTarget> {
    return {
        data: result.data.map(map),
        links: result.links,
        meta: result.meta,
    };
}
