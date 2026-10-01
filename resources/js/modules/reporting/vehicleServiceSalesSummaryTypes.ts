export interface VehicleServiceSalesSummaryParams {
    item_id?: number;
    date_from?: string;
    date_to?: string;
}

export interface VehicleServiceSalesSummaryRow {
    item: { id: number; code: string; name: string };
    quantity: string;
    job_count: number;
    sales_amount: string;
    collected_amount: string;
}

export interface VehicleServiceSalesSummaryResult {
    data: VehicleServiceSalesSummaryRow[];
    summary: {
        item_count: number;
        quantity: string;
        job_count: number;
        sales_amount: string;
        collected_amount: string;
    };
    date_basis: { sales: 'invoice_date'; collected: 'payment_date' };
}
