import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { Tax, TaxGroup, TaxLookups, TaxPayload, TaxPostingProfile, TaxProfile, TaxReportResult } from './taxTypes';

export async function getTaxLookups(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<TaxLookups>>(`${endpoints.tax}/lookups`, { signal });
    return response.data.data;
}

export async function listTaxes(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Tax>>(`${endpoints.tax}/taxes`, { params, signal });
    return response.data;
}

export async function getTax(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Tax>>(`${endpoints.tax}/taxes/${id}`, { signal });
    return response.data.data;
}

export async function createTax(payload: TaxPayload) {
    const response = await apiClient.post<ApiResource<Tax>>(`${endpoints.tax}/taxes`, payload);
    return response.data.data;
}

export async function updateTax(id: number, payload: TaxPayload) {
    const response = await apiClient.patch<ApiResource<Tax>>(`${endpoints.tax}/taxes/${id}`, payload);
    return response.data.data;
}

export async function addTaxRate(id: number, payload: { rate: string; effective_from: string; effective_to?: string | null; active: boolean }) {
    await apiClient.post(`${endpoints.tax}/taxes/${id}/rates`, payload);
}

export async function listTaxGroups(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<TaxGroup>>(`${endpoints.tax}/groups`, { params, signal });
    return response.data;
}

export async function saveTaxGroup(id: number | null, payload: Omit<TaxGroup, 'id'>) {
    const response = id
        ? await apiClient.patch<ApiResource<TaxGroup>>(`${endpoints.tax}/groups/${id}`, payload)
        : await apiClient.post<ApiResource<TaxGroup>>(`${endpoints.tax}/groups`, payload);
    return response.data.data;
}

export async function listCustomerTaxProfiles(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<TaxProfile>>(`${endpoints.tax}/customer-profiles`, { params, signal });
    return response.data;
}

export async function saveCustomerTaxProfile(id: number | null, payload: Record<string, unknown>) {
    const response = id
        ? await apiClient.patch<ApiResource<TaxProfile>>(`${endpoints.tax}/customer-profiles/${id}`, payload)
        : await apiClient.post<ApiResource<TaxProfile>>(`${endpoints.tax}/customer-profiles`, payload);
    return response.data.data;
}

export async function listSupplierTaxProfiles(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<TaxProfile>>(`${endpoints.tax}/supplier-profiles`, { params, signal });
    return response.data;
}

export async function saveSupplierTaxProfile(id: number | null, payload: Record<string, unknown>) {
    const response = id
        ? await apiClient.patch<ApiResource<TaxProfile>>(`${endpoints.tax}/supplier-profiles/${id}`, payload)
        : await apiClient.post<ApiResource<TaxProfile>>(`${endpoints.tax}/supplier-profiles`, payload);
    return response.data.data;
}

export async function listTaxPostingProfiles(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<TaxPostingProfile>>(`${endpoints.tax}/posting-profiles`, { params, signal });
    return response.data;
}

export async function saveTaxPostingProfile(id: number | null, payload: Record<string, unknown>) {
    const response = id
        ? await apiClient.patch<ApiResource<TaxPostingProfile>>(`${endpoints.tax}/posting-profiles/${id}`, payload)
        : await apiClient.post<ApiResource<TaxPostingProfile>>(`${endpoints.tax}/posting-profiles`, payload);
    return response.data.data;
}

export async function getTaxReport(report: string, params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<TaxReportResult>>(`${endpoints.tax}/reports/${report}`, { params, signal });
    return response.data.data;
}
