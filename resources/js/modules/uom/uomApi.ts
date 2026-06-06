import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type {
    UnitOfMeasure,
    UomConversion,
    UomConversionPayload,
    UomConvertResult,
    UomListResponse,
    UomPayload,
    UomSummary,
} from './uomTypes';

export async function listUoms(params: ListParams, signal?: AbortSignal): Promise<UomListResponse<UnitOfMeasure>> {
    const response = await apiClient.get<ApiCollection<UnitOfMeasure>>(endpoints.uoms, { params, signal });
    return withMeta(response.data);
}

export async function getUom(id: number, signal?: AbortSignal): Promise<UnitOfMeasure> {
    const response = await apiClient.get<ApiResource<UnitOfMeasure>>(`${endpoints.uoms}/${id}`, { signal });
    return response.data.data;
}

export async function createUom(payload: UomPayload): Promise<UnitOfMeasure> {
    const response = await apiClient.post<ApiResource<UnitOfMeasure>>(endpoints.uoms, payload);
    return response.data.data;
}

export async function updateUom(id: number, payload: Partial<UomPayload>): Promise<UnitOfMeasure> {
    const response = await apiClient.put<ApiResource<UnitOfMeasure>>(`${endpoints.uoms}/${id}`, payload);
    return response.data.data;
}

export async function deactivateUom(id: number): Promise<UnitOfMeasure> {
    const response = await apiClient.patch<ApiResource<UnitOfMeasure>>(`${endpoints.uoms}/${id}/deactivate`);
    return response.data.data;
}

export async function searchUoms(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<UnitOfMeasure>>(`${endpoints.uoms}/lookup`, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data.map((uom) => ({ id: uom.id, name: `${uom.code} - ${uom.name}`, code: uom.symbol ?? uom.code }));
}

export async function listUomConversions(params: ListParams, signal?: AbortSignal): Promise<UomListResponse<UomConversion>> {
    const response = await apiClient.get<ApiCollection<UomConversion>>(endpoints.uomConversions, { params, signal });
    return withMeta(response.data);
}

export async function getUomConversion(id: number, signal?: AbortSignal): Promise<UomConversion> {
    const response = await apiClient.get<ApiResource<UomConversion>>(`${endpoints.uomConversions}/${id}`, { signal });
    return response.data.data;
}

export async function createUomConversion(payload: UomConversionPayload): Promise<UomConversion> {
    const response = await apiClient.post<ApiResource<UomConversion>>(endpoints.uomConversions, payload);
    return response.data.data;
}

export async function updateUomConversion(id: number, payload: Partial<UomConversionPayload>): Promise<UomConversion> {
    const response = await apiClient.put<ApiResource<UomConversion>>(`${endpoints.uomConversions}/${id}`, payload);
    return response.data.data;
}

export async function convertUom(payload: { from_uom_id: number; to_uom_id: number; quantity: string }): Promise<UomConvertResult> {
    const response = await apiClient.post<UomConvertResult>(`${endpoints.uomConversions}/convert`, payload);
    return response.data;
}

export function namedToUomSummary(resource: NamedResource | null): UomSummary | null {
    if (!resource) return null;
    return { id: Number(resource.id), code: resource.code ?? String(resource.id), name: resource.name };
}

function withMeta<T>(response: ApiCollection<T>): UomListResponse<T> {
    const meta = response.meta as (typeof response.meta & { page?: number; page_count?: number }) | undefined;
    if (!meta) return { data: response.data };

    const currentPage = meta.current_page ?? meta.page ?? 1;
    const perPage = meta.per_page ?? response.data.length;
    const total = meta.total ?? response.data.length;
    const lastPage = meta.last_page ?? meta.page_count ?? Math.max(1, Math.ceil(total / Math.max(1, perPage)));

    return {
        data: response.data,
        meta: {
            current_page: currentPage,
            from: total === 0 ? null : (currentPage - 1) * perPage + 1,
            last_page: lastPage,
            per_page: perPage,
            to: total === 0 ? null : Math.min(currentPage * perPage, total),
            total,
        },
    };
}
