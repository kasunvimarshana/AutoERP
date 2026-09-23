import type { ApiCollection } from '@/shared/types/api';
import type { ReportDefinition, ReportRow } from './genericReportTypes';

export type GrnInvoiceProgress = 'not_invoiced' | 'partially_invoiced' | 'invoiced';
export type GrnExposureStatus = 'open' | 'settled' | 'credit';

export interface GrnPayablesReportParams {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    date_from?: string;
    date_to?: string;
    supplier_id?: number | null;
    invoice_progress?: GrnInvoiceProgress;
    exposure_status?: GrnExposureStatus;
}

export interface GrnPayablesSummary {
    grn_count: number;
    not_invoiced_count: number;
    partially_invoiced_count: number;
    invoiced_count: number;
    open_exposure_count: number;
    open_return_credit_count: number;
    not_invoiced_amount: string;
    partially_invoiced_amount: string;
    invoiced_ap_outstanding: string;
    receipt_total: string;
    linked_invoice_amount: string;
    finalized_invoice_amount: string;
    pending_invoice_amount: string;
    uninvoiced_amount: string;
    settled_invoice_amount: string;
    ap_outstanding: string;
    return_amount: string;
    open_return_credit: string;
    pending_return_credit: string;
    projected_exposure: string;
    grni_balance: string;
    accounting_liability: string;
}

export interface GrnPayablesSupplierRow {
    supplier: string;
    grn_count: number;
    uninvoiced_amount: string;
    ap_outstanding: string;
    open_return_credit: string;
    projected_exposure: string;
    grni_balance: string;
}

export interface GrnPayablesReportResult extends ApiCollection<ReportRow> {
    report: ReportDefinition;
    summary: GrnPayablesSummary;
    suppliers: GrnPayablesSupplierRow[];
    currency_code: string;
    period: {
        date_from: string | null;
        date_to: string | null;
    };
    basis: {
        projected_exposure: string;
        accounting_liability: string;
        invoice_allocation: string;
        scope: string;
    };
}
