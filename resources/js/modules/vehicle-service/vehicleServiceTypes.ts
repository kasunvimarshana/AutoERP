import type { NamedResource } from '@/shared/types/common';

export type VehicleServiceJobStatus =
    | 'draft'
    | 'inspected'
    | 'in_progress'
    | 'completed'
    | 'invoiced'
    | 'partially_paid'
    | 'paid'
    | 'cancelled';

export type VehicleServiceJobType = 'full_service' | 'body_wash';

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
    type: VehicleServiceJobType;
    type_label?: string;
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
    status: VehicleServiceJobStatus;
    status_label?: string;
    odometer_reading?: string | null;
    next_service_mileage?: string | null;
    manual_job_card?: string | null;
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
    type: VehicleServiceJobType;
    customer_id: number;
    bill_to_customer_id?: number;
    vehicle_id: number;
    supervisor_employee_id?: number;
    supervisor_commission_type?: CommissionType;
    supervisor_commission_value?: string;
    odometer_reading?: string;
    next_service_mileage?: string;
    manual_job_card?: string;
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

export interface VehicleServiceInventoryIssuePayload {
    expected_version?: number;
    warehouse_id: number;
    warehouse_location_id: number;
    line_ids?: number[];
}

export interface VehicleServiceInventoryMovement {
    id: number;
    movement_number: string;
    source_line_id?: number | null;
    quantity: string;
    status: string;
}

export interface VehicleServicePaymentMethod {
    id: number | null;
    code?: string | null;
    name: string;
    method_type: string;
    requires_reference: boolean;
    requires_instrument_details: boolean;
}

export interface VehicleServicePaymentOptions {
    job_version: number;
    methods: VehicleServicePaymentMethod[];
}

export interface VehicleServicePaymentPayload {
    expected_version: number;
    invoice_id: number;
    payment_date: string;
    amount: string;
    payment_method_id: number;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    external_bank_name?: string;
    external_bank_branch?: string;
    instrument_number?: string;
    instrument_date?: string;
}

export interface VehicleServicePaymentCreated {
    id: number;
    row_version?: number;
    payment_number?: string | null;
    document_status?: string | null;
    posting_status?: string | null;
    allocation_status?: string | null;
    instrument_status?: string | null;
    total_amount?: string | null;
    allocated_amount?: string | null;
}

export interface PreparedVehicleServicePayment {
    paymentType: string;
    direction: string;
    paymentDate: string;
    referenceNumber?: string | null;
    lines: Array<{
        amount: string;
        paymentMethodId?: number | null;
        externalBankName?: string | null;
        externalBankBranch?: string | null;
        instrumentNumber?: string | null;
        instrumentDate?: string | null;
    }>;
    allocations: Array<{ invoiceId: number; allocatedAmount: string }>;
}

export interface VehicleServiceStatusHistory {
    id: number;
    old_status?: VehicleServiceJobStatus | null;
    new_status: VehicleServiceJobStatus;
    reason?: string | null;
    changed_by?: number | null;
    changed_at: string;
}

export interface VehicleServiceDocumentOptions {
    document_types: string[];
    mime_types: string[];
    max_size_bytes: number;
}

export interface VehicleServiceDocument {
    id: number;
    document_type: string;
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    description?: string | null;
    uploaded_by?: number | null;
    created_at?: string | null;
}
