import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export type RentalDirection = 'outbound' | 'inbound';
export type RentalPartyType = 'customer' | 'supplier' | 'owner';
export type RentalType = 'hourly' | 'daily' | 'weekly' | 'monthly' | 'lease' | 'subscription' | 'with_driver' | 'without_driver';
export type RentalAgreementStatus = 'draft' | 'confirmed' | 'active' | 'returned' | 'completed' | 'cancelled';
export type RentalBillingCycle = 'hourly' | 'per_trip' | 'daily' | 'weekly' | 'monthly' | 'anniversary_cycle' | 'fixed_period' | 'final';
export type RentalBillingBasis = 'calendar_month' | 'anniversary_month' | 'fixed_30_day' | 'exact_day_count' | 'contractual_period';

export interface RentalVehicleSummary extends NamedResource {
    registration_number?: string | null;
    make?: string | null;
    model?: string | null;
}

export interface RentalRateSnapshot {
    id?: number;
    base_rate: string;
    rate_unit: 'hour' | 'day' | 'week' | 'month' | 'km' | 'trip';
    allowed_hours: string;
    allowed_km: string;
    extra_hour_rate: string;
    extra_km_rate: string;
    overtime_rate: string;
    double_overtime_rate: string;
    night_shift_rate: string;
    weekend_rate: string;
    holiday_rate: string;
    driver_rate: string;
    outstation_rate: string;
    day_out_rate: string;
    night_out_rate: string;
    fuel_rate: string;
    waiting_hour_rate: string;
    tax_profile_id?: number | null;
    currency_id?: number | null;
}

export interface RentalInspection {
    id: number;
    agreement_vehicle_id: number;
    vehicle_id: number;
    inspected_at: string;
    odometer: string;
    fuel_level?: string | null;
    condition_notes?: string | null;
    damage_notes?: string | null;
    damage_amount?: string | null;
    is_damage_billable?: boolean | null;
    attachments: unknown[];
}

export interface RentalAgreementVehicle {
    id: number;
    vehicle_id: number;
    vehicle?: RentalVehicleSummary | null;
    owner_party_type?: RentalPartyType | null;
    owner_party_id?: number | null;
    allocated_from: string;
    allocated_to?: string | null;
    start_odometer: string;
    end_odometer?: string | null;
    status: 'allocated' | 'active' | 'replaced' | 'returned';
    remarks?: string | null;
    pickup_inspection?: RentalInspection | null;
    return_inspection?: RentalInspection | null;
}

export interface RentalUsageEvent {
    id: number;
    event_type: string;
    quantity: string;
    remarks?: string | null;
}

export interface RentalUsageContext {
    id: number;
    agreement_id: number;
    agreement_vehicle_id: number;
    agreement_vehicle_link_id?: number | null;
    agreement_number?: string | null;
    agreement_direction: RentalDirection;
    financial_side: 'revenue' | 'cost';
    party_type: RentalPartyType;
    party_id: number;
    party?: NamedResource | null;
    rate_snapshot?: RentalRateSnapshot | null;
}

export interface RentalUsageLog {
    id: number;
    agreement_vehicle_id: number;
    vehicle_id: number;
    vehicle?: RentalVehicleSummary | null;
    driver_id?: number | null;
    driver?: NamedResource | null;
    usage_date: string;
    start_time?: string | null;
    end_time?: string | null;
    working_minutes: number;
    start_odometer: string;
    end_odometer: string;
    distance_km: string;
    cumulative_km?: string | null;
    comparative_km?: string | null;
    trip_from?: string | null;
    trip_to?: string | null;
    trip_purpose?: string | null;
    status: 'draft' | 'submitted' | 'approved' | 'rejected';
    remarks?: string | null;
    events: RentalUsageEvent[];
    contexts: RentalUsageContext[];
}

export interface RentalExpense {
    id: number;
    usage_log_id?: number | null;
    expense_type: string;
    expense_date: string;
    currency_id?: number | null;
    amount: string;
    tax_group_id?: number | null;
    tax_amount: string;
    withholding_amount: string;
    original_net_amount: string;
    original_tax_group_id?: number | null;
    original_tax_amount: string;
    original_gross_amount: string;
    original_withholding_amount: string;
    recoverable_input_tax_amount: string;
    recovery_base_amount: string;
    recovery_tax_group_id?: number | null;
    recovery_tax_amount: string;
    recovery_withholding_amount: string;
    markup_amount: string;
    generated_charge_id?: number | null;
    financial_treatment: 'company_borne' | 'customer_billable' | 'supplier_recoverable' | 'employee_reimbursable' | 'owner_payable';
    is_billable: boolean;
    is_recoverable: boolean;
    is_reimbursable: boolean;
    responsible_party_type?: string | null;
    responsible_party_id?: number | null;
    charge_generation_status: string;
    receipt_no?: string | null;
    reference_no?: string | null;
    description?: string | null;
    status: 'draft' | 'submitted' | 'approved' | 'rejected' | 'charged';
}

export interface RentalCharge {
    id: number;
    billing_period_id?: number | null;
    charge_run_id?: number | null;
    billing_period_start?: string | null;
    billing_period_end?: string | null;
    billing_cycle_key?: string | null;
    period_sequence?: number | null;
    financial_side: 'revenue' | 'cost';
    charge_type: string;
    description: string;
    quantity: string;
    rate: string;
    amount: string;
    discount_amount: string;
    tax_amount: string;
    withholding_amount: string;
    tax_group_id?: number | null;
    total_amount: string;
    invoice_status: 'not_invoiced' | 'partially_invoiced' | 'invoiced';
    status: 'draft' | 'approved' | 'cancelled';
    invoiced_quantity?: string | null;
    remaining_invoice_quantity?: string | null;
}

export interface RentalInvoiceLink {
    id: number;
    charge_id: number;
    invoice_id: number;
    invoice_number?: string | null;
    invoiced_quantity: string;
    invoiced_amount: string;
    balance_due?: string | null;
    invoice_status?: string | null;
    status: string;
}

export interface RentalPaymentLink {
    id: number;
    payment_id: number;
    payment_number?: string | null;
    invoice_id?: number | null;
    invoice_number?: string | null;
    link_type: string;
    amount: string;
    status: string;
}

export interface RentalReservation {
    id: number;
    reservation_number: string;
    direction: RentalDirection;
    party_type: RentalPartyType;
    party_id: number;
    party?: NamedResource | null;
    rental_type: RentalType;
    vehicle_id?: number | null;
    vehicle?: RentalVehicleSummary | null;
    start_at: string;
    expected_end_at: string;
    status: string;
    remarks?: string | null;
}

export interface RentalAgreement {
    id: number;
    agreement_number: string;
    reservation_id?: number | null;
    direction: RentalDirection;
    party_type: RentalPartyType;
    party_id: number;
    party?: NamedResource | null;
    rental_type: RentalType;
    billing_cycle: RentalBillingCycle;
    billing_basis: RentalBillingBasis;
    proration_rule: string;
    billing_timezone: string;
    billing_period_days?: number | null;
    agreement_date: string;
    start_at: string;
    expected_end_at: string;
    actual_end_at?: string | null;
    status: RentalAgreementStatus;
    status_label: string;
    remarks?: string | null;
    rate_snapshot?: RentalRateSnapshot | null;
    vehicles: RentalAgreementVehicle[];
    usage_logs: RentalUsageLog[];
    expenses: RentalExpense[];
    charges: RentalCharge[];
    invoice_links: RentalInvoiceLink[];
    payment_links: RentalPaymentLink[];
    vehicle_links: RentalAgreementVehicleLink[];
}

export interface RentalAgreementVehicleLink {
    id: number;
    vehicle_id: number;
    inbound_agreement_id: number;
    inbound_agreement_vehicle_id: number;
    outbound_agreement_id: number;
    outbound_agreement_vehicle_id: number;
    effective_from: string;
    effective_to: string;
    status: 'draft' | 'submitted' | 'approved' | 'cancelled' | 'superseded';
    remarks?: string | null;
}

export interface RunningChartAgreementOption {
    agreement_id: number;
    agreement_vehicle_id: number;
    agreement_number: string;
    direction: RentalDirection;
    party_type: RentalPartyType;
    party_id: number;
    party_name?: string | null;
    vehicle_id: number;
    vehicle_registration?: string | null;
    rental_type: RentalType;
    billing_cycle: string;
    start_at: string;
    expected_end_at: string;
    allocation_from: string;
    allocation_to?: string | null;
    status: RentalAgreementStatus;
}

export interface RunningChartResolvedContext {
    agreement_id: number;
    agreement_vehicle_id: number;
    agreement_number: string;
    direction: RentalDirection;
    financial_side: 'revenue' | 'cost';
    party_type: RentalPartyType;
    party_id: number;
    party_name?: string | null;
    billing_cycle: string;
    currency_id?: number | null;
    rate_snapshot: RentalRateSnapshot;
}

export interface RunningChartContext {
    mode?: RunningChartMode | null;
    vehicle_id: number;
    vehicle?: {
        id: number;
        vehicle_number: string;
        registration_number?: string | null;
        odometer_reading: string;
    } | null;
    selected_agreement_id: number;
    agreement_vehicle_link_id?: number | null;
    agreement_vehicle_link?: RentalAgreementVehicleLink | null;
    last_valid_finish_odometer: string;
    approved_cumulative_mileage: string;
    contexts: RunningChartResolvedContext[];
}

export type RunningChartMode = 'lessee' | 'lessor' | 'linked';

export interface RunningChartTripPayload {
    id?: number;
    tenant_id?: number;
    organization_unit_id?: number | null;
    mode: RunningChartMode;
    lessee_agreement_id?: number;
    lessee_agreement_vehicle_id?: number;
    lessor_agreement_id?: number;
    lessor_agreement_vehicle_id?: number;
    usage_date: string;
    driver_id?: number | null;
    start_time: string;
    end_time: string;
    start_odometer: string;
    end_odometer: string;
    comparative_km?: string;
    trip_from?: string;
    trip_to?: string;
    trip_purpose?: string;
    remarks?: string;
    events?: Array<{ event_type: string; quantity: string; remarks?: string | null }>;
}

export interface RunningChartPreview {
    daily_km: string;
    working_minutes: number;
    working_hours: string;
    overtime_hours: string;
    customer_revenue: string;
    owner_cost: string;
    estimated_margin: string;
    persistent: false;
    contexts: Array<{
        agreement_id: number;
        financial_side: 'revenue' | 'cost';
        estimated_total: string;
        lines: Array<{ type: string; quantity: string; rate: string; unit?: string | null; amount: string }>;
    }>;
}

export interface RentalAvailabilityRow {
    vehicle: RentalVehicleSummary;
    available: boolean;
    reasons: string[];
}

export interface RentalInvoicePreview {
    subtotal: string;
    discountTotal: string;
    taxTotal: string;
    chargeTotal: string;
    grandTotal: string;
    lines?: unknown[];
}

export type RentalAgreementCollection = ApiCollection<RentalAgreement>;
export type RentalReservationCollection = ApiCollection<RentalReservation>;
export type RunningChartAgreementCollection = ApiCollection<RunningChartAgreementOption>;
