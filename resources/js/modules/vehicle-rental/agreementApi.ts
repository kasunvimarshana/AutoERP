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


// -------------------------------------------------------------------------
// Two-Stage Invoicing & Billing Cycles
// -------------------------------------------------------------------------

export interface BillingCycleChart {
    id: number;
    billing_cycle_id: number;
    running_chart_id: number;
    vehicle_id: number;
    driver_name_snapshot?: string | null;
    starts_at: string;
    ends_at: string;
    commercial_km: string;
    normal_ot_minutes: number;
    double_ot_minutes: number;
    night_outs: number;
    tolls_amount: string;
    is_replacement: boolean;
    running_chart?: { id: number; reference: string; status: string };
    vehicle?: { id: number; vehicle_number: string; registration_number: string };
}

export interface BillingCycleItem {
    id: number;
    billing_cycle_id: number;
    item_type: 'addition' | 'deduction';
    category: string;
    description: string;
    quantity: string;
    unit_rate: string;
    amount: string;
}

export interface BillingCycle {
    id: number;
    cycle_number: string;
    customer_agreement_id?: number | null;
    owner_agreement_id?: number | null;
    period_from: string;
    period_until: string;
    status: string;
    billing_mode: string;
    finalization_mode: string;
    base_rent_amount: string;
    excess_km_amount: string;
    overtime_amount: string;
    night_out_amount: string;
    tolls_amount: string;
    additions_amount: string;
    deductions_amount: string;
    net_subtotal: string;
    tax_amount: string;
    total_billed_amount: string;
    total_paid_amount: string;
    row_version: number;
    notes?: string | null;
    initial_invoice?: { id: number; invoice_number: string; grand_total: string; status: string } | null;
    final_invoice?: { id: number; invoice_number: string; grand_total: string; status: string } | null;
    supplementary_invoice?: { id: number; invoice_number: string; grand_total: string; status: string } | null;
    charts?: BillingCycleChart[];
    items?: BillingCycleItem[];
    customer_agreement?: { id: number; reference: string; party_name_snapshot: string };
    owner_agreement?: { id: number; reference: string; party_name_snapshot: string };
}

export interface BillingCyclePreview {
    cycle_id: number;
    cycle_number: string;
    status: string;
    period_from: string;
    period_until: string;
    billing_mode: string;
    finalization_mode: string;
    base_rent_amount: string;
    excess_km_amount: string;
    overtime_amount: string;
    night_out_amount: string;
    tolls_amount: string;
    additions_amount: string;
    deductions_amount: string;
    net_subtotal: string;
    tax_amount: string;
    total_billed_amount: string;
    initial_invoice?: { id: number; invoice_number: string; grand_total: string; paid_total: string; balance_due: string } | null;
    gross_delta_amount: string;
    charts_count: number;
    items_count: number;
}

export interface ReassignmentRecord {
    id: number;
    reassignment_type: string;
    customer_agreement_id?: number | null;
    vehicle_use_id?: number | null;
    running_chart_id?: number | null;
    previous_vehicle_id?: number | null;
    new_vehicle_id?: number | null;
    previous_vehicle?: { id: number; vehicle_number: string; registration_number: string } | null;
    new_vehicle?: { id: number; vehicle_number: string; registration_number: string } | null;
    previous_driver_employee_id?: number | null;
    new_driver_employee_id?: number | null;
    previous_driver?: { id: number; display_name: string; employee_number: string } | null;
    new_driver?: { id: number; display_name: string; employee_number: string } | null;
    financial_status: string;
    financial_delta_amount: string;
    reason: string;
    occurred_at: string;
    user?: { id: number; name: string } | null;
}

export const listBillingCycles = (agreementId?: number, kind?: string, page = 1) =>
    apiClient.get<ApiCollection<BillingCycle>>(`${AGREEMENT_API}/billing-cycles`, {
        params: { agreement_id: agreementId, kind, page, per_page: PAGE_SIZE },
    }).then(r => r.data);

export const createBillingCycle = (kind: AgreementKind, agreementId: number, payload: {
    period_from: string;
    period_until: string;
    billing_mode?: string;
    finalization_mode?: string;
    issue_initial_invoice?: boolean;
    invoice_date?: string;
    due_date?: string | null;
    notes?: string;
}) =>
    apiClient.post<ApiResource<BillingCycle>>(`${AGREEMENT_API}/${kind}/agreements/${agreementId}/billing-cycles`, payload).then(r => r.data.data);

export const getBillingCycle = (cycleId: number) =>
    apiClient.get<ApiResource<BillingCycle>>(`${AGREEMENT_API}/billing-cycles/${cycleId}`).then(r => r.data.data);

export const attachCycleCharts = (cycleId: number, chartIds: number[], expectedVersion: number) =>
    apiClient.post<ApiResource<BillingCycle>>(`${AGREEMENT_API}/billing-cycles/${cycleId}/charts`, {
        chart_ids: chartIds,
        expected_version: expectedVersion,
    }).then(r => r.data.data);

export const addCycleItem = (cycleId: number, payload: {
    item_type: 'addition' | 'deduction';
    category: string;
    description: string;
    quantity: string;
    unit_rate: string;
}, expectedVersion: number) =>
    apiClient.post<ApiResource<BillingCycleItem>>(`${AGREEMENT_API}/billing-cycles/${cycleId}/items`, {
        ...payload,
        expected_version: expectedVersion,
    }).then(r => r.data.data);

export const removeCycleItem = (cycleId: number, itemId: number, expectedVersion: number) =>
    apiClient.delete(`${AGREEMENT_API}/billing-cycles/${cycleId}/items/${itemId}`, {
        params: { expected_version: expectedVersion },
    });

export const previewCycleFinal = (cycleId: number) =>
    apiClient.get<ApiResource<BillingCyclePreview>>(`${AGREEMENT_API}/billing-cycles/${cycleId}/preview`).then(r => r.data.data);

export const finalizeBillingCycle = (cycleId: number, payload: {
    finalization_mode?: string;
    invoice_date?: string;
    due_date?: string | null;
}, expectedVersion: number) =>
    apiClient.post<ApiResource<BillingCycle>>(`${AGREEMENT_API}/billing-cycles/${cycleId}/finalize`, {
        ...payload,
        expected_version: expectedVersion,
    }).then(r => r.data.data);

export const reassignRunningChartDriver = (chartId: number, driverEmployeeId: number, reason: string, expectedVersion: number) =>
    apiClient.post<ApiResource<ReassignmentRecord>>(`${AGREEMENT_API}/running-charts/${chartId}/reassign-driver`, {
        driver_employee_id: driverEmployeeId,
        reason,
        expected_version: expectedVersion,
    }).then(r => r.data.data);

export const replaceVehicleUse = (useId: number, payload: {
    new_vehicle_id: number;
    new_owner_agreement_id?: number | null;
    cutover_at: string;
    return_odometer: string;
    new_handover_odometer: string;
    reason: string;
}, expectedVersion: number) =>
    apiClient.post<ApiResource<ReassignmentRecord>>(`${AGREEMENT_API}/vehicle-uses/${useId}/replace-vehicle`, {
        ...payload,
        expected_version: expectedVersion,
    }).then(r => r.data.data);

export const listReassignments = (customerAgreementId?: number) =>
    apiClient.get<{ data: ReassignmentRecord[] }>(`${AGREEMENT_API}/reassignments`, {
        params: { customer_agreement_id: customerAgreementId },
    }).then(r => r.data.data);

export const generateOwnerSettlementBill = (payload: {
    owner_agreement_id: number;
    start_date: string;
    end_date: string;
    wht_rate?: number | string;
    invoice_date?: string;
    due_date?: string | null;
}) =>
    apiClient.post<ApiResource<{ id: number; invoice_number: string }>>(`${AGREEMENT_API}/owner-settlement-bill`, payload).then(r => r.data.data);
