import { apiClient } from './apiClient';
import { endpoints } from './endpoints';
import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export interface VehicleLookupResource extends NamedResource {
    registration_number?: string | null;
    make?: NamedResource | null;
    model?: NamedResource | null;
    customer?: NamedResource | null;
    odometer_reading?: string | null;
    odometer_unit?: string | null;
}

async function lookup(url: string, search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(url, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

async function mappedLookup<T extends NamedResource>(
    url: string,
    search: string,
    signal: AbortSignal | undefined,
    map: (resource: Record<string, unknown>) => T,
    params: Record<string, string | number | undefined> = {},
): Promise<T[]> {
    const response = await apiClient.get<ApiCollection<Record<string, unknown>>>(url, {
        params: { search, per_page: 20, ...params },
        signal,
    });
    return response.data.data.map(map);
}

export const lookupApi = {
    items: (search: string, signal?: AbortSignal) => lookup(`${endpoints.items}/lookup`, search, signal),
    stockableItems: (search: string, signal?: AbortSignal) => lookup(`${endpoints.items}/lookup/stockable`, search, signal),
    serviceItems: (search: string, signal?: AbortSignal) => lookup(`${endpoints.items}/lookup/service`, search, signal),
    labourItems: (search: string, signal?: AbortSignal) => lookup(`${endpoints.items}/lookup/labour`, search, signal),
    comboItems: async (search: string, signal?: AbortSignal) => {
        const [combos, packages] = await Promise.all([
            lookup(`${endpoints.items}/lookup/combo`, search, signal),
            lookup(`${endpoints.items}/lookup/package`, search, signal),
        ]);
        return [...new Map([...combos, ...packages].map((item) => [item.id, item])).values()];
    },
    suppliers: (search: string, signal?: AbortSignal) => lookup(`${endpoints.suppliers}/lookup`, search, signal),
    customers: (search: string, signal?: AbortSignal) => lookup(`${endpoints.customers}/lookup/active`, search, signal),
    availableEmployees: (search: string, signal?: AbortSignal) => mappedLookup(
        `${endpoints.hrEmployees}/lookup/available`,
        search,
        signal,
        (resource) => ({
            id: Number(resource.id),
            code: String(resource.employee_number ?? resource.code ?? ''),
            name: String(resource.display_name ?? resource.name ?? ''),
        }),
    ),
    serviceVehicles: (customerId: number, search: string, signal?: AbortSignal): Promise<VehicleLookupResource[]> => mappedLookup(
        `${endpoints.vehicles}/lookup/service-available`,
        search,
        signal,
        (resource) => ({
            id: Number(resource.id),
            code: String(resource.vehicle_number ?? resource.code ?? ''),
            name: String(resource.registration_number ?? resource.vehicle_number ?? resource.name ?? ''),
            registration_number: typeof resource.registration_number === 'string' ? resource.registration_number : null,
            make: resource.make as NamedResource | null | undefined,
            model: resource.model as NamedResource | null | undefined,
            customer: resource.customer as NamedResource | null | undefined,
            odometer_reading: typeof resource.odometer_reading === 'string' ? resource.odometer_reading : null,
            odometer_unit: typeof resource.odometer_unit === 'string' ? resource.odometer_unit : null,
        }),
        { customer_id: customerId },
    ),
};
