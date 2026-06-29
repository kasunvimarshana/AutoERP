import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type { FastPurchaseContext, FastPurchasePayload, FastPurchaseResult } from '../types/fastPurchaseTypes';

export type { FastPurchaseContext, FastPurchasePayload, FastPurchaseResult } from '../types/fastPurchaseTypes';

export async function getFastPurchaseContext(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<FastPurchaseContext>>(`${endpoints.purchase}/fast-purchases/context`, { signal });
    return response.data.data;
}

export async function previewFastPurchase(payload: FastPurchasePayload, signal?: AbortSignal) {
    const response = await apiClient.post<ApiResource<FastPurchaseResult>>(`${endpoints.purchase}/fast-purchases/preview`, payload, { signal });
    return response.data.data;
}

export async function createFastPurchase(payload: FastPurchasePayload) {
    const response = await apiClient.post<ApiResource<FastPurchaseResult>>(`${endpoints.purchase}/fast-purchases`, payload);
    return response.data.data;
}
