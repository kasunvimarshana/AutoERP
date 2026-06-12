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
    refunded_amount?: string;
    cheque_number?: string | null;
    cheque_date?: string | null;
    payee_name?: string | null;
    amount_in_words?: string | null;
    source_type?: string | null;
    source_id?: number | null;
    allocation_status?: string;
    metadata?: Record<string, unknown> | null;
    lines?: Array<{
        id?: number;
        amount?: string;
        cleared_amount?: string;
        reference_number?: string | null;
        status?: string;
        metadata?: Record<string, unknown> | null;
        payment_method?: {
            id: number;
            name?: string;
            method_type?: string;
        } | null;
    }>;
    refunds?: Array<Record<string, unknown>>;
    reversals?: Array<Record<string, unknown>>;
    status_history?: Array<Record<string, unknown>>;
}

export interface PaymentMethod {
    id: number;
    code?: string;
    name: string;
    method_type: string;
    direction_allowed?: string;
    requires_reference?: boolean;
    requires_bank_account?: boolean;
    metadata?: Record<string, unknown> | null;
}

export interface PaymentLinePayload {
    payment_method_id?: number;
    reference_number?: string;
    amount: string;
    cleared_amount?: string;
    status?: string;
    notes?: string;
    metadata?: Record<string, unknown>;
}

export interface PaymentPayload {
    payment_type: string;
    direction: string;
    payment_date: string;
    payment_number?: string;
    party_type?: string;
    party_id?: number;
    source_type?: string;
    source_id?: number;
    allocation_status?: string;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    cheque_number?: string;
    cheque_date?: string;
    bank_account_id?: number;
    payee_name?: string;
    notes?: string;
    lines: PaymentLinePayload[];
    metadata?: Record<string, unknown>;
}

export async function listPayments(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Payment>>(endpoints.payments, { params, signal });
    return response.data;
}

export async function createPayment(payload: PaymentPayload) {
    const response = await apiClient.post<ApiResource<Payment>>(endpoints.payments, payload);
    return response.data.data;
}

export async function getPayment(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Payment>>(`${endpoints.payments}/${id}`, { signal });
    return response.data.data;
}

export async function listPaymentMethods(params: ListParams & { direction?: string; method_type?: string; is_active?: boolean }, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PaymentMethod>>(`${endpoints.payments}/methods`, { params, signal });
    return response.data;
}

export async function getPaymentAllocations(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>[]>>(`${endpoints.payments}/${id}/allocations`, { signal });
    return response.data.data;
}

export async function getPaymentUnappliedBalance(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown> | null>>(`${endpoints.payments}/${id}/unapplied-balance`, { signal });
    return response.data.data;
}

export async function allocatePayment(id: number, allocations: Array<Record<string, unknown>>) {
    const response = await apiClient.post<ApiResource<Payment>>(`${endpoints.payments}/${id}/allocations`, { allocations });
    return response.data.data;
}

export async function refundPayment(id: number, payload: Record<string, unknown>) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.payments}/${id}/refunds`, payload);
    return response.data.data;
}

export async function reversePayment(id: number, payload: Record<string, unknown>) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.payments}/${id}/reverse`, payload);
    return response.data.data;
}

export async function settlePaymentLine(paymentId: number, lineId: number, status: string, metadata?: Record<string, unknown>) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.payments}/${paymentId}/lines/${lineId}/settlement`, { status, metadata });
    return response.data.data;
}
