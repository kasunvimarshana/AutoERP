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
    };
    owner_ap: {
        total_gross_billed: string;
        total_wht_withheld: string;
        total_net_paid: string;
        total_outstanding: string;
        overdue_count: number;
        overdue_amount: string;
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
    const res = await apiClient.get<any>(`${RENTAL_BILLING_API}/documents`, { params, signal });
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
