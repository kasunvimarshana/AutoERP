import type { NamedResource } from '@/shared/types/common';

export type VehicleServiceOperationalStatus =
    | 'draft'
    | 'inspected'
    | 'in_progress'
    | 'completed'
    | 'cancelled';

export type VehicleServiceBillingStatus =
    | 'unbilled'
    | 'partially_billed'
    | 'billed';

export type VehicleServicePaymentStatus =
    | 'unpaid'
    | 'partially_paid'
    | 'paid';

export type VehicleServiceLifecycleDimension = 'operational' | 'billing' | 'payment';

export type VehicleServiceLineSourceType =
    | 'inventory_item'
    | 'external_item'
    | 'service_item'
    | 'labour_item'
    | 'combo_parent'
    | 'combo_child';

export type CommissionType = 'none' | 'fixed' | 'percentage';

export interface VehicleServiceVehicle extends NamedResource {
    registration_number?: string | null;
    make?: NamedResource | null;
    model?: NamedResource | null;
    current_ownerships?: Array<{
        id: number;
        owner_type: string;
        owner_id?: number | null;
        owner?: NamedResource | null;
        ownership_type: string;
        started_at?: string | null;
        ended_at?: string | null;
        is_current: boolean;
    }>;
    odometer_reading?: string | null;
    odometer_unit?: string | null;
}

export interface VehicleServiceInspection {
    id: number;
    customer_complaint?: string | null;
    inspection_notes?: string | null;
    diagnosis?: string | null;
    recommended_work?: string | null;
    odometer_reading?: string | null;
    fuel_level?: string | null;
    inspected_by?: NamedResource | null;
    inspected_at?: string | null;
}

export interface VehicleServiceInspectionPayload {
    expected_version?: number;
    customer_complaint?: string;
    inspection_notes?: string;
    diagnosis?: string;
    recommended_work?: string;
    odometer_reading?: string;
    fuel_level?: string;
    mark_inspected?: boolean;
}

export interface VehicleServiceEmployeeAssignment {
    id: number;
    vehicle_service_job_line_id: number;
    employee_id: number;
    employee?: NamedResource | null;
    role_type: string;
    assigned_hours: string;
    rate: string;
    commission_type: CommissionType;
    commission_value: string;
    commission_amount: string;
    status: string;
    assigned_at?: string | null;
    completed_at?: string | null;
}

export interface VehicleServiceEmployeeAssignmentPayload {
    expected_version?: number;
    employee_id: number;
    role_type: string;
    assigned_hours?: string;
    rate?: string;
    commission_type?: CommissionType;
    commission_value?: string;
    status?: 'assigned' | 'completed' | 'cancelled';
}

export interface VehicleServiceJobLine {
    id: number;
    parent_line_id?: number | null;
    line_number: number;
    line_source_type: VehicleServiceLineSourceType;
    item_id?: number | null;
    item?: NamedResource | null;
    item_variant_id?: number | null;
    item_variant?: NamedResource | null;
    uom_id?: number | null;
    uom?: NamedResource | null;
    description: string;
    quantity: string;
    unit_cost: string;
    unit_price: string;
    discount_calculation_type?: 'fixed' | 'percentage' | null;
    discount_rate: string;
    discount_amount: string;
    tax_calculation_type?: 'fixed' | 'percentage' | null;
    tax_rate: string;
    tax_amount: string;
    charge_calculation_type?: 'fixed' | 'percentage' | null;
    charge_rate: string;
    charge_amount: string;
    line_total: string;
    is_inventory_tracked: boolean;
    is_customer_supplied: boolean;
    is_external: boolean;
    is_billable: boolean;
    is_employee_assignable: boolean;
    inventory_movement_id?: number | null;
    stock_on_hand?: string;
    stock_available?: string;
    issue_eligible?: boolean;
    inventory_warning?: string | null;
    invoiced_quantity?: string;
    remaining_billable_quantity?: string;
    invoice_state?: 'uninvoiced' | 'partially_invoiced' | 'invoiced';
    status: string;
    children?: VehicleServiceJobLine[];
    employee_assignments?: VehicleServiceEmployeeAssignment[];
}

export interface VehicleServiceInvoiceLink {
    id: number;
    invoice_id: number;
    invoice_number?: string | null;
    invoice_total: string;
    balance_due?: string | null;
    invoice_status?: string | null;
    status: string;
    can_receive_payment?: boolean;
}

export interface VehicleServicePaymentLink {
    id: number;
    payment_id: number;
    payment_number?: string | null;
    invoice_id?: number | null;
    invoice_number?: string | null;
    allocated_amount: string;
    status: string;
    document_status?: string | null;
    posting_status?: string | null;
    allocation_status?: string | null;
    instrument_status?: string | null;
    payment_method?: VehicleServicePaymentMethod | null;
    reference_number?: string | null;
}

export interface VehicleServiceJob {
    id: number;
    row_version?: number;
    job_number: string;
    job_date: string;
    expected_delivery_date?: string | null;
    customer_id: number;
    customer?: NamedResource | null;
    bill_to_customer_id?: number | null;
    bill_to_customer?: NamedResource | null;
    vehicle_id: number;
    vehicle?: VehicleServiceVehicle | null;
    supervisor_employee_id?: number | null;
    supervisor?: NamedResource | null;
    supervisor_commission_type: CommissionType;
    supervisor_commission_value: string;
    supervisor_commission_amount: string;
    operational_status: VehicleServiceOperationalStatus;
    operational_status_label?: string;
    billing_status: VehicleServiceBillingStatus;
    billing_status_label?: string;
    payment_status: VehicleServicePaymentStatus;
    payment_status_label?: string;
    odometer_reading?: string | null;
    fuel_level?: string | null;
    priority?: string | null;
    subtotal: string;
    discount_total: string;
    tax_total: string;
    charge_total: string;
    grand_total: string;
    notes?: string | null;
    completed_at?: string | null;
    inspection?: VehicleServiceInspection | null;
    lines?: VehicleServiceJobLine[];
    invoice_links?: VehicleServiceInvoiceLink[];
    payment_links?: VehicleServicePaymentLink[];
}

export interface VehicleServiceJobPayload {
    expected_version?: number;
    job_date: string;
    expected_delivery_date?: string;
    customer_id: number;
    bill_to_customer_id?: number;
    vehicle_id: number;
    supervisor_employee_id?: number;
    supervisor_commission_type?: CommissionType;
    supervisor_commission_value?: string;
    odometer_reading?: string;
    fuel_level?: string;
    priority?: string;
    notes?: string;
    customer_complaint?: string;
}

export interface VehicleServiceLinePayload {
    expected_version?: number;
    line_source_type: VehicleServiceLineSourceType;
    item_id?: number;
    uom_id?: number;
    description: string;
    quantity: string;
    unit_cost?: string;
    unit_price: string;
    discount_calculation_type?: 'fixed' | 'percentage';
    discount_rate?: string;
    discount_amount?: string;
    tax_calculation_type?: 'fixed' | 'percentage';
    tax_rate?: string;
    tax_amount?: string;
    charge_calculation_type?: 'fixed' | 'percentage';
    charge_rate?: string;
    charge_amount?: string;
    is_customer_supplied?: boolean;
    is_billable?: boolean;
    expand_combo?: boolean;
}

export interface VehicleServiceInvoicePreview {
    subtotal: string;
    discountTotal: string;
    taxTotal: string;
    chargeTotal: string;
    grandTotal: string;
}

export interface VehicleServiceInvoicePayload {
    expected_version?: number;
    invoice_date: string;
    due_date?: string;
    currency_id?: number;
    exchange_rate?: string;
    notes?: string;
    line_quantities?: Record<number, string>;
}

export interface VehicleServiceInvoiceCreated {
    id: number;
    invoice_number?: string | null;
    status?: string | null;
    posted_at?: string | null;
}

export interface VehicleServiceStatusHistory {
    id: number;
    dimension: VehicleServiceLifecycleDimension;
    old_status?: string | null;
    new_status: string;
    reason?: string | null;
    changed_by?: number | null;
    changed_at?: string | null;
}

export interface VehicleServicePaymentMethod extends NamedResource {
    method_type: string;
    requires_reference: boolean;
    requires_instrument_details: boolean;
}

export interface VehicleServicePaymentOption {
    invoice_id: number;
    invoice_number?: string | null;
    remaining_amount: string;
    currency_id?: number | null;
    due_date?: string | null;
    payment_allowed: boolean;
}
