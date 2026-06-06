import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface Payment extends Record<string, unknown> {
    id: number;
    payment_number?: string;
    payment_date?: string;
    payment_type?: string;
    direction?: string;
    status?: string;
    party?: { id: number; name: string };
    total_amount?: string;
    allocated_amount?: string;
    unapplied_amount?: string;
}

export async function listPayments(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Payment>>(endpoints.payments, { params, signal });
    return response.data;
}

export async function getPayment(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Payment>>(`${endpoints.payments}/${id}`, { signal });
    return response.data.data;
}

export async function getPaymentAllocations(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>[]>>(`${endpoints.payments}/${id}/allocations`, { signal });
    return response.data.data;
}

export async function getPaymentUnappliedBalance(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown> | null>>(`${endpoints.payments}/${id}/unapplied-balance`, { signal });
    return response.data.data;
}
