export interface FastSalesPaymentLinePayload {
    amount: string;
    payment_method_id?: number;
    reference?: string;
    instrument_number?: string;
    instrument_date?: string;
    external_bank_name?: string;
    external_bank_branch?: string;
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
        lines?: FastSalesPaymentLinePayload[];
    };
}
