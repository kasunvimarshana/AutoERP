import type { NamedResource } from '@/shared/types/common';

export type SalesQuotationStatus = 'draft' | 'sent' | 'accepted' | 'rejected' | 'expired' | 'converted' | 'cancelled';
export type SalesOrderStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'partially_allocated'
    | 'allocated'
    | 'partially_delivered'
    | 'delivered'
    | 'partially_invoiced'
    | 'invoiced'
    | 'partially_returned'
    | 'returned'
    | 'closed'
    | 'cancelled';
export type SalesDeliveryStatus = 'draft' | 'posted' | 'partially_returned' | 'returned' | 'partially_invoiced' | 'invoiced' | 'cancelled' | 'reversed';
export type SalesReturnStatus = 'draft' | 'approved' | 'posted' | 'cancelled' | 'reversed';
export type SalesReturnType =
    | 'referenced_customer_return'
    | 'manual_customer_return'
    | 'credit_note_only'
    | 'inventory_adjustment_only'
    | 'warranty_replacement'
    | 'exchange_return'
    | 'opening_imported_return';

export interface SalesHeaderAdjustment {
    id?: number;
    name: string;
    adjustment_type: string;
    effect: 'increase' | 'decrease';
    calculation_type: 'fixed' | 'percentage';
    calculation_base?: 'subtotal' | 'subtotal_after_line_discount' | 'subtotal_after_line_adjustments';
    rate: string;
    amount: string;
    allocated_amount?: string;
    returned_amount?: string;
    remaining_amount?: string;
    allocation_method: string;
    is_allocatable?: boolean;
    sort_order?: number;
    description?: string | null;
}

export interface SalesLine {
    id?: number;
    line_number?: number;
    item_id?: number;
    item?: NamedResource | null;
    item_variant?: NamedResource | null;
    description?: string | null;
    uom_id?: number;
    uom?: NamedResource | null;
    quantity?: string;
    ordered_quantity?: string;
    base_quantity?: string;
    allocated_quantity?: string;
    delivered_quantity?: string;
    invoiced_quantity?: string;
    returned_quantity?: string;
    remaining_allocatable_quantity?: string;
    remaining_deliverable_quantity?: string;
    remaining_invoiceable_quantity?: string;
    remaining_returnable_quantity?: string;
    unit_price: string;
    line_subtotal?: string;
    discount_calculation_type?: 'fixed' | 'percentage';
    discount_rate?: string;
    discount_amount: string;
    tax_calculation_type?: 'fixed' | 'percentage';
    tax_rate?: string;
    tax_amount: string;
    charge_calculation_type?: 'fixed' | 'percentage';
    charge_rate?: string;
    charge_amount: string;
    line_total?: string;
    status?: string;
}

export interface SalesQuotation {
    id: number;
    quotation_number?: string;
    quotation_date?: string;
    valid_until?: string | null;
    status?: SalesQuotationStatus;
    customer_id?: number;
    customer?: NamedResource | null;
    currency_id?: number | null;
    currency?: NamedResource | null;
    exchange_rate?: string;
    subtotal?: string;
    line_discount_total?: string;
    line_tax_total?: string;
    line_charge_total?: string;
    header_increase_total?: string;
    header_decrease_total?: string;
    grand_total?: string;
    notes?: string | null;
    lines?: SalesLine[];
    adjustments?: SalesHeaderAdjustment[];
}

export interface SalesOrder extends Omit<SalesQuotation, 'quotation_number' | 'quotation_date' | 'valid_until' | 'status'> {
    sales_order_number?: string;
    sales_order_date?: string;
    expected_delivery_date?: string | null;
    status?: SalesOrderStatus;
    quotation?: NamedResource | null;
    warehouse_id?: number | null;
    warehouse?: NamedResource | null;
    warehouse_location_id?: number | null;
    warehouse_location?: NamedResource | null;
    allocated_total?: string;
    delivered_total?: string;
    invoiced_total?: string;
    returned_total?: string;
}

export interface SalesDocumentPayload {
    quotation_date?: string;
    valid_until?: string;
    sales_order_date?: string;
    expected_delivery_date?: string;
    customer_id: number;
    warehouse_id?: number;
    warehouse_location_id?: number;
    currency_id?: number;
    exchange_rate?: string;
    notes?: string;
    lines: Array<{
        item_id: number;
        item_variant_id?: number;
        description?: string;
        uom_id: number;
        quantity: string;
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
    }>;
    adjustments?: Array<{
        name: string;
        adjustment_type: string;
        effect: 'increase' | 'decrease';
        calculation_type: 'fixed' | 'percentage';
        calculation_base: string;
        rate: string;
        amount: string;
        allocation_method: string;
        is_allocatable: boolean;
        sort_order: number;
        description?: string;
    }>;
}

export interface SalesDeliveryLine {
    id: number;
    sales_order_line_id?: number | null;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    ordered_quantity: string;
    delivered_quantity: string;
    invoiced_quantity: string;
    returned_quantity: string;
    remaining_quantity: string;
    unit_price: string;
    line_total: string;
    status?: string;
}

export interface SalesDelivery {
    id: number;
    delivery_number?: string;
    delivery_date?: string;
    status?: SalesDeliveryStatus;
    sales_order?: NamedResource | null;
    customer?: NamedResource | null;
    warehouse?: NamedResource | null;
    warehouse_location?: NamedResource | null;
    notes?: string | null;
    lines?: SalesDeliveryLine[];
}

export interface SalesDeliveryPayload {
    delivery_date: string;
    sales_order_id?: number;
    customer_id: number;
    warehouse_id: number;
    warehouse_location_id?: number;
    notes?: string;
    lines: Array<{
        sales_order_line_id?: number;
        item_id: number;
        item_variant_id?: number;
        description?: string;
        uom_id?: number;
        ordered_quantity?: string;
        delivered_quantity: string;
        unit_price: string;
    }>;
}

export interface SalesInvoicePayload {
    invoice_date: string;
    invoice_number?: string;
    customer_id?: number;
    due_date?: string;
    currency_id?: number;
    exchange_rate?: string;
    notes?: string;
    sources: Array<{
        source_type: 'sales_delivery' | 'sales_order';
        source_id: number;
        line_quantities?: Record<number, string>;
    }>;
}

export interface SalesPaymentPayload {
    payment_date: string;
    amount: string;
    customer_id?: number;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    allocations?: Array<{ invoice_id: number; allocated_amount: string; allocation_date?: string }>;
}

export interface SalesReturnLine {
    id?: number;
    source_line_type?: string | null;
    source_line_id?: number | null;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    returned_quantity: string;
    source_quantity?: string;
    previously_returned_quantity?: string;
    remaining_quantity?: string;
    unit_price: string;
    line_total?: string;
    condition_status?: 'sellable' | 'damaged' | 'quarantine' | 'scrap';
    reason?: string | null;
}

export interface SalesReturn {
    id: number;
    return_number?: string;
    return_date?: string;
    return_type?: SalesReturnType;
    status?: SalesReturnStatus;
    customer?: NamedResource | null;
    warehouse?: NamedResource | null;
    warehouse_location?: NamedResource | null;
    replacement_sales_order?: NamedResource | null;
    affects_inventory?: boolean;
    affects_customer_balance?: boolean;
    approval_required?: boolean;
    reason?: string | null;
    subtotal?: string;
    adjustment_return_total?: string;
    grand_total?: string;
    credit_note_id?: number | null;
    credit_note?: NamedResource | null;
    lines?: SalesReturnLine[];
}

export interface SalesReturnPayload {
    return_date: string;
    customer_id: number;
    return_type: SalesReturnType;
    warehouse_id?: number;
    warehouse_location_id?: number;
    replacement_sales_order_id?: number;
    approval_required?: boolean;
    cost_basis?: string;
    reason?: string;
    audit_metadata?: Record<string, unknown>;
    lines: Array<{
        source_line_type?: 'sales_delivery_line' | 'invoice_line' | 'sales_order_line';
        source_line_id?: number;
        item_id?: number;
        item_variant_id?: number;
        uom_id?: number;
        returned_quantity: string;
        unit_price?: string;
        cost_basis?: string;
        condition_status?: 'sellable' | 'damaged' | 'quarantine' | 'scrap';
        reason?: string;
    }>;
}

export interface SalesCreditNote {
    id: number;
    credit_note_number?: string;
    credit_note_date?: string;
    status?: string;
    customer?: NamedResource | null;
    sales_return?: NamedResource | null;
    amount?: string;
    allocated_amount?: string;
    remaining_amount?: string;
    reason?: string | null;
}

export interface SalesCreditNotePayload {
    credit_note_date: string;
    customer_id: number;
    amount: string;
    reason: string;
    sales_return_id?: number;
    credit_note_number?: string;
}

export interface SalesLineSummary {
    id: number;
    sales_order_line_id?: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    unit_price: string;
    remaining_quantity?: string;
    remaining_allocatable_quantity?: string;
    remaining_deliverable_quantity?: string;
    remaining_invoiceable_quantity?: string;
}

export interface ReturnableSalesLine {
    id: number;
    source_line_type: 'sales_delivery_line';
    source_line_id: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    returnable_quantity: string;
    unit_price: string;
}

export interface SalesInvoicePreview {
    subtotal?: string;
    discountTotal?: string;
    taxTotal?: string;
    chargeTotal?: string;
    adjustmentTotal?: string;
    grandTotal?: string;
}
