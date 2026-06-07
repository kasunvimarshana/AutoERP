import type { NamedResource } from '@/shared/types/common';

export type PurchaseOrderStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'partially_received'
    | 'received'
    | 'partially_invoiced'
    | 'invoiced'
    | 'partially_returned'
    | 'returned'
    | 'closed'
    | 'cancelled';

export interface PurchaseOrderLine {
    id?: number;
    line_number?: number;
    item_id?: number | null;
    item?: NamedResource | null;
    item_variant_id?: number | null;
    item_variant?: NamedResource | null;
    description?: string | null;
    uom_id?: number | null;
    uom?: NamedResource | null;
    ordered_quantity: string;
    received_quantity?: string;
    invoiced_quantity?: string;
    returned_quantity?: string;
    cancelled_quantity?: string;
    remaining_quantity?: string;
    remaining_receivable_quantity?: string;
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

export interface PurchaseHeaderAdjustment {
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

export interface PurchaseOrder {
    id: number;
    purchase_order_number?: string;
    purchase_order_date?: string;
    expected_delivery_date?: string | null;
    status?: PurchaseOrderStatus;
    supplier_id?: number | null;
    supplier?: NamedResource | null;
    warehouse_id?: number | null;
    warehouse?: NamedResource | null;
    warehouse_location_id?: number | null;
    warehouse_location?: NamedResource | null;
    currency_id?: number | null;
    currency?: NamedResource | null;
    exchange_rate?: string;
    subtotal?: string;
    discount_total?: string;
    tax_total?: string;
    charge_total?: string;
    adjustment_total?: string;
    grand_total?: string;
    received_quantity?: string;
    invoiced_quantity?: string;
    returned_quantity?: string;
    notes?: string | null;
    lines?: PurchaseOrderLine[];
    adjustments?: PurchaseHeaderAdjustment[];
    approved_at?: string | null;
    closed_at?: string | null;
}

export interface PurchaseOrderPayload {
    purchase_order_number?: string;
    purchase_order_date: string;
    supplier_type?: string;
    supplier_id: number;
    warehouse_id: number;
    warehouse_location_id?: number;
    expected_delivery_date?: string;
    currency_id?: number;
    exchange_rate?: string;
    notes?: string;
    lines: Array<{
        item_id: number;
        item_variant_id?: number;
        description?: string;
        uom_id: number;
        ordered_quantity: string;
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
        calculation_type?: 'fixed' | 'percentage';
        calculation_base?: string;
        rate?: string;
        amount: string;
        allocation_method?: string;
        is_allocatable?: boolean;
        sort_order?: number;
        description?: string;
    }>;
}

export type GoodsReceiptStatus = 'draft' | 'posted' | 'reversed' | 'partially_invoiced' | 'invoiced' | 'partially_returned' | 'returned' | 'cancelled';
export type PurchaseReturnStatus = 'draft' | 'approved' | 'posted' | 'cancelled' | 'reversed';

export interface SourceSummary {
    type?: string | null;
    id?: number | null;
    number?: string | null;
    date?: string | null;
}

export interface GoodsReceiptLine {
    id?: number;
    purchase_order_line_id?: number | null;
    item?: NamedResource | null;
    item_id?: number | null;
    item_variant?: NamedResource | null;
    uom?: NamedResource | null;
    received_quantity: string;
    accepted_quantity: string;
    rejected_quantity?: string;
    invoiced_quantity?: string;
    returned_quantity?: string;
    remaining_quantity?: string;
    unit_price: string;
    line_subtotal?: string;
    line_total?: string;
    status?: string;
}

export interface GoodsReceipt {
    id: number;
    grn_number?: string;
    received_date?: string;
    status?: GoodsReceiptStatus | string;
    purchase_order?: NamedResource & { purchase_order_number?: string; status?: string } | null;
    supplier?: NamedResource | null;
    warehouse?: NamedResource | null;
    warehouse_location?: NamedResource | null;
    subtotal?: string;
    discount_total?: string;
    tax_total?: string;
    charge_total?: string;
    grand_total?: string;
    notes?: string | null;
    posted_at?: string | null;
    lines?: GoodsReceiptLine[];
    adjustments?: PurchaseHeaderAdjustment[];
}

export interface GoodsReceiptPayload {
    received_date: string;
    warehouse_id: number;
    purchase_order_id?: number;
    warehouse_location_id?: number;
    supplier_type?: string;
    supplier_id?: number;
    notes?: string;
    lines: Array<{
        item_id: number;
        received_quantity: string;
        accepted_quantity: string;
        rejected_quantity?: string;
        unit_price: string;
        purchase_order_line_id?: number;
        item_variant_id?: number;
        uom_id?: number;
        ordered_uom_id?: number;
        ordered_quantity?: string;
    }>;
}

export interface ReturnableLine {
    id: number;
    source_line_type: string;
    source_line_id: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    returnable_quantity: string;
    unit_price: string;
}

export interface PurchaseReturnLine {
    id?: number;
    source_line_type: string;
    source_line_id: number;
    item?: NamedResource | null;
    item_id?: number | null;
    item_variant?: NamedResource | null;
    uom?: NamedResource | null;
    returned_quantity: string;
    source_quantity?: string;
    previously_returned_quantity?: string;
    remaining_quantity?: string;
    unit_price: string;
    cost_basis?: string | null;
    line_total?: string;
    reason?: string | null;
}

export interface PurchaseReturn {
    id: number;
    return_number?: string;
    return_date?: string;
    return_type?: 'referenced' | 'manual_supplier_return' | string;
    source_type?: string | null;
    source_id?: number | null;
    source?: SourceSummary | null;
    status?: PurchaseReturnStatus | string;
    supplier?: NamedResource | null;
    warehouse?: NamedResource | null;
    warehouse_location?: NamedResource | null;
    approval_required?: boolean;
    affects_supplier_balance?: boolean;
    cost_basis?: string | null;
    reason?: string | null;
    subtotal?: string;
    adjustment_return_total?: string;
    grand_total?: string;
    debit_note_id?: number | null;
    debit_note?: { id: number; debit_note_number?: string; status?: string } | null;
    lines?: PurchaseReturnLine[];
    adjustment_allocations?: Array<Record<string, unknown>>;
}

export interface PurchaseReturnPayload {
    return_date: string;
    warehouse_id: number;
    warehouse_location_id?: number;
    supplier_type?: string;
    supplier_id?: number;
    reason?: string;
    return_type?: 'referenced' | 'manual_supplier_return';
    source_type?: string;
    source_id?: number;
    approval_required?: boolean;
    affects_supplier_balance?: boolean;
    cost_basis?: string;
    lines: Array<{
        source_line_type: string;
        source_line_id: number;
        returned_quantity: string;
        item_id?: number;
        item_variant_id?: number;
        uom_id?: number;
        unit_price?: string;
        cost_basis?: string;
        reason?: string;
    }>;
}

export interface PurchaseDebitNote {
    id: number;
    debit_note_number?: string;
    debit_note_date?: string;
    status?: string;
    supplier?: NamedResource | null;
    supplier_id?: number | null;
    purchase_return_id?: number | null;
    purchase_return?: { id: number; return_number?: string; status?: string } | null;
    source_type?: string | null;
    source_id?: number | null;
    source?: SourceSummary | null;
    amount?: string;
    allocated_amount?: string;
    remaining_amount?: string;
    reason?: string;
}

export interface PurchaseDebitNotePayload {
    debit_note_date: string;
    debit_note_number?: string;
    supplier_type?: string;
    supplier_id: number;
    amount: string;
    reason: string;
    source_type?: string;
    source_id?: number;
}

export interface PurchaseInvoicePayload {
    invoice_date: string;
    invoice_number?: string;
    supplier_type?: string;
    supplier_id?: number;
    due_date?: string;
    currency_id?: number;
    exchange_rate?: string;
    notes?: string;
    sources: Array<{
        source_type: 'goods_receipt_note' | 'purchase_order';
        source_id: number;
        line_quantities?: Record<number, string>;
    }>;
}

export interface PurchasePaymentPreparePayload {
    payment_date: string;
    amount: string;
    supplier_type?: string;
    supplier_id?: number;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    allocations?: Array<{
        invoice_id: number;
        allocated_amount: string;
        allocation_date?: string;
    }>;
}

export interface InventoryAdjustmentRequestPayload {
    adjustment_date: string;
    adjustment_type: 'increase' | 'decrease' | 'recount' | 'damage' | 'expiry' | 'opening_balance';
    warehouse_id: number;
    warehouse_location_id?: number;
    reason: string;
    notes?: string;
    lines: Array<{
        item_id: number;
        item_variant_id?: number;
        system_quantity: string;
        counted_quantity: string;
        adjustment_quantity: string;
        unit_cost?: string;
        reason?: string;
    }>;
}
