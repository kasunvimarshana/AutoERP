export interface InvoicePartySnapshot extends Record<string, unknown> {
    id?: number | null;
    number?: string | null;
    code?: string | null;
    name?: string | null;
    legal_name?: string | null;
    tax_registration_number?: string | null;
    email?: string | null;
    phone?: string | null;
}

export interface InvoiceCurrencySnapshot extends Record<string, unknown> {
    id?: number | null;
    code?: string | null;
    name?: string | null;
    symbol?: string | null;
}

export interface InvoiceItemSnapshot extends Record<string, unknown> {
    code?: string | null;
    name?: string | null;
}

export interface InvoiceUomSnapshot extends Record<string, unknown> {
    code?: string | null;
    name?: string | null;
}

export interface InvoiceTaxSnapshot extends Record<string, unknown> {
    tax_id?: number;
    tax_code?: string;
    tax_name?: string;
    tax_type?: string;
    calculation_method?: string;
    rate?: string;
    sequence?: number;
    taxable_amount?: string;
    tax_amount?: string;
    is_withholding?: boolean;
}

export interface InvoiceLine extends Record<string, unknown> {
    id: number;
    line_number: number;
    item?: InvoiceItemSnapshot | null;
    description: string;
    line_type: string;
    quantity: string;
    uom?: InvoiceUomSnapshot | null;
    unit_price: string;
    discount_amount: string;
    tax_amount: string;
    taxes?: InvoiceTaxSnapshot[];
    charge_amount: string;
    line_total: string;
    source_line_type?: string | null;
}

export interface InvoiceBalance extends Record<string, unknown> {
    invoice_total: string;
    paid_amount: string;
    credit_allocated_amount: string;
    debit_allocated_amount: string;
    refunded_amount: string;
    remaining_amount: string;
    status: string;
}

export interface InvoiceBalanceResult extends Record<string, unknown> {
    invoiceId: number;
    invoiceTotal: string;
    paidAmount: string;
    creditAmount: string;
    debitAmount: string;
    refundedAmount: string;
    remainingAmount: string;
    status: string;
}

export interface InvoiceSource extends Record<string, unknown> {
    id: number;
    source_type: string;
    source_document_number?: string | null;
    source_document_date?: string | null;
    source_subtotal: string;
    source_adjustment_total: string;
    source_grand_total: string;
    invoiced_amount: string;
    allocated_adjustment_amount: string;
}

export interface InvoiceSourceLine extends Record<string, unknown> {
    id: number;
    source_type: string;
    source_line_type: string;
    source_quantity: string;
    previously_invoiced_quantity: string;
    invoiced_quantity: string;
    remaining_quantity: string;
    source_unit_price: string;
    source_line_total: string;
    invoiced_line_total: string;
}

export interface InvoiceAdjustmentAllocation extends Record<string, unknown> {
    id: number;
    source_type: string;
    adjustment_type: string;
    effect: string;
    allocation_method: string;
    source_amount: string;
    previously_allocated_amount: string;
    allocated_amount: string;
    remaining_amount: string;
}

export interface InvoiceAdjustment extends Record<string, unknown> {
    id: number;
    name: string;
    adjustment_type: string;
    effect: string;
    calculation_type: string;
    rate: string;
    amount: string;
    allocation_method: string;
    is_system_generated: boolean;
    description?: string | null;
    allocations: InvoiceAdjustmentAllocation[];
}

export interface Invoice extends Record<string, unknown> {
    id: number;
    row_version: number;
    invoice_number?: string;
    invoice_date?: string;
    due_date?: string | null;
    invoice_type?: string;
    direction?: string;
    status?: string;
    party_type?: string | null;
    party?: InvoicePartySnapshot | null;
    currency?: InvoiceCurrencySnapshot | null;
    grand_total?: string;
    paid_total?: string;
    credit_total?: string;
    balance_due?: string;
    approved_at?: string | null;
    posted_at?: string | null;
    cancelled_at?: string | null;
    cancellation_reason?: string | null;
    balance?: InvoiceBalance | null;
    lines?: InvoiceLine[];
}

export interface InvoiceSourcesResult {
    sources: InvoiceSource[];
    source_lines: InvoiceSourceLine[];
}
