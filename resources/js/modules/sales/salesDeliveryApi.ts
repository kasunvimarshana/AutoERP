import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type {
    ReturnableSalesLine,
    SalesDelivery,
    SalesDeliveryPayload,
} from './salesTypes';

const base = `${endpoints.sales}/deliveries`;

export async function listSalesDeliveries(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesDelivery>>(base, { params, signal })).data;
}

export async function getSalesDelivery(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesDelivery>>(`${base}/${id}`, { signal })).data.data;
}

export async function createSalesDelivery(payload: SalesDeliveryPayload) {
    return (await apiClient.post<ApiResource<SalesDelivery>>(base, payload)).data.data;
}

export async function postSalesDelivery(id: number) {
    return (await apiClient.patch<ApiResource<SalesDelivery>>(`${base}/${id}/post`)).data.data;
}

export async function reverseSalesDelivery(id: number) {
    return (await apiClient.patch<ApiResource<SalesDelivery>>(`${base}/${id}/reverse`)).data.data;
}

export async function getReturnableSalesDeliveryLines(id: number, signal?: AbortSignal) {
    return (
        await apiClient.get<ApiResource<ReturnableSalesLine[]>>(`${base}/${id}/returnable-lines`, {
            signal,
        })
    ).data.data;
}

export async function searchSalesDeliveries({
    search,
    page,
    perPage,
    signal,
}: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const response = await listSalesDeliveries({ search, page, per_page: perPage }, signal);

    return {
        data: response.data.map((delivery) => ({
            id: delivery.id,
            code: delivery.delivery_number,
            name: `${delivery.delivery_number ?? 'Delivery'}${
                delivery.customer?.name ? ` - ${delivery.customer.name}` : ''
            }`,
        })),
        links: response.links,
        meta: response.meta,
    };
}
