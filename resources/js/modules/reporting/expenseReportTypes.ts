import type { ApiCollection } from '@/shared/types/api';
import type { ReportDefinition, ReportRow } from './genericReportTypes';

export type ExpenseEventType = 'posted' | 'reversal';

export interface ExpenseReportParams {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    date_from: string;
    date_to: string;
    expense_type_id?: number;
    payment_method_id?: number;
    event_type?: ExpenseEventType;
}

export interface ExpenseReportSummary {
    event_count: number;
    posted_count: number;
    reversal_count: number;
    posted_amount: string;
    reversed_amount: string;
    net_amount: string;
}

export interface ExpenseBreakdownRow {
    id: number;
    code: string;
    name: string;
    transaction_count: number;
    posted_amount: string;
    reversed_amount: string;
    net_amount: string;
}

export interface ExpenseTrendRow {
    date: string;
    posted_amount: string;
    reversed_amount: string;
    net_amount: string;
}

export interface ExpenseFilterOption {
    id: number;
    name: string;
}

export interface ExpenseReportOverview {
    summary: ExpenseReportSummary;
    by_expense_type: ExpenseBreakdownRow[];
    by_payment_method: ExpenseBreakdownRow[];
    trend: ExpenseTrendRow[];
}

export interface ExpenseReportResult extends ApiCollection<ReportRow>, ExpenseReportOverview {
    report: ReportDefinition;
    currency_code: string;
    period: {
        date_from: string;
        date_to: string;
    };
    filter_options: {
        expense_types: ExpenseFilterOption[];
        payment_methods: ExpenseFilterOption[];
    };
    basis: string;
}
