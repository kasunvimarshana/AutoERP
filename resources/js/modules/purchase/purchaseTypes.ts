import type { NamedResource } from '@/shared/types/common';

export type PurchaseOrderStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'closed'
    | 'cancelled';

export type ReceiptProgressStatus = 'not_received' | 'partially_received' | 'received';
export type InvoiceProgressStatus = 'not_invoiced' | 'partially_invoiced' | 'invoiced';
export type ReturnProgressStatus = 'not_returned' | 'partially_returned' | 'returned';
export type AllocationProgressStatus = 'unallocated' | 'partially_allocated' | 'allocated';

export interface PurchaseActionPayload {
    expected_version: number;
}

export interface PurchaseCapabilityDetail {
    allowed: boolean;
    code?: string | null;
    reason?: string | null;
}

export type PurchaseCapabilityDetails<K extends string> = {
    details?: Partial<Record<K, PurchaseCapabilityDetail>>;
};

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
    recognition?: {
        cost_treatment?: string | null;
        tax_treatment?: string | null;
        final_treatment?: string | null;
    } | null;
    sort_order?: number;
    description?: string | null;
}

export interface PurchaseOrder {
    id: number;
    row_version: number;
    purchase_order_number?: string;
    purchase_order_date?: string;
    expected_delivery_date?: string | null;
    status?: PurchaseOrderStatus;
    workflow_status?: PurchaseOrderStatus;
    receipt_status?: ReceiptProgressStatus;
    invoice_status?: InvoiceProgressStatus;
    return_status?: ReturnProgressStatus;
    capabilities?: PurchaseCapabilityDetails<'can_edit' | 'can_submit' | 'can_approve' | 'can_receive' | 'can_invoice' | 'can_close' | 'can_force_close' | 'can_cancel' | 'can_delete'> & {
        can_edit?: boolean;
        can_submit?: boolean;
        can_approve?: boolean;
        can_receive?: boolean;
        can_invoice?: boolean;
        can_close?: boolean;
        can_force_close?: boolean;
        can_cancel?: boolean;
        can_delete?: boolean;
    };
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
    related_documents?: PurchaseRelatedDocuments;
    approved_at?: string | null;
    closed_at?: string | null;
}

export interface PurchaseRelatedDocument {
    id: number;
    type: string;
    number?: string | null;
    date?: string | null;
    status?: string | null;
    url?: string | null;
}

export interface PurchaseRelatedDocuments {
    goods_receipts: PurchaseRelatedDocument[];
    supplier_invoices: PurchaseRelatedDocument[];
    payments: PurchaseRelatedDocument[];
    returns: PurchaseRelatedDocument[];
    debit_notes: PurchaseRelatedDocument[];
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

export interface PurchaseOrderCreateContext {
    defaults: {
        purchase_order_date: string;
        expected_delivery_date?: string | null;
        currency_id?: number | null;
        currency?: NamedResource | null;
        currency_source?: string;
        exchange_rate: string;
        exchange_rate_source?: string;
        warehouse_id?: number | null;
        warehouse?: NamedResource | null;
        warehouse_source?: string;
        warehouse_location_id?: number | null;
        warehouse_location?: NamedResource | null;
        warehouse_location_source?: string;
    };
    exchange_rate_context: {
        base_currency_id?: number | null;
        selected_currency_id?: number | null;
        base_currency_uses_rate_one: boolean;
        foreign_currency_behavior: 'manual_required';
        override_allowed: boolean;
    };
    payment_terms: {
        options: Array<Record<string, unknown>>;
        default: unknown | null;
    };
    allowed_overrides: Record<string, boolean>;
}

export interface PurchaseSupplierContext {
    supplier: NamedResource;
    currency_id?: number | null;
    currency?: NamedResource | null;
    currency_source?: string;
    payment_term_id?: number | null;
    payment_terms_source?: string;
    tax_profile?: Record<string, unknown> | null;
    delivery_terms?: string | null;
    purchasing_contact?: NamedResource | null;
    supplier_item_mapping_context?: Record<string, unknown>;
    warning?: string;
}

export interface PurchaseItemContext {
    item: NamedResource;
    variants: NamedResource[];
    default_purchase_uom_id?: number | null;
    allowed_purchase_uoms: Array<{
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
    effective_date?: string | null;
    currency_id?: number | null;
    uom_id?: number | null;
    pricing_context_hash?: string | null;
    tax_defaults: Record<string, unknown>;
    description?: string | null;
    supplier_mapping?: Record<string, unknown> | null;
    eligible: boolean;
    block_reason?: string | null;
}

export interface PurchaseAdjustmentCatalogueEntry {
    type: string;
    default_name: string;
    allowed_effects: Array<'increase' | 'decrease'>;
    default_effect: 'increase' | 'decrease';
    allowed_calculation_types: Array<'fixed' | 'percentage'>;
    default_calculation_type: 'fixed' | 'percentage';
    allowed_calculation_bases: string[];
    default_calculation_base: string;
    cost_treatment?: string;
    tax_treatment?: string;
    recognition_label?: string;
}

export type GoodsReceiptStatus = 'draft' | 'posted' | 'reversed';
export type {
    ManualPurchaseReturnPayload,
    PurchaseReturn,
    PurchaseReturnLine,
    PurchaseReturnPayload,
    PurchasePostingResult,
    PurchaseReturnStatus,
    ReferencedPurchaseReturnPayload,
    ReturnableLine,
} from './types/purchaseReturnTypes';

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
    remaining_invoiceable_quantity?: string;
    remaining_returnable_quantity?: string;
    unit_price: string;
    line_subtotal?: string;
    line_total?: string;
    status?: string;
}

export interface GoodsReceipt {
    id: number;
    row_version: number;
    grn_number?: string;
    received_date?: string;
    status?: GoodsReceiptStatus | string;
    workflow_status?: GoodsReceiptStatus | string;
    invoice_status?: InvoiceProgressStatus;
    return_status?: ReturnProgressStatus;
    capabilities?: PurchaseCapabilityDetails<'can_post' | 'can_invoice' | 'can_return' | 'can_reverse' | 'read_only'> & {
        can_post?: boolean;
        can_invoice?: boolean;
        can_return?: boolean;
        can_reverse?: boolean;
        read_only?: boolean;
    };
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

export interface InvoiceableGoodsReceiptLine extends GoodsReceiptLine {
    remaining_invoiceable_quantity: string;
    can_invoice?: boolean;
    block_reason?: string | null;
}

export interface PurchaseDebitNote {
    id: number;
    row_version: number;
    debit_note_number?: string;
    debit_note_date?: string;
    status?: string;
    allocation_status?: AllocationProgressStatus;
    supplier?: NamedResource | null;
    supplier_id?: number | null;
    purchase_return_id?: number | null;
    purchase_return?: { id: number; return_number?: string; status?: string } | null;
    source_type?: string | null;
    source_id?: number | null;
    source?: SourceSummary | null;
    capabilities?: PurchaseCapabilityDetails<'can_approve' | 'can_post' | 'can_allocate' | 'read_only'> & {
        can_approve?: boolean;
        can_post?: boolean;
        can_allocate?: boolean;
        read_only?: boolean;
    };
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
}

export interface PurchaseDebitNoteAllocationPayload {
    invoice_id: number;
    amount: string;
    expected_version: number;
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

export interface PurchaseInvoicePreviewResult {
    subtotal: string;
    discount_total: string;
    tax_total: string;
    charge_total: string;
    adjustment_total: string;
    grand_total: string;
    header_increase_total: string;
    header_decrease_total: string;
    line_totals: string[];
}

export interface PurchasePaymentCreatePayload {
    payment_date: string;
    amount: string;
    supplier_type?: string;
    supplier_id?: number;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    lines?: Array<{
        amount: string;
        payment_method_id?: number;
        reference?: string;
        instrument_direction?: 'received' | 'issued';
        external_bank_name?: string;
        external_bank_branch?: string;
        instrument_number?: string;
        instrument_date?: string;
        notes?: string;
    }>;
    allocations?: Array<{
        invoice_id: number;
        allocated_amount: string;
        allocation_date?: string;
    }>;
}

export type PurchasePaymentPreparePayload = PurchasePaymentCreatePayload;

export interface PurchasePaymentPreview {
    tenant_id: number;
    organization_unit_id?: number | null;
    payment_date: string;
    amount: string;
    line_total: string;
    allocation_total: string;
    unapplied_amount: string;
    supplier_type: string;
    supplier_id: number;
    currency_id?: number | null;
    exchange_rate: string;
    reference_number?: string | null;
    lines: Array<{
        amount: string;
        payment_method_id?: number | null;
        reference_number?: string | null;
    }>;
    allocations: Array<{
        invoice_id: number;
        invoice_number?: string | null;
        invoice_total: string;
        invoice_balance_before: string;
        allocated_amount: string;
        invoice_balance_after: string;
        allocation_date: string;
        allocation_method: string;
    }>;
}
