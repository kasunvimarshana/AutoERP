import { endpoints } from './endpoints';
import { mapLookupResult, requestLookup } from './lookupRequest';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { PaginationMeta } from '@/shared/types/pagination';

export interface VehicleLookupResource extends NamedResource {
    registration_number?: string | null;
    make?: NamedResource | null;
    model?: NamedResource | null;
    current_ownerships?: Array<{
        owner_type: string;
        owner_id?: number | null;
        owner?: NamedResource | null;
    }>;
    current_customer?: { id: number; code: string; name: string } | null;
    current_supplier?: { id: number; code: string; name: string } | null;
    odometer_reading?: string | null;
    odometer_unit?: string | null;
}

export interface ItemLookupResource extends NamedResource {
    base_uom?: NamedResource | null;
    is_stockable?: boolean;
    resolved_service_unit_price?: string | null;
    resolved_purchase_unit_price?: string | null;
    available_stock_quantity?: string | null;
}

const lookup = <T extends NamedResource = NamedResource>(
    url: string,
    params: LookupLoadParams,
): Promise<LookupResult<T>> =>
    requestLookup<T>(url, params);

const itemLookup = (url: string, params: LookupLoadParams): Promise<LookupResult<ItemLookupResource>> =>
    requestLookup<ItemLookupResource>(url, params);

async function mappedLookup<T extends NamedResource>(
    url: string,
    params: LookupLoadParams,
    map: (resource: Record<string, unknown>) => T,
    filters: Record<string, string | number | boolean | null | undefined> = {},
): Promise<LookupResult<T>> {
    const result = await requestLookup<Record<string, unknown>>(url, params, filters);
    return mapLookupResult(result, map);
}

export const lookupApi = {
    items: (params: LookupLoadParams) => itemLookup(`${endpoints.items}/lookup`, params),
    stockableItems: (params: LookupLoadParams) => itemLookup(`${endpoints.items}/lookup/stockable`, params),
    serviceItems: (params: LookupLoadParams) => itemLookup(`${endpoints.items}/lookup/service`, params),
    labourItems: (params: LookupLoadParams) => itemLookup(`${endpoints.items}/lookup/labour`, params),
    comboItems: async (params: LookupLoadParams): Promise<LookupResult<ItemLookupResource>> => {
        const [combos, packages] = await Promise.all([
            itemLookup(`${endpoints.items}/lookup/combo`, params),
            itemLookup(`${endpoints.items}/lookup/package`, params),
        ]);
        const data = dedupeById([...combos.data, ...packages.data]);

        return {
            data,
            meta: combineMeta(params, data.length, combos.meta, packages.meta),
        };
    },
    suppliers: (params: LookupLoadParams) => lookup(`${endpoints.suppliers}/lookup`, params),
    customers: (params: LookupLoadParams) => lookup(`${endpoints.customers}/lookup/active`, params),
    availableEmployees: (params: LookupLoadParams) => mappedLookup(
        `${endpoints.hrEmployees}/lookup/available`,
        params,
        (resource) => ({
            id: Number(resource.id),
            code: String(resource.employee_number ?? resource.code ?? ''),
            name: String(resource.display_name ?? resource.name ?? ''),
        }),
    ),
    serviceVehicles: (params: LookupLoadParams, customerId?: number | null): Promise<LookupResult<VehicleLookupResource>> => mappedLookup(
        `${endpoints.vehicles}/lookup/service-available`,
        params,
        (resource) => ({
            id: Number(resource.id),
            code: String(resource.vehicle_number ?? resource.code ?? ''),
            name: String(resource.registration_number ?? resource.vehicle_number ?? resource.name ?? ''),
            registration_number: typeof resource.registration_number === 'string' ? resource.registration_number : null,
            make: resource.make as NamedResource | null | undefined,
            model: resource.model as NamedResource | null | undefined,
            current_ownerships: resource.current_ownerships as VehicleLookupResource['current_ownerships'],
            current_customer: resource.current_customer as VehicleLookupResource['current_customer'],
            current_supplier: resource.current_supplier as VehicleLookupResource['current_supplier'],
            odometer_reading: typeof resource.odometer_reading === 'string' ? resource.odometer_reading : null,
            odometer_unit: typeof resource.odometer_unit === 'string' ? resource.odometer_unit : null,
        }),
        customerId ? { customer_id: customerId } : {},
    ),
};

function dedupeById<T extends NamedResource>(options: T[]): T[] {
    const seen = new Set<number>();
    return options.filter((option) => {
        const id = Number(option.id);
        if (seen.has(id)) return false;
        seen.add(id);
        return true;
    });
}

function combineMeta(
    params: LookupLoadParams,
    count: number,
    first?: PaginationMeta,
    second?: PaginationMeta,
): PaginationMeta | undefined {
    if (!first && !second) return undefined;

    const from = count > 0 ? ((params.page - 1) * params.perPage) + 1 : null;
    return {
        current_page: params.page,
        from,
        last_page: Math.max(first?.last_page ?? params.page, second?.last_page ?? params.page),
        per_page: params.perPage,
        to: from === null ? null : from + count - 1,
        total: (first?.total ?? 0) + (second?.total ?? 0),
    };
}
