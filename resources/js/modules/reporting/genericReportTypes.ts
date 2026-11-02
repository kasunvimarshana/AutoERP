import type { ApiCollection } from '@/shared/types/api';

export type ReportFormat = 'html' | 'csv' | 'xlsx' | 'pdf' | 'print';

export interface ReportColumn {
    key: string;
    label: string;
    sortable: boolean;
    format: string;
}

export interface ReportFilter {
    key: string;
    label: string;
    type: 'text' | 'select' | 'date' | 'number' | 'boolean';
    options?: Array<{ value: string; label: string }>;
}

export interface ReportDefinition {
    key: string;
    title: string;
    group: string;
    description?: string;
    columns: ReportColumn[];
    filters: ReportFilter[];
    supports_date_range: boolean;
    default_sort: string;
    default_direction: 'asc' | 'desc';
}

export interface ReportParams {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    date_from?: string;
    date_to?: string;
    filters?: Record<string, string | number | boolean | null | undefined>;
}

export type ReportRow = Record<string, string | number | boolean | null>;
export type ReportResult = ApiCollection<ReportRow> & { report: ReportDefinition };
