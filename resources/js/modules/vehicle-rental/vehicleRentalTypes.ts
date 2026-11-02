import type { NamedResource } from "@/shared/types/common";

export interface RentalParty extends NamedResource {
    code?: string;
    customer_number?: string;
    supplier_number?: string;
    display_name?: string;
}
export interface RentalVehicle extends NamedResource {
    vehicle_number?: string;
    registration_number?: string;
    make?: string;
    model?: string;
    status?: string;
    vehicle_category?: NamedResource | null;
}
export interface RentalCurrency extends NamedResource {
    code?: string;
    symbol?: string;
}
export interface RentalReservation {
    id: number;
    row_version: number;
    reservation_number: string;
    customer?: RentalParty | null;
    requested_vehicle?: RentalVehicle | null;
    requested_vehicle_category?: NamedResource | null;
    rental_mode: string;
    billing_cycle: string;
    requested_start_at: string;
    requested_end_at: string;
    currency?: RentalCurrency | null;
    estimated_amount: string;
    estimated_deposit_amount: string;
    status: string;
    source?: string | null;
    remarks?: string | null;
}
export interface RentalRateComponent {
    id?: number;
    component_code: string;
    unit: string;
    included_quantity: string;
    rate: string;
    multiplier: string;
    is_taxable: boolean;
    calculation_order: number;
}
export interface RentalRateVersion {
    id: number;
    version_number: number;
    effective_from: string;
    effective_to?: string | null;
    driver_mode: string;
    billing_cycle: string;
    billing_basis: string;
    proration_rule: string;
    excess_km_method: string;
    included_km: string;
    included_hours: string;
    weekday_included_minutes: number;
    saturday_included_minutes: number;
    holiday_included_minutes: number;
    status: string;
    components: RentalRateComponent[];
}
export interface RentalAgreement {
    id: number;
    row_version: number;
    agreement_number: string;
    agreement_kind: string;
    customer?: RentalParty | null;
    supplier?: RentalParty | null;
    agreement_date: string;
    starts_at: string;
    ends_at: string;
    actual_ended_at?: string | null;
    legal_context?: string | null;
    rental_mode: string;
    billing_cycle: string;
    billing_basis: string;
    proration_rule: string;
    billing_timezone: string;
    payment_term_days?: number | null;
    currency?: RentalCurrency | null;
    status: string;
    remarks?: string | null;
    active_rate_version?: RentalRateVersion | null;
    rate_versions?: RentalRateVersion[];
    allocations?: RentalAllocation[];
    deposit_requirement?: RentalDeposit | null;
}
export interface RentalDriverAssignment {
    id: number;
    employee?: (NamedResource & {
        employee_number?: string;
        first_name?: string;
        last_name?: string;
        display_name?: string;
        default_hourly_rate?: string | null;
        default_daily_rate?: string | null;
        work_start_time?: string | null;
        work_end_time?: string | null;
        standard_shift_hours?: number | string | null;
    }) | null;
    assignment_role: string;
    assigned_from: string;
    assigned_to?: string | null;
    is_primary: boolean;
    status: string;
}
export interface RentalCustodyItem {
    id?: number;
    item_type: string;
    item_code?: string | null;
    description: string;
    condition_status?: string | null;
    is_chargeable: boolean;
    estimated_amount: string;
    responsible_side?: string | null;
}
export interface RentalCustodyEvent {
    id: number;
    row_version: number;
    event_number: string;
    vehicle?: RentalVehicle | null;
    allocation?: NamedResource | null;
    event_type: string;
    occurred_at: string;
    odometer: string;
    fuel_level_percent?: string | null;
    location?: string | null;
    from_role: string;
    to_role: string;
    condition_summary?: string | null;
    damage_summary?: string | null;
    status: string;
    items: RentalCustodyItem[];
}
export interface RentalAllocationAgreementSummary extends NamedResource {
    agreement_number?: string;
    agreement_kind?: string;
    status?: string;
    supplier?: RentalParty | null;
    customer?: RentalParty | null;
}
export interface RentalAllocation {
    id: number;
    row_version: number;
    allocation_number: string;
    agreement?: RentalAllocationAgreementSummary | null;
    vehicle?: RentalVehicle | null;
    vehicle_source_type: string;
    source_allocation?: (NamedResource & {
        status?: string;
        allocation_number?: string;
        vehicle_source_type?: string;
        vehicle?: RentalVehicle | null;
        agreement?: {
            id: number;
            agreement_number: string;
            agreement_kind?: string;
            supplier?: RentalParty | null;
        } | null;
    }) | null;
    replaces_allocation?: (NamedResource & { status?: string; allocation_number?: string; vehicle_source_type?: string; vehicle?: RentalVehicle | null }) | null;
    replacement_allocation?: (NamedResource & { status?: string; allocation_number?: string; vehicle_source_type?: string; vehicle?: RentalVehicle | null }) | null;
    replacement_record?: {
        id: number;
        replacement_number: string;
        replacement_at?: string | null;
        reason_code?: string | null;
        reason?: string | null;
        billing_continuity_rule?: string | null;
        status: string;
    } | null;
    replacement_origin?: {
        id: number;
        replacement_number: string;
        replacement_at?: string | null;
        reason_code?: string | null;
        reason?: string | null;
        billing_continuity_rule?: string | null;
        status: string;
    } | null;
    allocated_from: string;
    allocated_to?: string | null;
    actual_returned_at?: string | null;
    start_odometer?: string | null;
    end_odometer?: string | null;
    status: string;
    remarks?: string | null;
    drivers?: RentalDriverAssignment[];
    custody_events?: RentalCustodyEvent[];
}
export type RentalUsageEventApplicability =
    | "customer"
    | "owner"
    | "both"
    | "internal";
export interface RentalUsageEvent {
    id?: number;
    event_type: string;
    applicability: RentalUsageEventApplicability;
    occurred_at?: string | null;
    quantity: string;
    unit?: string | null;
    reference_number?: string | null;
    remarks?: string | null;
}
export interface RentalUsageFact {
    id: number;
    row_version: number;
    financial_side: "revenue" | "cost";
    context_id: number;
    usage_log_id: number;
    agreement?: NamedResource | null;
    started_at: string;
    ended_at: string;
    start_odometer: string;
    end_odometer: string;
    commercial_distance_km: string;
    working_minutes: number;
    normal_overtime_minutes: number;
    double_overtime_minutes: number;
    triple_overtime_minutes: number;
    night_out_count: string;
    reference_number?: string | null;
    variance_reason?: string | null;
    status: string;
    submitted_at?: string | null;
    approved_at?: string | null;
    rejected_at?: string | null;
    reversed_at?: string | null;
    reversal_reason?: string | null;
    remarks?: string | null;
}
export interface RentalUsageContext {
    id: number;
    financial_side: "revenue" | "cost";
    agreement?: NamedResource | null;
    rate_version_id: number;
    customer?: RentalParty | null;
    supplier?: RentalParty | null;
    usage_fact?: RentalUsageFact | null;
}
export interface RentalUsageLog {
    id: number;
    row_version: number;
    usage_number: string;
    allocation?: NamedResource | null;
    vehicle?: RentalVehicle | null;
    driver_assignment?: RentalDriverAssignment | null;
    driver?: NamedResource | null;
    usage_date: string;
    started_at: string;
    ended_at: string;
    start_odometer: string;
    end_odometer: string;
    distance_km: string;
    net_operational_distance_km: string;
    garage_distance_km: string;
    internal_distance_km: string;
    working_minutes: number;
    normal_overtime_minutes: number;
    double_overtime_minutes: number;
    triple_overtime_minutes: number;
    night_out_count: string;
    trip_from?: string | null;
    trip_to?: string | null;
    trip_purpose?: string | null;
    odometer_variance_reason?: string | null;
    status: string;
    events: RentalUsageEvent[];
    contexts: RentalUsageContext[];
    reversal_reason?: string | null;
    remarks?: string | null;
    metadata?: {
        driver_in_time?: string;
        driver_out_time?: string;
        normal_hours_threshold?: number | string;
        driver_hourly_rate?: number | string;
        driver_daily_rate?: number | string;
        driver_ot_hours?: number | string;
        driver_ot_amount?: number | string;
        driver_salary_amount?: number | string;
        remarks?: string;
        trip_purpose?: string;
        [key: string]: unknown;
    } | null;
}
export interface RentalExpense {
    id: number;
    row_version: number;
    expense_number: string;
    vehicle?: RentalVehicle | null;
    expense_type: string;
    expense_date: string;
    net_amount: string;
    tax_amount: string;
    gross_amount: string;
    reference_number?: string | null;
    description?: string | null;
    status: string;
}
export interface RentalCalculationLine {
    id: number;
    line_number: number;
    source_type: string;
    source_id: number;
    component_code: string;
    description: string;
    measured_quantity: string;
    allowed_quantity: string;
    chargeable_quantity: string;
    unit?: string | null;
    rate: string;
    multiplier: string;
    net_amount: string;
    discount_amount: string;
    tax_amount: string;
    withholding_amount: string;
    total_amount: string;
    applied_rule: string;
    status: string;
}
export interface RentalCalculationSource {
    id: number;
    source_kind: "usage_context" | "expense_allocation";
    status: string;
    usage?: NamedResource | null;
    financial_side?: "revenue" | "cost" | null;
    usage_fact?: {
        id: number;
        row_version: number;
        status: string;
    } | null;
    expense_allocation?: NamedResource | null;
}
export interface RentalCalculationRun {
    id: number;
    row_version: number;
    billing_period?: {
        id: number;
        agreement?: NamedResource | null;
        financial_side: string;
        period_start: string;
        period_end: string;
        status: string;
    } | null;
    run_version: number;
    currency?: RentalCurrency | null;
    calculation_status: string;
    document_status: string;
    net_total: string;
    discount_total: string;
    tax_total: string;
    withholding_total: string;
    grand_total: string;
    sources: RentalCalculationSource[];
    lines: RentalCalculationLine[];
}
export interface RentalDepositLink {
    id: number;
    link_type: string;
    payment?: (NamedResource & {
        payment_number?: string | null;
        row_version?: number;
        document_status?: string | null;
        posting_status?: string | null;
        unapplied_amount?: string | null;
    }) | null;
    invoice?: (NamedResource & {
        invoice_number?: string | null;
        row_version?: number;
        status?: string | null;
        balance_due?: string | null;
    }) | null;
    amount: string;
    status: string;
    linked_at: string;
    reverses_link_id?: number | null;
}
export interface RentalDeposit {
    id: number;
    row_version: number;
    agreement?: NamedResource | null;
    required_amount: string;
    received_amount: string;
    applied_amount: string;
    refunded_amount: string;
    balance_amount: string;
    status: string;
    due_date?: string | null;
    is_refundable: boolean;
    links?: RentalDepositLink[];
}
export interface VehicleFinanceInstallment {
    id: number;
    row_version: number;
    installment_number: number;
    due_date: string;
    principal_due: string;
    interest_due: string;
    fee_due: string;
    tax_due: string;
    total_due: string;
    paid_amount: string;
    balance_due: string;
    status: string;
    invoice?: NamedResource | null;
}
export interface VehicleFinanceAgreement {
    id: number;
    row_version: number;
    agreement_number: string;
    supplier?: RentalParty | null;
    vehicle?: RentalVehicle | null;
    agreement_date: string;
    starts_at: string;
    matures_at: string;
    principal_amount: string;
    initial_deposit_amount: string;
    residual_value: string;
    annual_interest_rate: string;
    installment_frequency: string;
    installment_count: number;
    status: string;
    installments: VehicleFinanceInstallment[];
}
export interface RentalMetadata {
    agreement_kinds: string[];
    agreement_statuses: string[];
    allocation_statuses: string[];
    rental_modes: string[];
    billing_cycles: string[];
    billing_bases: string[];
    proration_rules: string[];
    excess_km_methods: string[];
    vehicle_source_types: string[];
    custody_event_types: string[];
    usage_event_types: string[];
    usage_event_applicabilities: RentalUsageEventApplicability[];
    expense_types: string[];
    expense_allocation_types: string[];
    financial_sides: string[];
    rate_component_codes: string[];
    rate_units: string[];
}
export type RentalPayload = Record<string, unknown>;

export interface RentalRunningChartReportHeader {
    company_name: string;
    report_title: string;
    report_subtitle: string;
    client_name: string;
    duration: string;
    start_date: string;
    end_date: string;
    page_number: number;
    report_date: string;
    agreement_no: string;
    agreement_number: string;
    agreement_vehicle_no: string;
    agreement_vehicle_number: string;
    rental_mode: string;
    billing_basis: string;
    included_km: number;
}

export interface RentalRunningChartReportRow {
    date: string;
    raw_date: string;
    start_odometer: string;
    finish_odometer: string;
    comparative_mileage: number;
    cumulative_mileage: number;
    on_time: string;
    off_time: string;
    ot_hours: string;
    ot_type: string;
    night_out: string;
    pass: string;
    remarks: string;
    driver_ot_amount?: string;
    driver_salary_amount?: string;
}

export interface RentalRunningChartReportSummary {
    total_comparative_mileage: number;
    final_cumulative_mileage: number;
    total_normal_ot_hours: string;
    total_double_ot_hours: string;
    total_night_out: number;
    total_garage_km: number;
    total_pass: string;
    total_driver_ot_amount?: string;
    total_driver_salary_amount?: string;
    included_km: number;
    excess_km: number;
}

export interface RentalRunningChartReportData {
    header: RentalRunningChartReportHeader;
    rows: RentalRunningChartReportRow[];
    summary: RentalRunningChartReportSummary;
}

export interface RentalRunningChartReportParams {
    agreement_id?: number;
    allocation_id?: number;
    start_date: string;
    end_date: string;
}
