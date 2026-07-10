import type { NamedResource } from '@/shared/types/common';

export const customerTypes = ['individual', 'company', 'government', 'internal', 'foreign', 'retail', 'wholesale', 'corporate', 'other'] as const;
export const customerStatuses = ['pending_approval', 'active', 'inactive', 'on_hold', 'blacklisted'] as const;
export const customerAddressTypes = ['billing', 'shipping', 'registered', 'service', 'other'] as const;
export const customerDocumentTypes = ['business_registration', 'tax_certificate', 'vat_certificate', 'svat_certificate', 'contract', 'license', 'insurance', 'id_document', 'other'] as const;
export const customerDocumentStatuses = ['active', 'expired', 'revoked', 'pending'] as const;
export const preferredCommunicationChannels = ['email', 'phone', 'mobile', 'sms', 'whatsapp', 'other'] as const;

export interface CustomerSummary extends NamedResource {
    row_version: number;
    customer_number: string;
    code: string;
    display_name?: string | null;
    customer_type: string;
    status: string;
    email?: string | null;
    phone?: string | null;
    mobile?: string | null;
    default_currency?: NamedResource | null;
    categories?: CustomerCategory[];
    credit_allowed: boolean;
    advance_allowed: boolean;
    is_tax_exempt: boolean;
    marketing_consent: boolean;
}

export interface Customer extends CustomerSummary {
    legal_name?: string | null;
    website?: string | null;
    tax_registration_number?: string | null;
    vat_number?: string | null;
    svat_number?: string | null;
    business_registration_number?: string | null;
    preferred_communication_channel?: string | null;
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
    approved_at?: string | null;
    contacts?: CustomerContact[];
    addresses?: CustomerAddress[];
    bank_accounts?: CustomerBankAccount[];
    documents?: CustomerDocument[];
    credit_profile?: CustomerCreditProfile | null;
    status_history?: CustomerStatusHistory[];
}

export interface CustomerPayload {
    row_version?: number;
    customer_number?: string | null;
    code: string;
    name: string;
    customer_type: string;
    status?: string;
    legal_name?: string | null;
    display_name?: string | null;
    email?: string | null;
    phone?: string | null;
    mobile?: string | null;
    website?: string | null;
    default_currency_id?: number | null;
    payment_term_id?: number | null;
    tax_registration_number?: string | null;
    vat_number?: string | null;
    svat_number?: string | null;
    business_registration_number?: string | null;
    is_tax_exempt: boolean;
    marketing_consent: boolean;
    preferred_communication_channel?: string | null;
    notes?: string | null;
}

export interface CustomerContact {
    id: number;
    contact_name: string;
    designation?: string | null;
    department?: string | null;
    email?: string | null;
    phone?: string | null;
    mobile?: string | null;
    is_primary: boolean;
    is_active: boolean;
    notes?: string | null;
}

export type CustomerContactPayload = Omit<CustomerContact, 'id'>;

export interface CustomerAddress {
    id: number;
    address_type: string;
    address_line_1: string;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    postal_code?: string | null;
    country?: string | null;
    is_primary: boolean;
    is_active: boolean;
}

export type CustomerAddressPayload = Omit<CustomerAddress, 'id'>;

export interface CustomerBankAccount {
    id: number;
    bank_name: string;
    branch_name?: string | null;
    account_name: string;
    account_number: string;
    swift_code?: string | null;
    iban?: string | null;
    currency?: NamedResource | null;
    is_primary: boolean;
    is_active: boolean;
    notes?: string | null;
}

export interface CustomerBankAccountPayload {
    bank_name: string;
    branch_name?: string | null;
    account_name: string;
    account_number: string;
    swift_code?: string | null;
    iban?: string | null;
    currency_id?: number | null;
    is_primary: boolean;
    is_active: boolean;
    notes?: string | null;
}

export interface CustomerCategory extends NamedResource {
    code: string;
    description?: string | null;
    parent?: NamedResource | null;
    is_active: boolean;
    sort_order: number;
}

export interface CustomerDocument {
    id: number;
    document_type: string;
    document_number?: string | null;
    issued_date?: string | null;
    expiry_date?: string | null;
    file_path?: string | null;
    status: string;
    notes?: string | null;
}

export type CustomerDocumentPayload = Omit<CustomerDocument, 'id'>;

export interface CustomerCreditProfile {
    id?: number;
    row_version: number;
    credit_limit: string;
    credit_period_days?: number | null;
    warning_threshold_percent: string;
    credit_allowed: boolean;
    advance_allowed: boolean;
    allow_over_credit: boolean;
    allow_partial_payment: boolean;
    is_active: boolean;
}

export interface CustomerStatusHistory {
    id: number;
    old_status?: string | null;
    new_status: string;
    reason?: string | null;
    changed_at: string;
}

export interface CustomerWithRelationsPayload {
    customer: CustomerPayload;
    contacts: CustomerContactPayload[];
    addresses: CustomerAddressPayload[];
    bank_accounts: CustomerBankAccountPayload[];
    categories: number[];
    documents: CustomerDocumentPayload[];
    credit_profile?: Omit<CustomerCreditProfile, 'id' | 'row_version'> | null;
}
