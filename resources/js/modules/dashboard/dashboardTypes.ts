export interface DashboardDateRange {
    date_from: string;
    date_to: string;
}

export interface DashboardMetricBucket {
    key: string;
    label: string;
    count: number;
    amount: string;
}

export interface DashboardSummary {
    period: DashboardDateRange;
    currency_code: string;
    kpis: {
        today_revenue: string;
        period_revenue: string;
        receivables: string;
        payables: string;
        active_service_jobs: number;
        inventory_value: string;
    };
    revenue_trend: Array<{ key: string; label: string; value: string }>;
    service_jobs: Array<{ status: string; label: string; count: number; url: string }>;
    cash_flow: Array<{ key: string; label: string; inbound: string; outbound: string; net: string }>;
    inventory_health: {
        inventory_value: string;
        in_stock_items: number;
        low_stock_items: number;
        out_of_stock_items: number;
        expiring_batches: number;
    };
    aging: {
        receivables: DashboardMetricBucket[];
        payables: DashboardMetricBucket[];
    };
    profitability: {
        total_income: string;
        cost_of_sales: string;
        gross_profit: string;
        other_expenses: string;
        total_expenses: string;
        net_profit: string;
    };
    employee_performance: Array<{
        employee: {
            id: number;
            code: string;
            name: string;
        };
        completed_jobs: number;
        total_jobs: number;
        total_hours: string;
        labour_value: string;
        earned_commission: string;
        pending_commission: string;
        cancelled_commission: string;
        total_commission: string;
    }>;
    actions: Array<{
        key: string;
        label: string;
        count: number;
        url: string;
        tone: 'critical' | 'warning' | 'info';
    }>;
}
