import type { NamedResource } from '@/shared/types/common';

export interface FastPurchaseOptionResource {
    id: number;
    code?: string | null;
    name?: string | null;
    symbol?: string | null;
    is_default?: boolean;
    method_type?: string;
    requires_reference?: boolean;
    requires_instrument_details?: boolean;
}

export interface FastPurchaseContext {
    defaults: {
        purchase_date: string;
        exchange_rate: string;
        currency_id?: number | null;
        currency?: NamedResource | null;
        currency_source?: string;
        exchange_rate_source?: string;
        warehouse_id?: number | null;
        warehouse?: NamedResource | null;
        warehouse_location_id?: number | null;
        warehouse_location?: NamedResource | null;
        warehouse_location_source?: string;
    };
    endpoints: Record<string, string>;
    warehouses: FastPurchaseOptionResource[];
    currencies: FastPurchaseOptionResource[];
    payment_methods: FastPurchaseOptionResource[];
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
        client_line_key: string;
        item_id: number;
        item_variant_id?: number;
        description?: string;
        uom_id?: number;
        quantity: string;
        unit_cost?: string;
        pricing_mode: 'auto' | 'manual';
        manual_price_confirmed?: boolean;
        pricing_context_hash?: string;
        discount_calculation_type?: 'fixed' | 'percentage';
        discount_rate?: string;
        discount_amount?: string;
        tax_group_id?: number;
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
        amount?: string;
        allocation_method?: string;
        is_allocatable?: boolean;
        allocations?: Array<{
            client_line_key: string;
            amount: string;
        }>;
        description?: string;
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

export interface FastPurchaseDocumentReference {
    id: number;
    number: string;
    status?: string;
    url: string;
    total_debit?: string;
    total_credit?: string;
}

export interface FastPurchaseLinePreview {
    client_line_key?: string | null;
    line_number: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    description?: string;
    is_stock: boolean;
    quantity: string;
    base_quantity?: string;
    unit_cost: string;
    pricing_mode?: 'auto' | 'manual';
    price_source?: string | null;
    price_source_id?: number | null;
    pricing_context_hash?: string | null;
    line_subtotal?: string;
    discount_calculation_type?: 'fixed' | 'percentage';
    discount_rate?: string;
    discount_amount: string;
    tax_group_id?: number | null;
    tax_amount: string;
    withholding_amount: string;
    charge_calculation_type?: 'fixed' | 'percentage';
    charge_rate?: string;
    charge_amount?: string;
    line_total: string;
    taxes?: Array<Record<string, unknown>>;
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
        line_withholding_total?: string;
        charge_total?: string;
        adjustment_total?: string;
        header_increase_total?: string;
        header_decrease_total?: string;
        grand_total: string;
        paid_total: string;
        balance_due: string;
        stock_taxable_total?: string;
        non_stock_taxable_total?: string;
    };
    adjustments?: Array<Record<string, unknown>>;
    lines: FastPurchaseLinePreview[];
    document_plan?: Record<string, unknown>;
    documents: {
        purchase_order?: FastPurchaseDocumentReference | null;
        goods_receipt?: FastPurchaseDocumentReference | null;
        inventory_transaction?: FastPurchaseDocumentReference | null;
        inventory_transactions?: FastPurchaseDocumentReference[];
        supplier_invoice?: FastPurchaseDocumentReference | null;
        supplier_payment?: FastPurchaseDocumentReference | null;
        finance_posting?: FastPurchaseDocumentReference | null;
        finance_postings?: FastPurchaseDocumentReference[];
    };
}
