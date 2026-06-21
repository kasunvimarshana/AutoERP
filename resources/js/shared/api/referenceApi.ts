import { endpoints } from './endpoints';
import { requestLookup } from './lookupRequest';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';

export function listUoms(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return requestLookup<NamedResource>(`${endpoints.uoms}/lookup`, params);
}

export function searchWarehouses(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return requestLookup<NamedResource>(endpoints.warehouses, params, { is_active: true });
}

export function searchWarehouseLocations(
    params: LookupLoadParams,
    warehouseId?: number | null,
): Promise<LookupResult<NamedResource>> {
    return requestLookup<NamedResource>(endpoints.warehouseLocations, params, {
        warehouse_id: warehouseId ?? undefined,
        is_active: true,
    });
}

export function searchCurrencies(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return requestLookup<NamedResource>(endpoints.currencies, params);
}
