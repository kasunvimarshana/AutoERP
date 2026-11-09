import { apiClient } from '@/shared/api/apiClient';
import { requestLookup, mapLookupResult } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { AGREEMENT_API, PAGE_SIZE, AgreementKind, AgreementAction, AgreementStatus, type Agreement, type AgreementHistory, type AgreementPayload, type AgreementSuccessorPayload } from './agreements';

const url = (kind: AgreementKind) => `${AGREEMENT_API}/${kind}/agreements`;
export const listAgreements = (kind: AgreementKind, page: number, signal?: AbortSignal, search?: string, status?: AgreementStatus | '') =>
    apiClient.get<ApiCollection<Agreement>>(url(kind), {
        params: {
            page,
            per_page: PAGE_SIZE,
            search: search || undefined,
            status: status || undefined,
        },
        signal,
    }).then(r => r.data);
export const getAgreement = (kind: AgreementKind, id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<Agreement>>(`${url(kind)}/${id}`, { signal }).then(r => r.data.data);
export const searchCustomerAgreements = async (params: LookupLoadParams): Promise<LookupResult<NamedResource>> => {
    const result = await requestLookup<Agreement>(url(AgreementKind.Customer), params);
    return mapLookupResult(result, ag => ({
        id: ag.id,
        name: `${ag.reference} — ${ag.party.name}`,
    }));
};
export const searchOwnerAgreements = async (params: LookupLoadParams): Promise<LookupResult<NamedResource>> => {
    const result = await requestLookup<Agreement>(url(AgreementKind.Owner), params);
    return mapLookupResult(result, ag => ({
        id: ag.id,
        name: `${ag.reference} — ${ag.party.name}`,
    }));
};
export const saveAgreement = (kind: AgreementKind, payload: AgreementPayload, id?: number) =>
    (id ? apiClient.put<ApiResource<Agreement>>(`${url(kind)}/${id}`, payload) : apiClient.post<ApiResource<Agreement>>(url(kind), payload)).then(r => r.data.data);
export const createAgreementSuccessor = (kind: AgreementKind, record: Agreement, payload: AgreementSuccessorPayload) =>
    apiClient.post<ApiResource<Agreement>>(`${url(kind)}/${record.id}/successor`, { ...payload, expected_version: record.row_version }).then(r => r.data.data);
export const transitionAgreement = (kind: AgreementKind, record: Agreement, action: AgreementAction, reason?: string) =>
    apiClient.post<ApiResource<Agreement>>(`${url(kind)}/${record.id}/${action}`, { expected_version: record.row_version, reason }).then(r => r.data.data);
export const agreementHistory = (kind: AgreementKind, id: number, page: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<AgreementHistory>>(`${url(kind)}/${id}/history`, { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);

export interface AgreementFinancialSummary {
    agreement: {
        id: number;
        reference: string;
        kind: string;
        status: string;
        currency: { id: number; code: string };
        party: { id: number; name: string; code: string };
    };
    summary: {
        total_base_charges: string;
        total_usage_charges: string;
        total_allocations: string;
        total_invoiced: string;
        total_credit_notes: string;
        total_debit_notes: string;
        total_paid: string;
        total_credit_allocated: string;
        total_remaining_balance: string;
        total_unallocated_credit: string;
    };
    base_charges: Array<{
        id: number;
        from: string;
        until: string;
        amount: string;
        voided_at?: string | null;
        invoices: Array<{ id: number; invoice_number: string; status: string }>;
    }>;
    usage_charges: Array<{
        id: number;
        component: string;
        from: string;
        until: string;
        amount: string;
        voided_at?: string | null;
        invoices: Array<{ id: number; invoice_number: string; status: string }>;
    }>;
    allocations: Array<{
        id: number;
        row_version: number;
        sequence: number;
        allocation_type: string;
        allocation_type_label: string;
        net_amount: string;
        total_amount: string;
        status: string;
        expense: {
            id: number;
            expense_number: string;
            expense_type: string;
            expense_date: string;
        };
        invoice?: {
            id: number;
            invoice_number: string;
            status: string;
        } | null;
    }>;
    documents: Array<{
        id: number;
        invoice_number: string;
        invoice_date: string;
        due_date?: string | null;
        invoice_type: string;
        document_kind: string;
        status: string;
        grand_total: string;
        paid_total: string;
        credit_total: string;
        balance_due: string;
        notes?: string | null;
    }>;
    deposit?: {
        requirement: string | null;
        net_receipts: string;
        remaining: string | null;
        receipts_count: number;
    } | null;
}

export interface FinancialAdjustmentPayload {
    expected_version: number;
    adjustment_type: 'credit_note' | 'debit_note';
    amount: string;
    reason: string;
    invoice_date: string;
    due_date?: string | null;
    exchange_rate: string;
}

export const getAgreementFinancialSummary = (kind: AgreementKind, id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<AgreementFinancialSummary>>(`${url(kind)}/${id}/financial-summary`, { signal }).then(r => r.data.data);

export const createAgreementFinancialAdjustment = (kind: AgreementKind, id: number, payload: FinancialAdjustmentPayload) =>
    apiClient.post<ApiResource<{ id: number; invoice_number: string }>>(`${url(kind)}/${id}/financial-adjustments`, payload).then(r => r.data.data);
