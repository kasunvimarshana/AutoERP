import { apiClient } from './apiClient';
import { endpoints } from './endpoints';
import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

async function lookup(url: string, search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(url, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

async function mappedLookup(
    url: string,
    search: string,
    signal: AbortSignal | undefined,
    map: (resource: Record<string, unknown>) => NamedResource,
    params: Record<string, string | number | undefined> = {},
): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<Record<string, unknown>>>(url, {
        params: { search, per_page: 20, ...params },
        signal,
    });
    return response.data.data.map(map);
}

export const lookupApi = {
    items: (search: string, signal?: AbortSignal) => lookup(`${endpoints.items}/lookup`, search, signal),
    stockableItems: (search: string, signal?: AbortSignal) => lookup(`${endpoints.items}/lookup/stockable`, search, signal),
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
    serviceVehicles: (customerId: number, search: string, signal?: AbortSignal) => mappedLookup(
        `${endpoints.vehicles}/lookup/service-available`,
        search,
        signal,
        (resource) => ({
            id: Number(resource.id),
            code: String(resource.vehicle_number ?? resource.code ?? ''),
            name: String(resource.registration_number ?? resource.vehicle_number ?? resource.name ?? ''),
        }),
        { customer_id: customerId },
    ),
};
