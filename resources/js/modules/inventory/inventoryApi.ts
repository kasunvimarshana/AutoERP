import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface StockBalance extends Record<string, unknown> {
    id: number;
    item?: { id: number; name: string; code?: string };
    warehouse?: { id: number; name: string; code?: string };
    quantity_on_hand?: string;
    quantity_reserved?: string;
    quantity_available?: string;
}

export async function listStockBalances(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<StockBalance>>(`${endpoints.inventory}/stock-balances`, { params, signal });
    return response.data;
}

export async function getAvailability(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.inventory}/availability`, { params, signal });
    return response.data.data;
}
