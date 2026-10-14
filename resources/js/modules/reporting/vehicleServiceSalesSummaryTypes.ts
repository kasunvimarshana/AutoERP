export interface VehicleServiceSalesSummaryParams {
    item_id?: number;
    date_from?: string;
    date_to?: string;
}

export interface VehicleServiceSalesSummaryItem {
    id: number | null;
    code: string;
    name: string;
}

export interface VehicleServiceSalesSummaryCostLine {
    line_id: number;
    item: VehicleServiceSalesSummaryItem;
    quantity: string;
    sales_amount: string;
    cost: string;
}

export interface VehicleServiceSalesSummaryComboComponent {
    item: VehicleServiceSalesSummaryItem;
    kind: 'stock' | 'labour_or_service';
    quantity: string;
    cost: string;
}

export interface VehicleServiceSalesSummaryRankedItem {
    item: VehicleServiceSalesSummaryItem;
    quantity: string;
    job_count: number;
    sales_amount: string;
    cost: string;
    stock_cost?: string;
    profit: string;
    margin: string;
    components?: VehicleServiceSalesSummaryComboComponent[];
}

export interface VehicleServiceSalesSummaryCombo {
    line_id: number;
    item: VehicleServiceSalesSummaryItem;
    quantity: string;
    sales_amount: string;
    component_cost: string;
    stock_cost: string;
    stock_quantity: string;
    profit: string;
    components: VehicleServiceSalesSummaryComboComponent[];
}

export interface VehicleServiceSalesSummaryJob {
    id: number;
    job_number: string;
    date: string;
    customer: { name: string };
    vehicle: { name: string };
    revenue: string;
    collected: string;
    direct_cost: string;
    stock_cost: string;
    used_stock_cost: string;
    commission: string;
    profit: string;
    margin: string;
    stock_items: VehicleServiceSalesSummaryCostLine[];
    combos: VehicleServiceSalesSummaryCombo[];
    other_items: VehicleServiceSalesSummaryCostLine[];
}

export interface VehicleServiceSalesSummaryResult {
    jobs: VehicleServiceSalesSummaryJob[];
    summary: {
        job_count: number;
        revenue: string;
        collected: string;
        direct_cost: string;
        stock_cost: string;
        commission: string;
        profit: string;
        margin: string;
        stock: {
            item_count: number;
            quantity: string;
            revenue: string;
            cost: string;
            profit: string;
            items: VehicleServiceSalesSummaryRankedItem[];
        };
        combo: {
            combo_count: number;
            item_count: number;
            quantity: string;
            component_stock_quantity: string;
            revenue: string;
            stock_cost: string;
            component_cost: string;
            profit: string;
            items: VehicleServiceSalesSummaryRankedItem[];
        };
        other_service_revenue: string;
    };
    date_basis: { sales: 'invoice_date'; collected: 'payment_date' };
}
