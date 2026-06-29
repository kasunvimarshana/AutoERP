import type { NamedResource } from '@/shared/types/common';

export type SalesQuotationStatus = 'draft' | 'sent' | 'accepted' | 'rejected' | 'expired' | 'converted' | 'cancelled';
export type SalesOrderStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'closed'
    | 'cancelled';
export type SalesProgressStatus = 'none' | 'partial' | 'complete';
export type SalesPaymentProgressStatus = 'unpaid' | 'partial' | 'paid';
export type SalesDeliveryStatus = 'draft' | 'posted' | 'cancelled' | 'reversed';
export type SalesAllocationStatus = 'active' | 'partially_released' | 'released' | 'issued' | 'cancelled';
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

export interface SalesItemContext {
    item: NamedResource;
    variants: NamedResource[];
    default_sales_uom_id?: number | null;
    allowed_sales_uoms: Array<{
        id: number;
        item_unit_id: number;
        unit_role: string;
        is_default: boolean;
        conversion_factor: string;
        quantity_precision: number;
        uom: NamedResource | null;
    }>;
    quantity_precision: number;
    unit_price?: string | null;
    pricing_mode?: 'auto' | 'manual';
    price_source: string;
    price_source_id?: number | null;
    price_source_label: string;
    currency_id?: number | null;
    uom_id?: number | null;
    pricing_context_hash?: string | null;
    tax_defaults: Record<string, unknown>;
    description?: string | null;
    eligible: boolean;
    block_reason?: string | null;
}

export interface SalesAdjustmentCatalogueEntry {
    type: string;
    default_name: string;
    allowed_effects: Array<'increase' | 'decrease'>;
    default_effect: 'increase' | 'decrease';
    allowed_calculation_types: Array<'fixed' | 'percentage'>;
    default_calculation_type: 'fixed' | 'percentage';
    allowed_calculation_bases: string[];
    default_calculation_base: string;
    revenue_treatment?: string;
    tax_treatment?: string;
    finance_mapping_label?: string;
    override_allowed: boolean;
}

export interface SalesCapabilityDetail {
    allowed: boolean;
    code: string | null;
    reason: string | null;
}

export interface SalesCapabilities {
    details?: Record<string, SalesCapabilityDetail>;
    can_edit?: boolean;
    can_submit?: boolean;
    can_approve?: boolean;
    can_allocate?: boolean;
    can_release_allocation?: boolean;
    can_deliver?: boolean;
    can_invoice?: boolean;
    can_receive_payment?: boolean;
    can_return?: boolean;
    can_cancel?: boolean;
    can_close?: boolean;
    can_delete?: boolean;
    can_post?: boolean;
    can_reverse?: boolean;
    read_only?: boolean;
}

export interface SalesRelatedDocument {
    type: string;
    id: number;
    number?: string | null;
    status?: string | null;
}

export interface SalesOrderProgress {
    allocation: SalesProgressStatus;
    delivery: SalesProgressStatus;
    invoice: SalesProgressStatus;
    payment: SalesPaymentProgressStatus;
    return: SalesProgressStatus;
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
    workflow_status?: SalesOrderStatus;
    progress?: SalesOrderProgress;
    capabilities?: SalesCapabilities;
    related_documents?: SalesRelatedDocument[];
}

export interface SalesAllocationLine {
    id: number;
    sales_order_line_id: number;
    line_number?: number;
    item?: NamedResource | null;
    variant?: NamedResource | null;
    uom?: NamedResource | null;
    requested_quantity: string;
    allocated_quantity: string;
    released_quantity: string;
    issued_quantity: string;
    inventory_allocation_id?: number | null;
    status?: SalesAllocationStatus;
}

export interface SalesAllocation {
    id: number;
    allocation_number?: string;
    allocation_date?: string;
    status?: SalesAllocationStatus;
    sales_order?: NamedResource | null;
    customer?: NamedResource | null;
    warehouse?: NamedResource | null;
    warehouse_location?: NamedResource | null;
    notes?: string | null;
    released_at?: string | null;
    lines?: SalesAllocationLine[];
}

export interface SalesAllocationPayload {
    allocation_date: string;
    allocation_number?: string;
    sales_order_id: number;
    warehouse_id: number;
    warehouse_location_id?: number;
    notes?: string;
    lines: Array<{
        sales_order_line_id: number;
        quantity: string;
    }>;
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
    progress?: {
        invoice: SalesProgressStatus;
        return: SalesProgressStatus;
    };
    capabilities?: SalesCapabilities;
    related_documents?: SalesRelatedDocument[];
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

export interface FastSalesOptionResource {
    id: number;
    code?: string | null;
    name?: string | null;
    symbol?: string | null;
    is_default?: boolean;
    is_cash_account?: boolean;
    is_bank_account?: boolean;
    method_type?: string;
    requires_reference?: boolean;
    requires_instrument_details?: boolean;
}

export interface FastSalesContext {
    defaults: {
        transaction_date: string;
        exchange_rate: string;
    };
    endpoints: Record<string, string>;
    warehouses: FastSalesOptionResource[];
    currencies: FastSalesOptionResource[];
    payment_methods: FastSalesOptionResource[];
    tax_groups: FastSalesOptionResource[];
}

export interface FastSalesPayload {
    idempotency_key?: string;
    customer_id: number;
    customer_reference?: string;
    transaction_date: string;
    warehouse_id?: number;
    warehouse_location_id?: number;
    currency_id?: number;
    exchange_rate?: string;
    payment_terms?: string;
    due_date?: string;
    notes?: string;
    options: {
        create_sales_order_only: boolean;
        deliver_items_now: boolean;
        create_customer_invoice_now: boolean;
        record_customer_receipt_now: boolean;
    };
    lines: Array<{
        item_id: number;
        item_variant_id?: number;
        description?: string;
        uom_id?: number;
        quantity: string;
        unit_price?: string;
        discount_amount?: string;
        tax_group_id?: number;
    }>;
    payment?: {
        amount?: string;
        payment_method_id?: number;
        reference?: string;
        cheque_number?: string;
        cheque_date?: string;
        card_reference?: string;
        instrument_number?: string;
        instrument_date?: string;
        external_bank_name?: string;
        external_bank_branch?: string;
        lines?: Array<{
            amount: string;
            payment_method_id?: number;
            reference?: string;
            instrument_number?: string;
            instrument_date?: string;
            external_bank_name?: string;
            external_bank_branch?: string;
        }>;
    };
}

export interface FastSalesDocumentReference {
    id: number;
    number: string;
    status?: string;
    url: string;
    total_debit?: string;
    total_credit?: string;
}

export interface FastSalesLinePreview {
    line_number: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    description?: string;
    is_stock: boolean;
    quantity: string;
    base_quantity?: string;
    available_quantity?: string | null;
    available_base_quantity?: string | null;
    unit_price: string;
    discount_amount: string;
    tax_amount: string;
    withholding_amount: string;
    line_total: string;
}

export interface FastSalesResult {
    customer_reference?: string;
    mode: string;
    options: FastSalesPayload['options'];
    customer?: NamedResource | null;
    summary: {
        subtotal: string;
        discount_total: string;
        tax_total: string;
        withholding_total: string;
        grand_total: string;
        received_total: string;
        balance_due: string;
        revenue_total?: string;
        stock_revenue_total?: string;
        non_stock_revenue_total?: string;
    };
    lines: FastSalesLinePreview[];
    documents: {
        sales_order?: FastSalesDocumentReference | null;
        goods_delivery?: FastSalesDocumentReference | null;
        inventory_transaction?: FastSalesDocumentReference | null;
        inventory_transactions?: FastSalesDocumentReference[];
        customer_invoice?: FastSalesDocumentReference | null;
        customer_receipt?: FastSalesDocumentReference | null;
        finance_posting?: FastSalesDocumentReference | null;
        finance_postings?: FastSalesDocumentReference[];
    };
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
    capabilities?: SalesCapabilities;
    related_documents?: SalesRelatedDocument[];
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
    capabilities?: SalesCapabilities;
    related_documents?: SalesRelatedDocument[];
}

export interface SalesCreditNotePayload {
    credit_note_date: string;
    customer_id: number;
    amount: string;
    reason: string;
    sales_return_id?: number;
    credit_note_number?: string;
}

export interface SalesCreditNoteAllocationPayload {
    invoice_id: number;
    amount: string;
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
