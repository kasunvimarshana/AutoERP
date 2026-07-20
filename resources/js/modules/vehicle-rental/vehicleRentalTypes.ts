export interface RentalReference {
    id: number;
    code?: string | null;
    name?: string | null;
}

export interface RentalAgreement {
    id: number;
    row_version: number;
    agreement_number: string;
    kind: 'customer' | 'owner' | string;
    customer?: RentalReference | null;
    supplier?: RentalReference | null;
    executed_at?: string | null;
    starts_on?: string | null;
    ends_on?: string | null;
    billing_basis?: string | null;
    currency?: RentalReference | null;
    included_km: string;
    deposit_required: boolean;
    deposit_amount: string;
    payment_terms_days: number;
    status: string;
}

export interface RentalAssignment {
    id: number;
    row_version: number;
    side: string;
    status: string;
    agreement?: (RentalReference & {
        kind?: string | null;
        party?: RentalReference | null;
    }) | null;
    vehicle?: (RentalReference & { registration_number?: string | null }) | null;
    driver?: RentalReference | null;
    starts_at?: string | null;
    ends_at?: string | null;
    handover_odometer?: string | null;
    return_odometer?: string | null;
    self_drive: boolean;
}

export interface RentalRunningChart {
    id: number;
    row_version: number;
    chart_number: string;
    status: string;
    operational_date?: string | null;
    starts_at?: string | null;
    ends_at?: string | null;
    assignment?: {
        id: number;
        agreement?: RentalReference | null;
        vehicle?: RentalReference | null;
        owner_agreement?: RentalReference | null;
    } | null;
    driver?: RentalReference | null;
    ac_mode?: string | null;
    start_odometer: string;
    end_odometer: string;
    total_km: string;
    garage_km: string;
    commercial_km: string;
    normal_overtime_hours: string;
    double_overtime_hours: string;
    triple_overtime_hours: string;
    night_out_count: number;
}

export interface RentalCalculation {
    id: number;
    row_version: number;
    calculation_number: string;
    side: string;
    status: string;
    agreement?: (RentalReference & { kind?: string | null }) | null;
    rate_version?: {
        id: number;
        version_number: number;
        effective_from?: string | null;
        effective_to?: string | null;
    } | null;
    currency?: RentalReference | null;
    period_start?: string | null;
    period_end?: string | null;
    chart_count: number;
    operating_days: number;
    commercial_km: string;
    included_km: string;
    excess_km: string;
    subtotal_amount: string;
}
