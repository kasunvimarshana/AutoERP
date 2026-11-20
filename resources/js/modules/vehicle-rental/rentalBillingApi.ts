import { apiClient } from '@/shared/api/apiClient';
import type { PaginationMeta } from '@/shared/types/pagination';

export const RENTAL_BILLING_API = '/api/v1/vehicle-rental/billing';

export interface RentalBillingDocument {
    id: number;
    invoice_number: string;
    invoice_type: string;
    direction: 'outbound' | 'inbound';
    status: string;
    balance_status: 'unpaid' | 'partially_paid' | 'paid' | 'overdue' | 'cancelled';
    invoice_date: string | null;
    due_date: string | null;
    currency: {
        id: number;
        code: string;
        symbol: string;
    };
    subtotal: string;
    tax_total: string;
    discount_total: string;
    grand_total: string;
    paid_total: string;
    credit_total: string;
    balance_due: string;
    wht_amount: string;
    gross_amount: string;
    party: {
        id: number;
        type: string;
        name: string;
        code: string;
        phone: string | null;
    };
    agreement: {
        id: number | null;
        reference: string;
        kind: 'customer' | 'owner';
        status: string | null;
        starts_on: string | null;
        ends_on: string | null;
    } | null;
    vehicle: {
        id: number | null;
        registration_number: string | null;
        make_model: string | null;
    } | null;
    driver_name: string | null;
    notes?: string | null;
    maintenance_deductions?: string;
    row_version: number;
    created_at: string;
}

export interface RentalBillingLine {
    id: number;
    line_number: number;
    description: string;
    quantity: string;
    unit_price: string;
    discount_amount: string;
    subtotal: string;
    tax_total: string;
    grand_total: string;
    line_type: string;
}

export interface RentalBillingAdjustment {
    id: number;
    name: string;
    adjustment_type: string;
    effect: string;
    amount: string;
    rate_snapshot: string | null;
}

export interface RentalBillingPayment {
    id: number;
    payment_id: number;
    payment_number: string;
    payment_date: string | null;
    method_name: string;
    reference: string;
    allocated_amount: string;
    realized_at: string | null;
    status: string;
    receipt_url: string | null;
}

export interface RentalTelematicsSummary {
    has_charts: boolean;
    trips_count: number;
    commercial_km: string;
    normal_ot_hours: string;
    double_ot_hours: string;
    triple_ot_hours: string;
    night_outs: number;
    vehicles: string[];
    drivers: string[];
}

export interface RentalFinancialBreakdown {
    base_rent_amount: string;
    excess_km_amount: string;
    overtime_amount: string;
    night_out_amount: string;
    tolls_amount: string;
    additions_amount: string;
    deductions_amount: string;
    net_subtotal: string;
    tax_amount: string;
    wht_withheld: string;
    maintenance_deductions: string;
    grand_total: string;
    paid_total: string;
    balance_due: string;
}

export interface RentalBillingDetail extends RentalBillingDocument {
    lines: RentalBillingLine[];
    adjustments: RentalBillingAdjustment[];
    payments: RentalBillingPayment[];
    telematics: RentalTelematicsSummary | null;
    financial_breakdown: RentalFinancialBreakdown;
    statement_url: string | null;
    print_url: string;
    pdf_url: string;
    notes: string | null;
    cancellation_reason: string | null;
    can_cancel: boolean;
    can_edit: boolean;
    can_record_payment: boolean;
    can_print: boolean;
}

export interface RentalBillingSummary {
    customer_ar: {
        total_invoiced: string;
        total_collected: string;
        total_outstanding: string;
        overdue_count: number;
        overdue_amount: string;
        draft_count?: number;
        draft_amount?: string;
    };
    owner_ap: {
        total_gross_billed: string;
        total_wht_withheld: string;
        total_net_paid: string;
        total_outstanding: string;
        overdue_count: number;
        overdue_amount: string;
        draft_count?: number;
        draft_amount?: string;
    };
}

export interface RentalBillingQueryParams {
    direction?: 'outbound' | 'inbound';
    search?: string;
    status?: string;
    balance_status?: string;
    date_from?: string;
    date_to?: string;
    agreement_id?: number;
    party_id?: number;
    page?: number;
    per_page?: number;
}

export interface PaginatedBillingDocuments {
    data: RentalBillingDocument[];
    meta: PaginationMeta;
}

export interface UpdateBillingDocumentPayload {
    expected_version: number;
    due_date?: string | null;
    notes?: string | null;
}

export interface CancelBillingDocumentPayload {
    expected_version: number;
    reason: string;
}

export async function listRentalBillingDocuments(
    params: RentalBillingQueryParams,
    signal?: AbortSignal
): Promise<PaginatedBillingDocuments> {
    const res = await apiClient.get<{
        data?: RentalBillingDocument[];
        meta?: PaginatedBillingDocuments['meta'];
        current_page?: number;
        last_page?: number;
        per_page?: number;
        total?: number;
        from?: number;
        to?: number;
    }>(`${RENTAL_BILLING_API}/documents`, { params, signal });
    const raw = res.data;
    if (raw.meta) {
        return { data: raw.data ?? [], meta: raw.meta };
    }
    return {
        data: raw.data ?? [],
        meta: {
            current_page: raw.current_page ?? 1,
            last_page: raw.last_page ?? 1,
            per_page: raw.per_page ?? 25,
            total: raw.total ?? (raw.data ? raw.data.length : 0),
            from: raw.from ?? 1,
            to: raw.to ?? (raw.data ? raw.data.length : 0),
        },
    };
}

export async function getRentalBillingSummary(signal?: AbortSignal): Promise<RentalBillingSummary> {
    const res = await apiClient.get<{ data: RentalBillingSummary }>(`${RENTAL_BILLING_API}/summary`, { signal });
    return res.data.data;
}

export async function getRentalBillingDetail(id: number, signal?: AbortSignal): Promise<RentalBillingDetail> {
    const res = await apiClient.get<{ data: RentalBillingDetail }>(`${RENTAL_BILLING_API}/documents/${id}`, { signal });
    return res.data.data;
}

export async function updateRentalBillingDocument(
    id: number,
    payload: UpdateBillingDocumentPayload
): Promise<RentalBillingDetail> {
    const res = await apiClient.put<{ data: RentalBillingDetail }>(`${RENTAL_BILLING_API}/documents/${id}`, payload);
    return res.data.data;
}

export async function cancelRentalBillingDocument(
    id: number,
    payload: CancelBillingDocumentPayload
): Promise<RentalBillingDetail> {
    const res = await apiClient.post<{ data: RentalBillingDetail }>(`${RENTAL_BILLING_API}/documents/${id}/cancel`, payload);
    return res.data.data;
}

export interface RentalQuotationLine {
    line_number: number;
    description: string;
    quantity: string;
    unit_price: string;
    discount_amount: string;
    line_total: string;
}

export interface RentalQuotationPreviewRequest {
    billing_cycle_id?: number;
    agreement_id?: number;
    customer_agreement_id?: number;
    period_from?: string;
    period_until?: string;
    custody_days?: number;
    excess_km?: string | number;
    normal_ot_hours?: string | number;
    double_ot_hours?: string | number;
    triple_ot_hours?: string | number;
    night_outs?: number;
    tolls_amount?: string | number;
    additions?: { description: string; amount: string; category?: string }[];
    deductions?: { description: string; amount: string; category?: string }[];
    tax_rate?: string | number;
}

export interface RentalQuotationPreview {
    quotation_number: string;
    quotation_date: string;
    direction: 'outbound' | 'inbound';
    customer: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
    } | null;
    agreement: {
        id: number;
        reference: string;
        billing_basis: string;
        starts_on?: string | null;
        ends_on?: string | null;
    };
    billing_cycle: {
        id: number;
        cycle_number: number;
        period_from?: string | null;
        period_until?: string | null;
        status: string;
        finalization_mode?: string;
    } | null;
    period: {
        from: string;
        until: string;
    };
    lines: RentalQuotationLine[];
    telematics: {
        trips_count: number;
        commercial_km: string;
        normal_ot_hours: string;
        double_ot_hours: string;
        triple_ot_hours: string;
        night_outs: number;
        assigned_vehicles: string[];
        assigned_drivers: string[];
    };
    vehicle_attributions?: unknown[];
    driver_attributions?: unknown[];
    line_items?: unknown[];
    financial_breakdown: {
        base_rent: string;
        excess_km: string;
        overtime: string;
        night_out: string;
        tolls: string;
        additions: string;
        deductions: string;
        gross_subtotal: string;
        discount_total: string;
        net_subtotal: string;
        tax_amount: string;
        grand_total: string;
        net_payable: string;
    };
}

export interface RentalOwnerBillPreviewRequest {
    owner_agreement_id?: number;
    agreement_id?: number;
    start_date: string;
    end_date: string;
    wht_rate?: string | number;
    include_maintenance_chargebacks?: boolean;
}

export interface RentalOwnerBillAdjustment {
    name: string;
    type: string;
    effect: string;
    amount: string;
}

export interface RentalOwnerBillPreview {
    bill_number: string;
    direction: 'inbound';
    document_date: string;
    supplier: {
        id: number;
        name: string;
    } | null;
    agreement: {
        id: number;
        reference: string;
        starts_on?: string | null;
        ends_on?: string | null;
        vehicle?: {
            id: number;
            registration_number: string;
            make?: string | null;
            model?: string | null;
        } | null;
    };
    period: {
        from: string;
        until: string;
    };
    wht_rate: string;
    lines: RentalQuotationLine[];
    adjustments: RentalOwnerBillAdjustment[];
    financial_breakdown: {
        gross_payable: string;
        base_payable: string;
        usage_payable: string;
        adjustment_additions: string;
        wht_amount: string;
        maintenance_deductions: string;
        adjustment_deductions: string;
        total_deductions: string;
        net_payable: string;
    };
    notes: string;
}

export async function previewRentalQuotation(
    payload: RentalQuotationPreviewRequest,
    signal?: AbortSignal
): Promise<RentalQuotationPreview> {
    const res = await apiClient.post<{ data: RentalQuotationPreview }>(
        `${RENTAL_BILLING_API}/quotation-preview`,
        payload,
        { signal }
    );
    return res.data.data;
}

export async function previewRentalOwnerBill(
    payload: RentalOwnerBillPreviewRequest,
    signal?: AbortSignal
): Promise<RentalOwnerBillPreview> {
    const res = await apiClient.post<{ data: RentalOwnerBillPreview }>(
        `${RENTAL_BILLING_API}/owner-bill-preview`,
        payload,
        { signal }
    );
    return res.data.data;
}
