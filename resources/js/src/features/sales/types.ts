export type SalesOrderRecord = {
    id: number;
    tenant_id: number;
    customer_id: number;
    org_unit_id: number | null;
    warehouse_id: number;
    so_number: string;
    status: 'draft' | 'confirmed' | 'partial' | 'shipped' | 'invoiced' | 'closed' | 'cancelled';
    currency_id: number;
    exchange_rate: string | number | null;
    order_date: string;
    requested_delivery_date: string | null;
    price_list_id: number | null;
    subtotal: string | number;
    tax_total: string | number;
    discount_total: string | number;
    grand_total: string | number;
    notes: string | null;
    metadata: Record<string, unknown> | null;
    created_by: number | null;
    approved_by: number | null;
    created_at: string;
    updated_at: string;
};

export type ShipmentRecord = {
    id: number;
    tenant_id: number;
    customer_id: number;
    sales_order_id: number | null;
    warehouse_id: number;
    shipment_number: string;
    status: 'draft' | 'picking' | 'packed' | 'shipped' | 'delivered' | 'cancelled';
    shipped_date: string | null;
    carrier: string | null;
    tracking_number: string | null;
    currency_id: number;
    notes: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type SalesInvoiceRecord = {
    id: number;
    tenant_id: number;
    customer_id: number;
    sales_order_id: number | null;
    shipment_id: number | null;
    invoice_number: string;
    status: 'draft' | 'sent' | 'partial_paid' | 'paid' | 'overdue' | 'cancelled';
    invoice_date: string;
    due_date: string;
    currency_id: number;
    exchange_rate: string | number | null;
    subtotal: string | number;
    tax_total: string | number;
    discount_total: string | number;
    grand_total: string | number;
    ar_account_id: number | null;
    journal_entry_id: number | null;
    notes: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type SalesReturnRecord = {
    id: number;
    tenant_id: number;
    customer_id: number;
    original_sales_order_id: number | null;
    original_invoice_id: number | null;
    return_number: string;
    status: 'draft' | 'approved' | 'received' | 'closed' | 'cancelled';
    return_date: string;
    return_reason: string | null;
    currency_id: number;
    exchange_rate: string | number | null;
    subtotal: string | number;
    tax_total: string | number;
    restocking_fee_total: string | number;
    grand_total: string | number;
    credit_memo_number: string | null;
    journal_entry_id: number | null;
    notes: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type SalesOrderListFilters = {
    tenant_id?: number;
    customer_id?: number;
    status?: string;
    include?: string;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type ShipmentListFilters = {
    tenant_id?: number;
    customer_id?: number;
    sales_order_id?: number;
    status?: string;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type SalesInvoiceListFilters = {
    tenant_id?: number;
    customer_id?: number;
    sales_order_id?: number;
    status?: string;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type SalesReturnListFilters = {
    tenant_id?: number;
    customer_id?: number;
    original_sales_order_id?: number;
    status?: string;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type SalesOrderPayload = {
    tenant_id: number;
    customer_id: number;
    warehouse_id: number;
    currency_id: number;
    order_date: string;
    org_unit_id?: number | null;
    price_list_id?: number | null;
    requested_delivery_date?: string | null;
    exchange_rate?: number | null;
    subtotal?: number | null;
    tax_total?: number | null;
    discount_total?: number | null;
    grand_total?: number | null;
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
    created_by?: number | null;
};
