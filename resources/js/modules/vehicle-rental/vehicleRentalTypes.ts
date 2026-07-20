export interface RentalReference {
    id: number;
    code?: string | null;
    name?: string | null;
}

export interface RentalAgreementFormLookups {
    tax_groups: RentalReference[];
}

export type RentalAgreementKind = 'customer' | 'owner';
export type RentalBillingBasis = 'daily' | 'monthly';
export type RentalAssignmentSide = 'customer_use' | 'owner_supply';
export type RentalRunningChartStatus = 'draft' | 'finalized' | 'reversed';
export type RentalCalculationStatus = 'calculated' | 'cancelled';
export type RentalCalculationSide = 'customer' | 'owner';
export type RentalAcMode = 'non_ac' | 'front_ac' | 'dual_ac';
export type RentalRateCode =
    | 'base_rental'
    | 'excess_km'
    | 'non_ac'
    | 'front_ac'
    | 'dual_ac'
    | 'driver_salary'
    | 'normal_overtime'
    | 'double_overtime'
    | 'triple_overtime'
    | 'night_out'
    | 'other';
export type RentalRateUnit = 'day' | 'month' | 'kilometre' | 'hour' | 'occurrence' | 'fixed';

export interface RentalRateLine {
    id?: number;
    code: RentalRateCode;
    unit: RentalRateUnit;
    rate: string;
    is_taxable: boolean;
    description?: string | null;
}

export interface RentalRateVersion {
    id: number;
    row_version: number;
    version_number: number;
    effective_from?: string | null;
    effective_to?: string | null;
    status: string;
    rates: RentalRateLine[];
}

export interface RentalAgreement {
    id: number;
    row_version: number;
    agreement_number: string;
    kind: RentalAgreementKind;
    customer?: RentalReference | null;
    supplier?: RentalReference | null;
    executed_at?: string | null;
    starts_on?: string | null;
    ends_on?: string | null;
    billing_basis: RentalBillingBasis;
    currency?: RentalReference | null;
    tax_group?: RentalReference | null;
    included_km: string;
    deposit_required: boolean;
    deposit_amount: string;
    payment_terms_days: number;
    status: string;
    terms?: string | null;
    notes?: string | null;
    rate_versions?: RentalRateVersion[];
}

export interface RentalCustodyEvent {
    id: number;
    event_type: 'handover' | 'return' | string;
    event_at?: string | null;
    odometer: string;
    fuel_level?: string | null;
    condition_notes?: string | null;
    damage_notes?: string | null;
}

export interface RentalAssignment {
    id: number;
    row_version: number;
    side: RentalAssignmentSide;
    status: string;
    agreement?: (RentalReference & {
        kind?: RentalAgreementKind | null;
        party?: RentalReference | null;
    }) | null;
    vehicle?: (RentalReference & { registration_number?: string | null }) | null;
    driver?: RentalReference | null;
    source_assignment?: (RentalReference & { agreement?: RentalReference | null }) | null;
    replaces_assignment?: (RentalReference & { vehicle?: RentalReference | null }) | null;
    starts_at?: string | null;
    ends_at?: string | null;
    handover_odometer?: string | null;
    return_odometer?: string | null;
    self_drive: boolean;
    replacement_reason?: string | null;
    custody_events?: RentalCustodyEvent[];
}

export interface RentalRunningChart {
    id: number;
    row_version: number;
    chart_number: string;
    status: RentalRunningChartStatus;
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
    ac_mode?: RentalAcMode | null;
    start_odometer: string;
    end_odometer: string;
    total_km: string;
    garage_km: string;
    commercial_km: string;
    normal_overtime_hours: string;
    double_overtime_hours: string;
    triple_overtime_hours: string;
    night_out_count: number;
    trip_origin?: string | null;
    trip_destination?: string | null;
    purpose?: string | null;
    odometer_variance_reason?: string | null;
    remarks?: string | null;
    replaces_running_chart?: { id: number; chart_number: string } | null;
    finalized_at?: string | null;
    reversed_at?: string | null;
    reversal_reason?: string | null;
}

export interface RentalCalculationLine {
    id: number;
    line_number: number;
    rate_code: RentalRateCode;
    unit: RentalRateUnit;
    quantity: string;
    unit_rate: string;
    line_total: string;
    is_taxable: boolean;
    description?: string | null;
}

export interface RentalCalculationSource {
    id: number;
    active: boolean;
    running_chart?: {
        id: number;
        chart_number: string;
        operational_date?: string | null;
    } | null;
}

export interface RentalCalculation {
    id: number;
    row_version: number;
    calculation_number: string;
    side: RentalCalculationSide;
    status: RentalCalculationStatus;
    agreement?: (RentalReference & { kind?: RentalAgreementKind | null }) | null;
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
    lines?: RentalCalculationLine[];
    sources?: RentalCalculationSource[];
    cancelled_at?: string | null;
    cancellation_reason?: string | null;
}

export interface RentalAgreementPayload {
    kind: RentalAgreementKind;
    customer_id: number | null;
    supplier_id: number | null;
    agreement_number?: string | null;
    executed_at?: string | null;
    starts_on: string;
    ends_on?: string | null;
    billing_basis: RentalBillingBasis;
    currency_id: number;
    tax_group_id?: number | null;
    included_km: string;
    deposit_required: boolean;
    deposit_amount: string;
    payment_terms_days: number;
    terms?: string | null;
    notes?: string | null;
    rates: RentalRateLine[];
    expected_version?: number;
}

export interface RentalRateVersionPayload {
    effective_from: string;
    rates: RentalRateLine[];
    expected_version: number;
}

export interface RentalAssignmentPayload {
    agreement_id: number;
    vehicle_id: number;
    side: RentalAssignmentSide;
    starts_at: string;
    ends_at?: string | null;
    source_assignment_id?: number | null;
    handover_odometer?: string | null;
    driver_employee_id?: number | null;
    self_drive: boolean;
}

export interface RentalCustodyPayload {
    event_type: 'handover' | 'return';
    event_at: string;
    odometer: string;
    fuel_level?: string | null;
    condition_notes?: string | null;
    damage_notes?: string | null;
    expected_version: number;
}

export interface RentalReplacementPayload {
    vehicle_id: number;
    effective_at: string;
    old_return_odometer: string;
    new_handover_odometer: string;
    source_assignment_id?: number | null;
    driver_employee_id?: number | null;
    self_drive: boolean;
    reason: string;
    old_fuel_level?: string | null;
    new_fuel_level?: string | null;
    old_condition_notes?: string | null;
    new_condition_notes?: string | null;
    old_damage_notes?: string | null;
    new_damage_notes?: string | null;
    expected_version: number;
}

export interface RentalRunningChartPayload {
    assignment_id?: number;
    operational_date: string;
    starts_at: string;
    ends_at: string;
    start_odometer: string;
    end_odometer: string;
    garage_km: string;
    normal_overtime_hours: string;
    double_overtime_hours: string;
    triple_overtime_hours: string;
    night_out_count: number;
    ac_mode?: RentalAcMode | null;
    trip_origin?: string | null;
    trip_destination?: string | null;
    purpose?: string | null;
    odometer_variance_reason?: string | null;
    remarks?: string | null;
    expected_version?: number;
}
