export type VehicleJobCardWorkflowStatus = 'draft' | 'scheduled' | 'in_progress' | 'awaiting_parts' | 'quality_check' | 'completed' | 'cancelled';
export type VehicleJobCardServiceType = 'maintenance' | 'repair' | 'inspection' | 'accident' | 'other';
export type VehicleJobCardPaymentStatus = 'unpaid' | 'partial_paid' | 'paid';
export type VehicleJobCardDiscountType = 'none' | 'percentage' | 'fixed';

export type VehicleJobCardTaskPayload = {
    task_name: string;
    task_status?: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    estimated_hours?: number | null;
    actual_hours?: number | null;
    labor_rate?: number | null;
    labor_cost?: number | null;
    notes?: string | null;
};

export type VehicleJobCardPartPayload = {
    service_task_id?: number | null;
    product_id?: number | null;
    uom_id?: number | null;
    quantity?: number | null;
    unit_cost?: number | null;
    line_total?: number | null;
    description?: string | null;
};

export type JobCardOrderLine = {
    id: string;
    product_id?: number | null;
    product_name: string;
    quantity: number;
    net_unit_price: number;
    discount: number;
    sub_total: number;
};

export type JobCardSubItem = {
    id: string;
    sub_item_id: string;
    sub_item_name: string;
    price: number;
};

export type JobCardAssignedSubItem = {
    id: string;
    employee_name: string;
    service_item: string;
    sub_item: string;
    sub_item_id: string;
    incentive_amount: number;
};

export type VehicleJobCardMetadata = {
    mileage?: number | null;
    monthly_mileage?: number | null;
    next_service_date?: string | null;
    order_discount_type?: VehicleJobCardDiscountType;
    order_discount_value?: number | null;
    payment_status?: VehicleJobCardPaymentStatus;
    service_item?: string | null;
    supervisor_user_id?: number | null;
    supervisor_employee_id?: number | null;
    supervisor_name?: string | null;
    order_lines?: JobCardOrderLine[];
    sub_items?: JobCardSubItem[];
    assigned_sub_items?: JobCardAssignedSubItem[];
    [key: string]: unknown;
};

export type VehicleJobCardRecord = {
    id: number;
    tenant_id: number;
    vehicle_id: number;
    customer_id?: number | null;
    assigned_mechanic_id?: number | null;
    job_card_no: string;
    workflow_status: VehicleJobCardWorkflowStatus;
    service_type?: VehicleJobCardServiceType | null;
    scheduled_at?: string | null;
    notes?: string | null;
    tasks?: VehicleJobCardTaskPayload[] | null;
    parts?: VehicleJobCardPartPayload[] | null;
    labor_cost_total?: number | string | null;
    parts_cost_total?: number | string | null;
    subtotal?: number | string | null;
    tax_amount?: number | string | null;
    grand_total?: number | string | null;
    metadata?: VehicleJobCardMetadata | null;
    created_at?: string;
    updated_at?: string;
};

export type VehicleJobCardListFilters = {
    tenant_id: number;
    vehicle_id: number;
    page?: number;
    per_page?: number;
    sort?: string;
};

export type VehicleJobCardPayload = {
    tenant_id: number;
    vehicle_id: number;
    customer_id?: number | null;
    assigned_mechanic_id?: number | null;
    job_card_no: string;
    workflow_status?: VehicleJobCardWorkflowStatus;
    service_type?: VehicleJobCardServiceType | null;
    scheduled_at?: string | null;
    notes?: string | null;
    tasks?: VehicleJobCardTaskPayload[] | null;
    parts?: VehicleJobCardPartPayload[] | null;
    labor_cost_total?: number | null;
    parts_cost_total?: number | null;
    subtotal?: number | null;
    tax_amount?: number | null;
    grand_total?: number | null;
    metadata?: VehicleJobCardMetadata | null;
};
