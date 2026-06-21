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
    employee?: NamedResource | null;
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
export interface RentalAllocation {
    id: number;
    allocation_number: string;
    agreement?: NamedResource | null;
    vehicle?: RentalVehicle | null;
    vehicle_source_type: string;
    source_allocation?: NamedResource | null;
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
export interface RentalUsageEvent {
    id?: number;
    event_type: string;
    occurred_at?: string | null;
    quantity: string;
    unit?: string | null;
    reference_number?: string | null;
    remarks?: string | null;
}
export interface RentalUsageLog {
    id: number;
    usage_number: string;
    allocation?: NamedResource | null;
    vehicle?: RentalVehicle | null;
    driver?: NamedResource | null;
    usage_date: string;
    started_at?: string | null;
    ended_at?: string | null;
    start_odometer: string;
    end_odometer: string;
    distance_km: string;
    chargeable_distance_km: string;
    garage_distance_km: string;
    internal_distance_km: string;
    working_minutes: number;
    normal_overtime_minutes: number;
    double_overtime_minutes: number;
    triple_overtime_minutes: number;
    night_out_count: string;
    status: string;
    events: RentalUsageEvent[];
    remarks?: string | null;
}
export interface RentalExpense {
    id: number;
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
    component_code: string;
    description: string;
    chargeable_quantity: string;
    unit?: string | null;
    rate: string;
    net_amount: string;
    tax_amount: string;
    withholding_amount: string;
    total_amount: string;
    status: string;
}
export interface RentalCalculationRun {
    id: number;
    billing_period?: {
        id: number;
        agreement?: NamedResource | null;
        financial_side: string;
        period_start: string;
        period_end: string;
        status: string;
    } | null;
    run_version: number;
    calculation_status: string;
    document_status: string;
    net_total: string;
    tax_total: string;
    withholding_total: string;
    grand_total: string;
    lines: RentalCalculationLine[];
}
export interface RentalDepositLink {
    id: number;
    link_type: string;
    payment_id?: number | null;
    invoice_id?: number | null;
    amount: string;
    status: string;
    linked_at: string;
    reverses_link_id?: number | null;
}
export interface RentalDeposit {
    id: number;
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
    invoice_id?: number | null;
}
export interface VehicleFinanceAgreement {
    id: number;
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
    expense_types: string[];
    expense_allocation_types: string[];
    financial_sides: string[];
    rate_component_codes: string[];
    rate_units: string[];
}
export type RentalPayload = Record<string, unknown>;
