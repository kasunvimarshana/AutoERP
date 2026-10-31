import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { InventoryAvailability } from '@/modules/inventory/inventoryTypes';

export interface ConsumableUsage {
    id: number;
    number: string;
    date: string;
    item: NamedResource;
    warehouse: NamedResource;
    location: NamedResource | null;
    variant: NamedResource | null;
    batch: NamedResource | null;
    uom: NamedResource | null;
    quantity: string;
    total_cost: string;
    status: 'posted' | 'reversed';
    notes: string | null;
}

export interface ConsumableUsagePayload {
    usage_date: string;
    item_id: number;
    warehouse_id: number;
    warehouse_location_id?: number;
    item_variant_id?: number;
    batch_id?: number;
    uom_id?: number;
    quantity: string;
    notes?: string;
}

export type ConsumableUsageCollection = ApiCollection<ConsumableUsage> & {
    summary: {
        from: string;
        to: string;
        items: Array<{ item: NamedResource; quantity: string; uom: string | null; cost: string }>;
    };
};

const url = `${endpoints.inventory}/consumable-usage`;
const retainedKeys = new Map<string, string>();

export async function listConsumableUsage(params: { from: string; to: string; item_id?: number; page?: number; per_page?: number }, signal?: AbortSignal) {
    const response = await apiClient.get<ConsumableUsageCollection>(url, { params, signal });
    return response.data;
}

export async function postConsumableUsage(payload: ConsumableUsagePayload) {
    const fingerprint = JSON.stringify(payload);
    const key = retainedKeys.get(fingerprint) ?? globalThis.crypto.randomUUID();
    retainedKeys.set(fingerprint, key);
    const response = await apiClient.post<ApiResource<ConsumableUsage>>(url, payload, { headers: { 'Idempotency-Key': key } });
    retainedKeys.delete(fingerprint);
    return response.data.data;
}

export async function reverseConsumableUsage(id: number, reason: string) {
    const response = await apiClient.post<ApiResource<ConsumableUsage>>(`${url}/${id}/reverse`, { reason });
    return response.data.data;
}

export async function getConsumableAvailability(params: { item_id: number; warehouse_id: number; warehouse_location_id?: number; item_variant_id?: number; batch_id?: number }, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<InventoryAvailability>>(`${url}/availability`, { params, signal });
    return response.data.data;
}
