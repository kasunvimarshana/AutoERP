import type { NamedResource } from '@/shared/types/common';

export type TaxCalculationMethod = 'percentage' | 'fixed' | 'inclusive' | 'exclusive' | 'compound';

export interface TaxRate {
    id: number;
    rate: string;
    effective_from: string;
    effective_to?: string | null;
    active: boolean;
}

export interface Tax {
    id: number;
    code: string;
    name: string;
    description?: string | null;
    tax_type: string;
    calculation_method: TaxCalculationMethod;
    is_withholding: boolean;
    recoverable: boolean;
    payable: boolean;
    receivable: boolean;
    active: boolean;
    rates?: TaxRate[];
}

export interface TaxPayload {
    code: string;
    name: string;
    description?: string | null;
    tax_type: string;
    calculation_method: TaxCalculationMethod;
    is_withholding: boolean;
    recoverable: boolean;
    payable: boolean;
    receivable: boolean;
    active: boolean;
}

export interface TaxGroupLine {
    tax_id: number;
    sequence: number;
    active: boolean;
    tax?: NamedResource & { tax_type?: string };
}

export interface TaxGroup {
    id: number;
    code: string;
    name: string;
    is_default: boolean;
    active: boolean;
    lines?: TaxGroupLine[];
}

export interface TaxProfile {
    id: number;
    customer_id?: number | null;
    supplier_id?: number | null;
    party?: NamedResource | null;
    tax_group_id?: number | null;
    tax_group?: NamedResource | null;
    registration_number?: string | null;
    exemption_status: string;
    active: boolean;
}

export interface TaxPostingProfile {
    id: number;
    tax_id: number;
    tax?: NamedResource & { tax_type?: string };
    direction: string;
    posting_key: string;
    active: boolean;
}

export interface TaxLookups {
    taxes: Array<NamedResource & { tax_type: string; calculation_method: string }>;
    groups: Array<NamedResource & { is_default: boolean }>;
    posting_keys: string[];
    calculation_methods: TaxCalculationMethod[];
    exemption_statuses: string[];
    posting_directions: string[];
}

export interface TaxReportResult {
    rows: Array<Record<string, string | number | null>>;
    totals: Record<string, string>;
}
