import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface VoucherListRow {
    voucher_type: string;
    voucher_label?: string | null;
    voucher_number?: string | null;
    voucher_date?: string | null;
    source_module?: string | null;
    source_kind?: string | null;
    source_type?: string | null;
    source_id: number;
    source_document_number?: string | null;
    party_or_narration?: string | null;
    party_type?: string | null;
    currency_code?: string | null;
    transaction_amount?: string | null;
    allocated_amount?: string | null;
    unallocated_amount?: string | null;
    document_status?: string | null;
    approval_status?: string | null;
    allocation_status?: string | null;
    posting_status?: string | null;
    instrument_status?: string | null;
    source_document_url?: string | null;
    voucher_url?: string | null;
    available_actions?: string[];
    print_available?: boolean;
}

export interface VoucherLine {
    method?: string | null;
    method_type?: string | null;
    reference_number?: string | null;
    amount?: string | null;
    cleared_amount?: string | null;
    status?: string | null;
    internal_bank_account?: string | null;
    instrument_number?: string | null;
    external_bank?: string | null;
    external_branch?: string | null;
}

export interface VoucherAllocation {
    invoice_number?: string | null;
    invoice_date?: string | null;
    allocated_amount?: string | null;
    invoice_balance_after?: string | null;
    allocation_date?: string | null;
    status?: string | null;
}

export interface VoucherJournalLine {
    line_number?: number;
    account_code?: string | null;
    account_name?: string | null;
    description?: string | null;
    debit?: string | null;
    credit?: string | null;
}

export interface VoucherDetail extends VoucherListRow {
    payer_or_payee?: string | null;
    party_name?: string | null;
    currency?: string | null;
    exchange_rate?: string | null;
    base_currency_amount?: string | null;
    narration?: string | null;
    prepared_by?: number | null;
    approved_by?: number | null;
    posted_by?: number | null;
    created_at?: string | null;
    updated_at?: string | null;
    payment_lines?: VoucherLine[];
    invoice_or_payable_references?: VoucherAllocation[];
    journal_lines?: VoucherJournalLine[];
    reversal_information?: Record<string, string | number | null> | null;
    print_url?: string | null;
}

export interface VoucherTypeMeta {
    type: string;
    label: string;
    source_module: string;
    source_kind: string;
}

export async function listVouchers(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<VoucherListRow>>(endpoints.vouchers, { params, signal });
    return response.data;
}

export async function getVoucher(type: string, sourceId: number, sourceKind?: string | null, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<VoucherDetail>>(`${endpoints.vouchers}/${type}/${sourceId}`, {
        params: sourceKind ? { source_kind: sourceKind } : undefined,
        signal,
    });
    return response.data.data;
}

export async function listVoucherTypes(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<VoucherTypeMeta[]>>(`${endpoints.vouchers}/types`, { signal });
    return response.data.data;
}
