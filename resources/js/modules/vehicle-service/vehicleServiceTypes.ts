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

export type VehicleServiceLineSourceType =
    | 'inventory_item'
    | 'external_item'
    | 'service_item'
    | 'labour_item'
    | 'combo_parent'
    | 'combo_child';

export type CommissionType = 'none' | 'fixed' | 'percentage';

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
    status: string;
}

export interface VehicleServicePaymentLink {
    id: number;
    payment_id: number;
    payment_number?: string | null;
    invoice_id?: number | null;
    allocated_amount: string;
    status: string;
}

export interface VehicleServiceJob {
    id: number;
    job_number: string;
    job_date: string;
    expected_delivery_date?: string | null;
    customer_id: number;
    customer?: NamedResource | null;
    vehicle_id: number;
    vehicle?: NamedResource | null;
    supervisor_employee_id?: number | null;
    supervisor?: NamedResource | null;
    supervisor_commission_type: CommissionType;
    supervisor_commission_value: string;
    supervisor_commission_amount: string;
    status: VehicleServiceJobStatus;
    status_label?: string;
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
    job_date: string;
    expected_delivery_date?: string;
    customer_id: number;
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
    line_source_type: VehicleServiceLineSourceType;
    item_id?: number;
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

export interface VehicleServiceStatusHistory {
    id: number;
    old_status?: VehicleServiceJobStatus | null;
    new_status: VehicleServiceJobStatus;
    reason?: string | null;
    changed_by?: number | null;
    changed_at: string;
}

export interface VehicleServiceDocument {
    id: number;
    document_type: string;
    file_path?: string | null;
    description?: string | null;
    uploaded_by?: number | null;
    created_at?: string | null;
}
