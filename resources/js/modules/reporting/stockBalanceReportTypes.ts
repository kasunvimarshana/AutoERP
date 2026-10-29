import type { ReportDefinition, ReportResult } from './genericReportTypes';

type Quantity = string | number;

export interface StockBalanceReportParams {
    page: number;
    per_page: number;
    search?: string;
    warehouse_id?: number;
    location_id?: number;
    include_zero: boolean;
}

export interface StockBalanceReportRow {
    item_id: number;
    item_variant_id: number | null;
    base_uom_id: number | null;
    warehouse_id: number;
    location_id: number | null;
    item_name: string;
    item_code: string;
    variant_name: string | null;
    variant_code: string | null;
    warehouse: string;
    location: string | null;
    uom: string | null;
    quantity_on_hand: Quantity;
    quantity_available: Quantity;
    quantity_damaged: Quantity;
    quantity_expired: Quantity;
    stock_level: 'in_stock' | 'low_stock' | 'out_of_stock';
}

export interface StockBalanceDetail {
    batch_number: string | null;
    quantity_on_hand: Quantity;
    quantity_available: Quantity;
    quantity_reserved: Quantity;
    quantity_allocated: Quantity;
    quantity_returned: Quantity;
    quantity_in_transit: Quantity;
    quantity_damaged: Quantity;
    quantity_quarantine: Quantity;
    quantity_expired: Quantity;
    quantity_scrapped: Quantity;
}

export interface StockBalanceReportResult {
    data: StockBalanceReportRow[];
    meta: ReportResult['meta'];
    report: ReportDefinition;
    generated_at: string;
}
