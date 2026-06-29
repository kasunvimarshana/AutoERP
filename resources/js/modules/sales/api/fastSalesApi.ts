import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type { FastSalesContext } from '../fastSalesContextTypes';
import type { FastSalesPayload } from '../fastSalesPayloadTypes';
import type { FastSalesResult } from '../fastSalesResultTypes';

export async function getFastSalesContext(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<FastSalesContext>>(`${endpoints.sales}/fast-sales/context`, { signal });
    return response.data.data;
}

export async function previewFastSales(payload: FastSalesPayload, signal?: AbortSignal) {
    const response = await apiClient.post<ApiResource<FastSalesResult>>(`${endpoints.sales}/fast-sales/preview`, payload, { signal });
    return response.data.data;
}

export async function createFastSales(payload: FastSalesPayload) {
    const response = await apiClient.post<ApiResource<FastSalesResult>>(`${endpoints.sales}/fast-sales`, payload, {
        headers: payload.idempotency_key ? { 'Idempotency-Key': payload.idempotency_key } : undefined,
    });
    return response.data.data;
}
