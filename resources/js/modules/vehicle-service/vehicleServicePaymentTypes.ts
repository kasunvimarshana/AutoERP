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

export interface VehicleServicePaymentLinePayload {
    amount: string;
    payment_method_id: number;
    reference_number?: string;
    instrument_direction?: 'inbound';
    external_bank_name?: string;
    external_bank_branch?: string;
    instrument_number?: string;
    instrument_date?: string;
}

export interface VehicleServicePaymentPayload {
    expected_job_version: number;
    payment_date: string;
    amount: string;
    currency_id?: number;
    exchange_rate?: string;
    reference_number?: string;
    notes?: string;
    lines: VehicleServicePaymentLinePayload[];
}
