import type { NamedResource } from '@/shared/types/common';

export interface Supplier extends NamedResource {
    supplier_number?: string | null;
    supplier_type: string;
    status: string;
    legal_name?: string | null;
    display_name?: string | null;
    email?: string | null;
    phone?: string | null;
    mobile?: string | null;
    website?: string | null;
    credit_limit?: string | null;
    opening_balance?: string | null;
    is_credit_allowed?: boolean;
    is_advance_allowed?: boolean;
    notes?: string | null;
    contacts?: Record<string, unknown>[];
    addresses?: Record<string, unknown>[];
    bank_accounts?: Record<string, unknown>[];
    categories?: NamedResource[];
    documents?: Record<string, unknown>[];
    item_mappings?: Record<string, unknown>[];
    credit_profile?: Record<string, unknown> | null;
    status_histories?: Record<string, unknown>[];
}

export interface SupplierPayload {
    code: string;
    name: string;
    supplier_type: string;
    status?: string;
    legal_name?: string;
    display_name?: string;
    email?: string;
    phone?: string;
    mobile?: string;
    website?: string;
    credit_limit?: string;
    opening_balance?: string;
    is_credit_allowed?: boolean;
    is_advance_allowed?: boolean;
    notes?: string;
    contacts?: Array<{ contact_name: string; email?: string; phone?: string; is_primary?: boolean }>;
    addresses?: Array<{ address_type: string; address_line_1: string; city?: string; country?: string; is_primary?: boolean }>;
}
