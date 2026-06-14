import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export type RentalDirection = 'outbound' | 'inbound';
export type RentalPartyType = 'customer' | 'supplier' | 'owner';
export type RentalType = 'hourly' | 'daily' | 'weekly' | 'monthly' | 'lease' | 'subscription' | 'with_driver' | 'without_driver';
export type RentalAgreementStatus = 'draft' | 'confirmed' | 'active' | 'returned' | 'completed' | 'cancelled';

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
    rate_snapshot: string;
    amount: string;
    remarks?: string | null;
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
    start_odometer: string;
    end_odometer: string;
    distance_km: string;
    cumulative_km: string;
    comparative_km?: string | null;
    trip_from?: string | null;
    trip_to?: string | null;
    trip_purpose?: string | null;
    status: 'draft' | 'submitted' | 'approved' | 'rejected';
    remarks?: string | null;
    events: RentalUsageEvent[];
}

export interface RentalExpense {
    id: number;
    usage_log_id?: number | null;
    expense_type: string;
    amount: string;
    is_billable: boolean;
    receipt_no?: string | null;
    reference_no?: string | null;
    description?: string | null;
    status: 'draft' | 'approved' | 'rejected' | 'charged';
}

export interface RentalCharge {
    id: number;
    charge_type: string;
    description: string;
    quantity: string;
    rate: string;
    amount: string;
    discount_amount: string;
    tax_amount: string;
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
    billing_cycle: 'per_trip' | 'daily' | 'weekly' | 'monthly' | 'final';
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
