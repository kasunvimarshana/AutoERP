import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { GoodsReceipt, GoodsReceiptPayload, InvoiceableGoodsReceiptLine, PurchaseActionPayload, ReturnableLine } from '../purchaseTypes';

export async function listGoodsReceipts(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts`, { params, signal });
    return response.data;
}

export async function listInvoiceableGoodsReceipts(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<GoodsReceipt>>(
        `${endpoints.purchase}/eligible/invoiceable-goods-receipts`,
        { params, signal },
    );
    return response.data;
}

export async function listReturnableGoodsReceipts(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<GoodsReceipt>>(
        `${endpoints.purchase}/eligible/returnable-goods-receipts`,
        { params, signal },
    );
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

export async function postGoodsReceipt(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}/post`, payload);
    return response.data.data;
}

export async function reverseGoodsReceipt(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}/reverse`, payload);
    return response.data.data;
}

export async function getReturnableGoodsReceiptLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<ReturnableLine[]>>(
        `${endpoints.purchase}/goods-receipts/${id}/returnable-lines`,
        { signal },
    );
    return response.data.data;
}

export async function getInvoiceableGoodsReceiptLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<InvoiceableGoodsReceiptLine[]>>(
        `${endpoints.purchase}/goods-receipts/${id}/invoiceable-lines`,
        { signal },
    );
    return response.data.data;
}

export async function searchGoodsReceipts({
    search,
    page,
    perPage,
    signal,
}: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const response = await listGoodsReceipts({ search, page, per_page: perPage }, signal);
    return {
        data: response.data.map((grn) => ({
            id: grn.id,
            code: grn.grn_number,
            name: `${grn.grn_number ?? 'Goods receipt'}${grn.supplier?.name ? ` - ${grn.supplier.name}` : ''}`,
        })),
        links: response.links,
        meta: response.meta,
    };
}

export async function searchInvoiceableGoodsReceipts({
    search,
    page,
    perPage,
    signal,
}: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const response = await listInvoiceableGoodsReceipts({ search, page, per_page: perPage }, signal);
    return goodsReceiptLookupResult(response);
}

export async function searchReturnableGoodsReceipts({
    search,
    page,
    perPage,
    signal,
}: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const response = await listReturnableGoodsReceipts({ search, page, per_page: perPage }, signal);
    return goodsReceiptLookupResult(response);
}

function goodsReceiptLookupResult(response: ApiCollection<GoodsReceipt>): LookupResult<NamedResource> {
    return {
        data: response.data.map((grn) => ({
            id: grn.id,
            code: grn.grn_number,
            name: `${grn.grn_number ?? 'Goods receipt'}${grn.supplier?.name ? ` - ${grn.supplier.name}` : ''}`,
        })),
        links: response.links,
        meta: response.meta,
    };
}
