import type { ApiCollection } from '@/shared/types/api';
import type { ReportDefinition } from './genericReportTypes';

export interface GoodsReceiptSummaryParams {
    page?: number;
    per_page?: number;
    date_from?: string;
    date_to?: string;
    supplier_id?: number | null;
    warehouse_id?: number | null;
    search?: string;
}

export interface GoodsReceiptSummaryRow {
    id: number;
    grn_number: string;
    received_date: string;
    supplier: string;
    purchase_order: { id: number; number: string } | null;
    note: string;
    location_id: number | null;
    location: string;
    currency: string;
    accepted_quantity: string | null;
    uom: string | null;
    total_cost: string;
}

export interface GoodsReceiptSummaryTotal {
    currency: string;
    receipt_count: number;
    accepted_quantity: string | null;
    uom: string | null;
    total_cost: string;
}

export interface GoodsReceiptSummaryLocationTotal extends GoodsReceiptSummaryTotal {
    location_id: number | null;
    location: string;
}

export interface GoodsReceiptSummaryResult extends ApiCollection<GoodsReceiptSummaryRow> {
    report: ReportDefinition;
    summary: {
        total_receipts: number;
        locations: GoodsReceiptSummaryLocationTotal[];
        currencies: GoodsReceiptSummaryTotal[];
    };
}
