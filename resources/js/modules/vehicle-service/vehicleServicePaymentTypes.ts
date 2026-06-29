export interface VehicleServicePaymentMethodOption {
    id: number;
    code: string;
    name: string;
    method_type: string;
    requires_reference: boolean;
    requires_instrument_details: boolean;
}

export interface VehicleServicePaymentOptions {
    job_version: number;
    methods: VehicleServicePaymentMethodOption[];
}

export interface VehicleServicePaymentPayload {
    expected_job_version: number;
    invoice_id: number;
    payment_date: string;
    amount: string;
    payment_method_id: number;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    external_bank_name?: string;
    external_bank_branch?: string;
    instrument_number?: string;
    instrument_date?: string;
}

export interface PreparedVehicleServicePayment {
    paymentType: string;
    direction: string;
    paymentDate: string;
    referenceNumber?: string | null;
    lines: Array<{
        amount: string;
        paymentMethodId?: number | null;
        externalBankName?: string | null;
        externalBankBranch?: string | null;
        instrumentNumber?: string | null;
        instrumentDate?: string | null;
    }>;
    allocations: Array<{
        invoiceId: number;
        allocatedAmount: string;
    }>;
}
