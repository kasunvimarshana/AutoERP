import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { GoodsReceipt, GoodsReceiptPayload, ReturnableLine } from '../purchaseTypes';

export async function listGoodsReceipts(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts`, { params, signal });
    return response.data;
}

export async function getGoodsReceipt(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}`, { signal });
    return response.data.data;
}

export async function createGoodsReceipt(payload: GoodsReceiptPayload) {
    const response = await apiClient.post<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts`, payload);
    return response.data.data;
}

export async function postGoodsReceipt(id: number) {
    const response = await apiClient.patch<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}/post`);
    return response.data.data;
}

export async function reverseGoodsReceipt(id: number) {
    const response = await apiClient.patch<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}/reverse`);
    return response.data.data;
}

export async function getReturnableGoodsReceiptLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<ReturnableLine[]>>(
        `${endpoints.purchase}/goods-receipts/${id}/returnable-lines`,
        { signal },
    );
    return response.data.data;
}

export async function searchGoodsReceipts(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listGoodsReceipts({ search, per_page: 20 }, signal);
    return response.data.map((grn) => ({
        id: grn.id,
        code: grn.grn_number,
        name: `${grn.grn_number ?? 'Goods receipt'}${grn.supplier?.name ? ` - ${grn.supplier.name}` : ''}`,
    }));
}
