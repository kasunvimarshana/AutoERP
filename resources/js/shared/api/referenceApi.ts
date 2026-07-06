import { endpoints } from './endpoints';
import { createLocallyFilteredLookupLoader, createQueryCachedLookupLoader } from './lookupCache';
import { requestLookup } from './lookupRequest';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';

const cachedUomLookup = createLocallyFilteredLookupLoader<NamedResource>({
    key: 'lookup:uoms',
    load: (params) => requestLookup<NamedResource>(`${endpoints.uoms}/lookup`, params),
});

export function listUoms(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return cachedUomLookup(params);
}

const cachedWarehouseLookup = createQueryCachedLookupLoader<NamedResource>({
    key: 'lookup:warehouses:active',
    load: (params) => requestLookup<NamedResource>(endpoints.warehouses, params, { is_active: true }),
});

export function searchWarehouses(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return cachedWarehouseLookup(params);
}

export function searchWarehouseLocations(
    params: LookupLoadParams,
    warehouseId?: number | null,
): Promise<LookupResult<NamedResource>> {
    const loader = createQueryCachedLookupLoader<NamedResource>({
        key: `lookup:warehouse-locations:${warehouseId ?? 'all'}:active`,
        load: (lookupParams) => requestLookup<NamedResource>(endpoints.warehouseLocations, lookupParams, {
            warehouse_id: warehouseId ?? undefined,
            is_active: true,
        }),
    });

    return loader(params);
}

const cachedCurrencyLookup = createLocallyFilteredLookupLoader<NamedResource>({
    key: 'lookup:currencies',
    load: (params) => requestLookup<NamedResource>(endpoints.currencies, params),
});

export function searchCurrencies(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return cachedCurrencyLookup(params);
}
