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
    finance_posting_profile_id?: number | null;
    finance_account_id?: number | null;
    cost_treatment?: string | null;
    tax_treatment?: string | null;
    mapping_source?: string | null;
    override_reason?: string | null;
    finance_mapping?: {
        posting_profile_id?: number | null;
        account_id?: number | null;
        cost_treatment?: string | null;
        tax_treatment?: string | null;
        source?: string | null;
    } | null;
    sort_order?: number;
    description?: string | null;
}

export interface PurchaseOrder {
    id: number;
    purchase_order_number?: string;
    purchase_order_date?: string;
    expected_delivery_date?: string | null;
    status?: PurchaseOrderStatus;
    workflow_status?: PurchaseOrderStatus;
    receipt_status?: string;
    invoice_status?: string;
    return_status?: string;
    capabilities?: {
        can_edit?: boolean;
        can_submit?: boolean;
        can_approve?: boolean;
        can_receive?: boolean;
        can_invoice?: boolean;
        can_return?: boolean;
        can_close?: boolean;
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
        finance_posting_profile_id?: number;
        finance_account_id?: number;
        cost_treatment?: string;
        tax_treatment?: string;
        mapping_source?: 'catalogue' | 'override';
        override_reason?: string;
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
    price_source: string;
    price_source_label: string;
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
    finance_mapping_label?: string;
    override_allowed: boolean;
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
    remaining_invoiceable_quantity?: string;
    remaining_returnable_quantity?: string;
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

export interface InvoiceableGoodsReceiptLine extends GoodsReceiptLine {
    remaining_invoiceable_quantity: string;
    can_invoice?: boolean;
    block_reason?: string | null;
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
    capabilities?: {
        can_edit?: boolean;
        can_approve?: boolean;
        can_post?: boolean;
        can_cancel?: boolean;
        can_reverse?: boolean;
        read_only?: boolean;
    };
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

export interface PurchaseDebitNoteAllocationPayload {
    invoice_id: number;
    amount: string;
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

export interface FastPurchaseOptionResource {
    id: number;
    code?: string | null;
    name?: string | null;
    symbol?: string | null;
    is_default?: boolean;
    is_cash_account?: boolean;
    is_bank_account?: boolean;
    method_type?: string;
    requires_reference?: boolean;
    requires_bank_account?: boolean;
}

export interface FastPurchaseContext {
    defaults: {
        purchase_date: string;
        exchange_rate: string;
    };
    endpoints: Record<string, string>;
    warehouses: FastPurchaseOptionResource[];
    currencies: FastPurchaseOptionResource[];
    payment_methods: FastPurchaseOptionResource[];
    payment_accounts: FastPurchaseOptionResource[];
    tax_groups: FastPurchaseOptionResource[];
}

export interface FastPurchasePayload {
    supplier_id: number;
    supplier_reference?: string;
    purchase_date: string;
    warehouse_id?: number;
    warehouse_location_id?: number;
    currency_id?: number;
    exchange_rate?: string;
    payment_terms?: string;
    due_date?: string;
    notes?: string;
    options: {
        receive_stock_now: boolean;
        create_supplier_invoice_now: boolean;
        record_payment_now: boolean;
    };
    lines: Array<{
        item_id: number;
        item_variant_id?: number;
        description?: string;
        uom_id?: number;
        quantity: string;
        unit_cost?: string;
        discount_amount?: string;
        tax_group_id?: number;
    }>;
    payment?: {
        amount?: string;
        payment_method_id?: number;
        source_account_id?: number;
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
            source_account_id?: number;
            reference?: string;
            instrument_number?: string;
            instrument_date?: string;
            external_bank_name?: string;
            external_bank_branch?: string;
        }>;
    };
}

export interface FastPurchaseDocumentReference {
    id: number;
    number: string;
    status?: string;
    url: string;
    total_debit?: string;
    total_credit?: string;
}

export interface FastPurchaseLinePreview {
    line_number: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    description?: string;
    is_stock: boolean;
    quantity: string;
    base_quantity?: string;
    unit_cost: string;
    discount_amount: string;
    tax_amount: string;
    withholding_amount: string;
    line_total: string;
}

export interface FastPurchaseResult {
    supplier_reference?: string;
    mode: string;
    options: FastPurchasePayload['options'];
    supplier?: NamedResource | null;
    summary: {
        subtotal: string;
        discount_total: string;
        tax_total: string;
        withholding_total: string;
        grand_total: string;
        paid_total: string;
        balance_due: string;
        stock_taxable_total?: string;
        non_stock_taxable_total?: string;
    };
    lines: FastPurchaseLinePreview[];
    documents: {
        goods_receipt?: FastPurchaseDocumentReference | null;
        inventory_transaction?: FastPurchaseDocumentReference | null;
        inventory_transactions?: FastPurchaseDocumentReference[];
        supplier_invoice?: FastPurchaseDocumentReference | null;
        supplier_payment?: FastPurchaseDocumentReference | null;
        finance_posting?: FastPurchaseDocumentReference | null;
        finance_postings?: FastPurchaseDocumentReference[];
    };
}
