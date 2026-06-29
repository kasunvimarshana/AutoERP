export interface FastSalesOptionResource {
    id: number;
    code?: string | null;
    name?: string | null;
    symbol?: string | null;
    is_default?: boolean;
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
