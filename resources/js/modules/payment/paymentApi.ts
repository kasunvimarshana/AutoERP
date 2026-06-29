import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface PaymentPartySummary {
    id: number | null;
    type?: string | null;
    number?: string | null;
    code?: string | null;
    name?: string | null;
    email?: string | null;
    phone?: string | null;
}

export interface PaymentCurrencySummary {
    id: number | null;
    code?: string | null;
    name?: string | null;
    symbol?: string | null;
}

export interface PaymentMethodSnapshot {
    id: number | null;
    code?: string | null;
    name?: string | null;
    method_type?: string | null;
    requires_reference?: boolean;
    requires_instrument_details?: boolean;
}

export interface PaymentLine {
    id: number;
    row_version: number;
    line_number: number;
    payment_method_id?: number | null;
    payment_method?: PaymentMethodSnapshot | null;
    reference_number?: string | null;
    amount: string;
    cleared_amount: string;
    status: string;
    instrument_direction?: string | null;
    external_bank_name?: string | null;
    external_bank_branch?: string | null;
    instrument_number?: string | null;
    instrument_date?: string | null;
    deposit_date?: string | null;
    realized_date?: string | null;
    clearing_date?: string | null;
    bounced_date?: string | null;
    returned_date?: string | null;
    notes?: string | null;
}

export interface Payment extends Record<string, unknown> {
    id: number;
    row_version: number;
    payment_number?: string | null;
    payment_date?: string | null;
    payment_type?: string | null;
    direction?: string | null;
    document_status?: string | null;
    allocation_status?: string | null;
    posting_status?: string | null;
    instrument_status?: string | null;
    party?: PaymentPartySummary | null;
    currency?: PaymentCurrencySummary | null;
    total_amount?: string | null;
    allocated_amount?: string | null;
    unapplied_amount?: string | null;
    refunded_amount?: string | null;
    reference_number?: string | null;
    cheque_number?: string | null;
    cheque_date?: string | null;
    payee_name?: string | null;
    amount_in_words?: string | null;
    source_type?: string | null;
    source_id?: number | null;
    finance_posting_reference?: string | null;
    posting_correlation_key?: string | null;
    capabilities?: Record<string, boolean>;
    blockers?: Record<string, string[]>;
    lines?: PaymentLine[];
    allocations?: Array<Record<string, unknown>>;
    refunds?: Array<Record<string, unknown>>;
    reversals?: Array<Record<string, unknown>>;
    lifecycle_events?: Array<Record<string, unknown>>;
}

export interface PaymentMethod {
    id: number;
    row_version?: number;
    code?: string;
    name: string;
    method_type: string;
    direction_allowed?: string;
    requires_reference?: boolean;
    requires_instrument_details?: boolean;
    is_active?: boolean;
    sort_order?: number;
    metadata?: Record<string, unknown> | null;
}

export interface PaymentLinePayload {
    payment_method_id: number;
    reference_number?: string;
    amount: string;
    instrument_direction?: 'received' | 'issued';
    external_bank_name?: string;
    external_bank_branch?: string;
    instrument_number?: string;
    instrument_date?: string;
    notes?: string;
}

export interface PaymentAllocationPayload {
    invoice_id: number;
    allocated_amount: string;
    allocation_date?: string;
    allocation_method?: string;
}

export interface PaymentPayload {
    payment_type: string;
    direction: string;
    payment_date: string;
    party_type?: string;
    party_id?: number;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    cheque_number?: string;
    cheque_date?: string;
    payee_name?: string;
    notes?: string;
    lines: PaymentLinePayload[];
    allocations?: PaymentAllocationPayload[];
}

export interface PaymentRefundPayload {
    expected_version: number;
    refund_date: string;
    amount: string;
    reason: string;
    payment_method_id?: number;
    reference_number?: string;
    external_bank_name?: string;
    external_bank_branch?: string;
    instrument_number?: string;
    instrument_date?: string;
}

export interface PaymentReversalPayload {
    expected_version: number;
    reversal_date: string;
    reason: string;
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

export async function listPaymentMethods(params: ListParams & { direction?: string; method_type?: string; is_active?: boolean; include_overrides?: boolean }, signal?: AbortSignal) {
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

export async function allocatePayment(id: number, expectedVersion: number, allocations: PaymentAllocationPayload[]) {
    const response = await apiClient.post<ApiResource<Payment>>(`${endpoints.payments}/${id}/allocations`, {
        expected_version: expectedVersion,
        allocations,
    });
    return response.data.data;
}

export async function refundPayment(id: number, payload: PaymentRefundPayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.payments}/${id}/refunds`, payload);
    return response.data.data;
}

export async function reversePayment(id: number, payload: PaymentReversalPayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.payments}/${id}/reverse`, payload);
    return response.data.data;
}

export async function settlePaymentLine(
    paymentId: number,
    lineId: number,
    payload: {
        expected_payment_version: number;
        expected_line_version: number;
        status: string;
        reason?: string;
    },
) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.payments}/${paymentId}/lines/${lineId}/settlement`, payload);
    return response.data.data;
}

export async function getPaymentMethod(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PaymentMethod>>(`${endpoints.payments}/methods/${id}`, { signal });
    return response.data.data;
}

export async function createPaymentMethod(payload: Partial<PaymentMethod>) {
    const response = await apiClient.post<ApiResource<PaymentMethod>>(`${endpoints.payments}/methods`, payload);
    return response.data.data;
}

export async function updatePaymentMethod(id: number, payload: Partial<PaymentMethod>) {
    const response = await apiClient.put<ApiResource<PaymentMethod>>(`${endpoints.payments}/methods/${id}`, payload);
    return response.data.data;
}

export async function activatePaymentMethod(id: number) {
    const response = await apiClient.post<ApiResource<PaymentMethod>>(`${endpoints.payments}/methods/${id}/activate`);
    return response.data.data;
}

export async function deactivatePaymentMethod(id: number) {
    const response = await apiClient.post<ApiResource<PaymentMethod>>(`${endpoints.payments}/methods/${id}/deactivate`);
    return response.data.data;
}

export async function deletePaymentMethod(id: number) {
    await apiClient.delete(`${endpoints.payments}/methods/${id}`);
}

async function paymentAction(id: number, path: string, expectedVersion: number, reason?: string) {
    const response = await apiClient.post<ApiResource<Payment>>(`${endpoints.payments}/${id}/${path}`, {
        expected_version: expectedVersion,
        reason,
    });
    return response.data.data;
}

export const submitPayment = (id: number, expectedVersion: number) => paymentAction(id, 'submit-approval', expectedVersion);
export const approvePayment = (id: number, expectedVersion: number) => paymentAction(id, 'approve', expectedVersion);
export const postPayment = (id: number, expectedVersion: number) => paymentAction(id, 'post', expectedVersion);
export const voidPayment = (id: number, expectedVersion: number, reason?: string) => paymentAction(id, 'void', expectedVersion, reason);
