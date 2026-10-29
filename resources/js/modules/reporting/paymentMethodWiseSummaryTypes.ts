export type PaymentMethodWiseSource = 'all' | 'selling' | 'vehicle_service';

export interface PaymentMethodWiseParams {
    date_from: string;
    date_to: string;
    source: PaymentMethodWiseSource;
}

export interface PaymentMethodWiseAmountRow {
    currency: string;
    credit_sales: string;
    receipts: string;
    methods: Record<string, string>;
}

export interface PaymentMethodWiseDay extends PaymentMethodWiseAmountRow {
    date: string;
    documents?: PaymentMethodWiseDocument[];
}

export interface PaymentMethodWiseDocument {
    type: 'credit_sale' | 'receipt';
    invoice: { id: number; number: string };
    payment: { id: number; number: string } | null;
    amount: string;
    methods: Record<string, string>;
}

export interface PaymentMethodWiseResult {
    period: { date_from: string; date_to: string };
    source: PaymentMethodWiseSource;
    organization_unit: string;
    methods: Array<{ key: string; name: string }>;
    days: PaymentMethodWiseDay[];
    totals: PaymentMethodWiseAmountRow[];
}
