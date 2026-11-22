/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { PaginationMeta } from '@/shared/types/pagination';

export type CashierReportDirection = 'all' | 'inbound' | 'outbound';
export type CashierReportStatus = 'all' | 'approved' | 'draft' | 'voided' | 'reversed' | 'submitted';

export interface CashierReportParams {
    date_from: string;
    date_to: string;
    cashier_user_id?: number | null;
    payment_method?: string;
    direction?: CashierReportDirection;
    document_status?: CashierReportStatus;
    search?: string;
    page?: number;
    per_page?: number;
}

export interface CashierReportSummary {
    total_inflow: string;
    total_outflow: string;
    net_collections: string;
    cash_inflow: string;
    cash_outflow: string;
    net_cash_in_drawer: string;
    non_cash_total: string;
    total_transactions: number;
    inbound_count: number;
    outbound_count: number;
}

export interface CashierPaymentMethodRow {
    key: string;
    name: string;
    type: string;
    inflow: string;
    inflow_count: number;
    outflow: string;
    outflow_count: number;
    net: string;
    total_count: number;
}

export interface CashierBreakdownRow {
    cashier_id: number | null;
    cashier_name: string;
    cashier_email: string;
    inflow: string;
    outflow: string;
    net: string;
    cash_net: string;
    non_cash_net: string;
    transaction_count: number;
}

export interface CashierTransactionLine {
    method_code: string;
    method_name: string;
    method_type: string;
    amount: string;
    reference_number: string;
    instrument_number: string;
}

export interface CashierTransactionAllocation {
    invoice_number: string;
    allocated_amount: string;
}

export interface CashierTransactionRow {
    id: number;
    payment_number: string;
    payment_date: string;
    created_at: string | null;
    direction: 'inbound' | 'outbound';
    direction_label: string;
    payment_type: string;
    document_status: string;
    posting_status: string;
    currency_code: string;
    total_amount: string;
    party_name: string;
    reference_number: string;
    cashier_id: number | null;
    cashier_name: string;
    organization_unit_name: string;
    lines: CashierTransactionLine[];
    allocations: CashierTransactionAllocation[];
}

export interface CashierReportResult {
    period: {
        date_from: string;
        date_to: string;
    };
    organization_unit: string;
    currency: string;
    summary: CashierReportSummary;
    payment_methods: CashierPaymentMethodRow[];
    cashiers: CashierBreakdownRow[];
    transactions: {
        data: CashierTransactionRow[];
        meta: PaginationMeta;
    };
}

export interface CashierLookupUser {
    id: number;
    name: string;
    email: string;
}
