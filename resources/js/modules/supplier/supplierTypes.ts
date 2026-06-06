import type { NamedResource } from '@/shared/types/common';

export const supplierTypes = ['company', 'individual', 'government', 'internal', 'foreign', 'other'] as const;
export const supplierStatuses = ['pending_approval', 'active', 'inactive', 'on_hold', 'blacklisted'] as const;
export const supplierAddressTypes = ['billing', 'shipping', 'registered', 'warehouse', 'other'] as const;
export const supplierDocumentTypes = ['business_registration', 'tax_certificate', 'vat_certificate', 'svat_certificate', 'contract', 'license', 'insurance', 'other'] as const;
export const supplierDocumentStatuses = ['active', 'expired', 'revoked', 'pending'] as const;

export interface SupplierSummary extends NamedResource {
    supplier_number: string;
    code: string;
    display_name?: string | null;
    supplier_type: string;
    status: string;
    email?: string | null;
    phone?: string | null;
    mobile?: string | null;
    default_currency?: NamedResource | null;
    categories?: SupplierCategory[];
    is_credit_allowed: boolean;
    is_advance_allowed: boolean;
}

export interface Supplier extends SupplierSummary {
    legal_name?: string | null;
    website?: string | null;
    tax_registration_number?: string | null;
    vat_number?: string | null;
    svat_number?: string | null;
    business_registration_number?: string | null;
    credit_limit: string;
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
    approved_at?: string | null;
    contacts?: SupplierContact[];
    addresses?: SupplierAddress[];
    bank_accounts?: SupplierBankAccount[];
    documents?: SupplierDocument[];
    item_mappings?: SupplierItemMapping[];
    credit_profile?: SupplierCreditProfile | null;
    status_history?: SupplierStatusHistory[];
}

export interface SupplierPayload {
    supplier_number?: string | null;
    code: string;
    name: string;
    supplier_type: string;
    status?: string;
    legal_name?: string | null;
    display_name?: string | null;
    email?: string | null;
    phone?: string | null;
    mobile?: string | null;
    website?: string | null;
    default_currency_id?: number | null;
    tax_registration_number?: string | null;
    vat_number?: string | null;
    svat_number?: string | null;
    business_registration_number?: string | null;
    credit_limit?: string;
    is_credit_allowed: boolean;
    is_advance_allowed: boolean;
    notes?: string | null;
}

export interface SupplierContact {
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

export type SupplierContactPayload = Omit<SupplierContact, 'id'>;

export interface SupplierAddress {
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

export type SupplierAddressPayload = Omit<SupplierAddress, 'id'>;

export interface SupplierBankAccount {
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

export interface SupplierBankAccountPayload {
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

export interface SupplierCategory extends NamedResource {
    code: string;
    description?: string | null;
    parent?: NamedResource | null;
    is_active: boolean;
    sort_order: number;
}

export interface SupplierDocument {
    id: number;
    document_type: string;
    document_number?: string | null;
    issued_date?: string | null;
    expiry_date?: string | null;
    file_path?: string | null;
    status: string;
    notes?: string | null;
}

export type SupplierDocumentPayload = Omit<SupplierDocument, 'id'>;

export interface SupplierItemMapping {
    id: number;
    item: NamedResource | null;
    variant?: NamedResource | null;
    supplier_item_code?: string | null;
    supplier_item_name?: string | null;
    default_purchase_uom?: NamedResource | null;
    minimum_order_quantity: string;
    lead_time_days?: number | null;
    is_preferred: boolean;
    is_active: boolean;
}

export interface SupplierItemMappingPayload {
    item_id: number;
    item_variant_id?: number | null;
    supplier_item_code?: string | null;
    supplier_item_name?: string | null;
    default_purchase_uom_id?: number | null;
    minimum_order_quantity: string;
    lead_time_days?: number | null;
    is_preferred: boolean;
    is_active: boolean;
}

export interface SupplierCreditProfile {
    id?: number;
    credit_limit: string;
    credit_period_days?: number | null;
    warning_threshold_percent: string;
    allow_over_credit: boolean;
    allow_partial_payment: boolean;
    is_active: boolean;
}

export interface SupplierStatusHistory {
    id: number;
    old_status?: string | null;
    new_status: string;
    reason?: string | null;
    changed_at: string;
}

export interface SupplierWithRelationsPayload {
    supplier: SupplierPayload;
    contacts: SupplierContactPayload[];
    addresses: SupplierAddressPayload[];
    bank_accounts: SupplierBankAccountPayload[];
    categories: number[];
    documents: SupplierDocumentPayload[];
    item_mappings: SupplierItemMappingPayload[];
    credit_profile?: SupplierCreditProfile | null;
}
