export interface SummaryDocumentMetrics {
    document_count: number;
    subtotal: string;
    discount_total?: string;
    tax_total?: string;
    charge_total?: string;
    adjustment_total?: string;
    grand_total: string;
    paid_total?: string;
}

export interface SummaryPaymentMethod {
    type: string;
    name: string;
    transaction_count: number;
    amount: string;
}

export interface SummaryPaymentMetrics {
    amount: string;
    transaction_count: number;
    methods: SummaryPaymentMethod[];
}

export interface SalesSettlementMetric {
    amount: string;
    document_count: number;
}

export interface SalesSettlementBreakdown {
    cash: SalesSettlementMetric;
    card: SalesSettlementMetric;
    credit: SalesSettlementMetric;
    other_paid: SalesSettlementMetric;
    credits_applied: string;
    source_note: string;
}

export interface SummaryCapability {
    available: boolean;
    source: string | null;
    message?: string;
}

export interface SummaryReportResult {
    period: {
        date_from: string;
        date_to: string;
    };
    currency_code: string;
    documents: {
        sales: SummaryDocumentMetrics;
        purchases: SummaryDocumentMetrics;
        sales_returns: SummaryDocumentMetrics;
        purchase_returns: SummaryDocumentMetrics;
    };
    sales_settlement: SalesSettlementBreakdown;
    payments: {
        received: SummaryPaymentMetrics;
        sent: SummaryPaymentMetrics;
    };
    performance: {
        total_income: string;
        cost_of_sales: string;
        other_expenses: string;
        total_expenses: string;
        net_profit: string;
    };
    capabilities: {
        sales_returns: SummaryCapability;
        purchase_returns: SummaryCapability;
        payroll: SummaryCapability;
    };
}
