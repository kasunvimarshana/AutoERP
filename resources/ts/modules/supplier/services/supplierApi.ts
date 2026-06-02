import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type {
    Supplier,
    SupplierAddress,
    SupplierBankAccount,
    SupplierContact,
    SupplierFinanceDefaults,
    SupplierFormInput,
    SupplierStatus,
    SupplierTaxProfile,
    SupplierUserAccess,
    SupplierUserAccessCreateInput,
    SupplierUserAccessLinkInput,
} from '../types/supplier.types';

type BackendRecord = Record<string, unknown>;

type SupplierListQuery = {
    page?: number;
    perPage?: number;
    search?: string;
    status?: SupplierStatus;
};

export type SupplierLookupOption = {
    id: string;
    label: string;
    secondary?: string;
};

function asRecord(value: unknown): BackendRecord {
    return value && typeof value === 'object' && !Array.isArray(value) ? (value as BackendRecord) : {};
}

function asString(value: unknown, fallback = ''): string {
    if (value === null || value === undefined) {
        return fallback;
    }

    return String(value);
}

function asOptionalString(value: unknown): string | undefined {
    const normalized = asString(value).trim();

    return normalized === '' ? undefined : normalized;
}

function asBoolean(value: unknown, fallback = false): boolean {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value === 1;
    }

    if (typeof value === 'string') {
        return ['1', 'true', 'yes'].includes(value.toLowerCase());
    }

    return fallback;
}

function normalizeStatus(value: unknown): SupplierStatus {
    const status = asString(value, 'draft').toLowerCase();
    const allowed: SupplierStatus[] = ['active', 'archived', 'blocked', 'draft', 'inactive', 'pending_approval', 'suspended'];

    return allowed.includes(status as SupplierStatus) ? (status as SupplierStatus) : 'draft';
}

function normalizeSupplier(raw: BackendRecord): Supplier {
    const userAccounts = Array.isArray(raw.user_accounts) ? raw.user_accounts : [];

    return {
        category: asString(raw.category_name ?? raw.category ?? raw.supplier_category, 'Uncategorized'),
        code: asString(raw.supplier_code ?? raw.code),
        defaultCurrency: asString(raw.default_currency_code ?? raw.defaultCurrency),
        displayName: asString(raw.display_name ?? raw.supplier_name ?? raw.name),
        email: asString(raw.email),
        id: asString(raw.id),
        legalName: asOptionalString(raw.legal_name),
        mobile: asOptionalString(raw.mobile),
        name: asString(raw.supplier_name ?? raw.name ?? raw.display_name),
        notes: asOptionalString(raw.notes),
        phone: asString(raw.phone),
        registrationNumber: asOptionalString(raw.registration_number),
        status: normalizeStatus(raw.status),
        supplierType: asString(raw.supplier_type ?? raw.supplierType, 'business'),
        taxNumber: asOptionalString(raw.tax_number ?? raw.taxNumber),
        updatedAt: asString(raw.updated_at ?? raw.updatedAt),
        userAccessStatus: raw.user_access_status === 'linked' || raw.has_user_access === true || userAccounts.length > 0 ? 'linked' : 'none',
        vatNumber: asOptionalString(raw.vat_number ?? raw.vatNumber),
        website: asOptionalString(raw.website),
    };
}

function normalizeLookup(raw: BackendRecord): SupplierLookupOption {
    const code = asString(raw.supplier_code ?? raw.code);
    const name = asString(raw.supplier_name ?? raw.display_name ?? raw.name, 'Supplier');
    const contact = asOptionalString(raw.phone ?? raw.mobile ?? raw.email);

    return {
        id: asString(raw.id),
        label: [code, name].filter(Boolean).join(' - ') || name,
        secondary: contact,
    };
}

function normalizeContact(raw: BackendRecord): SupplierContact {
    return {
        department: asOptionalString(raw.department),
        designation: asOptionalString(raw.designation ?? raw.role),
        email: asString(raw.email),
        id: asString(raw.id),
        isPrimary: asBoolean(raw.is_primary),
        name: asString(raw.name ?? raw.contact_name),
        phone: asString(raw.phone ?? raw.mobile),
        supplierId: asString(raw.supplier_id),
    };
}

function normalizeAddress(raw: BackendRecord): SupplierAddress {
    return {
        city: asString(raw.city),
        country: asString(raw.country_name ?? raw.country),
        id: asString(raw.id),
        isDefault: asBoolean(raw.is_default ?? raw.is_default_billing ?? raw.is_default_shipping),
        line1: asString(raw.address_line1 ?? raw.address_line_1 ?? raw.line1),
        line2: asOptionalString(raw.address_line2 ?? raw.address_line_2 ?? raw.line2),
        postalCode: asOptionalString(raw.postal_code),
        supplierId: asString(raw.supplier_id),
        type: normalizeAddressType(raw.type ?? raw.address_type),
    };
}

function normalizeAddressType(value: unknown): SupplierAddress['type'] {
    const type = asString(value, 'billing').toLowerCase();

    if (type === 'registered' || type === 'shipping') {
        return type;
    }

    return 'billing';
}

function normalizeBankAccount(raw: BackendRecord, supplierId: string): SupplierBankAccount {
    return {
        accountName: asString(raw.account_name),
        accountNumber: asString(raw.account_number),
        bankName: asString(raw.bank_name),
        branchName: asOptionalString(raw.branch_name),
        currency: asString(raw.currency_code ?? raw.currency ?? raw.currency_id),
        id: asString(raw.id),
        isActive: asBoolean(raw.is_active, true),
        isPrimary: asBoolean(raw.is_primary),
        supplierId,
    };
}

function normalizeTaxProfile(raw: BackendRecord | null | undefined, supplierId: string): SupplierTaxProfile {
    const record = asRecord(raw);

    return {
        isTaxExempt: asBoolean(record.is_tax_exempt),
        supplierId,
        taxIdentifier: asOptionalString(record.tax_identifier),
        taxType: asString(record.tax_type),
        vatIdentifier: asOptionalString(record.vat_identifier),
        withholdingRate: asString(record.withholding_rate),
    };
}

function normalizeFinanceDefaults(raw: BackendRecord, supplierId: string): SupplierFinanceDefaults {
    return {
        creditLimit: asString(raw.credit_limit),
        defaultCurrency: asString(raw.default_currency_code ?? raw.default_currency_id),
        expenseAccount: asString(raw.default_expense_account_name ?? raw.default_expense_account_id),
        payableAccount: asString(raw.default_payable_account_name ?? raw.default_payable_account_id),
        paymentTerm: asString(raw.default_payment_term_name ?? raw.default_payment_term_id),
        supplierId,
    };
}

function normalizeUserAccess(raw: BackendRecord, supplierId: string): SupplierUserAccess {
    return {
        email: asString(raw.email ?? raw.user_email),
        id: asString(raw.id),
        invitedAt: asOptionalString(raw.invited_at ?? raw.linked_at),
        isPrimary: asBoolean(raw.is_primary),
        lastLogin: asOptionalString(raw.last_login_at ?? raw.lastLogin),
        status: normalizeUserAccessStatus(raw.status),
        supplierId,
        userName: asString(raw.user_name ?? raw.name ?? raw.user_id, 'Linked user'),
    };
}

function normalizeUserAccessStatus(value: unknown): SupplierUserAccess['status'] {
    const status = asString(value, 'active').toLowerCase();

    if (status === 'inactive' || status === 'revoked' || status === 'deactivated') {
        return 'deactivated';
    }

    if (status === 'invited') {
        return 'invited';
    }

    return 'active';
}

function toBackendSupplierPayload(input: SupplierFormInput): BackendRecord {
    return {
        create_user_access: false,
        display_name: input.displayName || input.name,
        email: input.email || null,
        legal_name: input.legalName || null,
        mobile: input.mobile || null,
        notes: input.notes || null,
        phone: input.phone || null,
        registration_number: input.registrationNumber || null,
        status: input.status || 'draft',
        supplier_code: input.code,
        supplier_name: input.name,
        supplier_type: input.supplierType || null,
        tax_number: input.taxNumber || null,
        vat_number: input.vatNumber || null,
        website: input.website || null,
    };
}

function toBackendContactPayload(supplierId: string, input: SupplierContact): BackendRecord {
    return {
        department: input.department || null,
        designation: input.designation || null,
        email: input.email || null,
        is_primary: input.isPrimary,
        name: input.name,
        phone: input.phone || null,
        supplier_id: Number(supplierId),
    };
}

function toBackendAddressPayload(supplierId: string, input: SupplierAddress): BackendRecord {
    return {
        address_line1: input.line1,
        address_line2: input.line2 || null,
        city: input.city,
        is_default: input.isDefault,
        postal_code: input.postalCode || '00000',
        supplier_id: Number(supplierId),
        type: input.type,
    };
}

function toBackendBankAccountPayload(input: SupplierBankAccount): BackendRecord {
    return {
        account_name: input.accountName,
        account_number: input.accountNumber,
        bank_name: input.bankName,
        branch_name: input.branchName || null,
        is_active: input.isActive,
        is_primary: input.isPrimary,
    };
}

export const supplierApi = {
    activateSupplier: (supplierId: string) => supplierApi.changeStatus(supplierId, 'active'),
    blockSupplier: (supplierId: string) => supplierApi.changeStatus(supplierId, 'blocked'),
    changeStatus: async (supplierId: string, status: SupplierStatus): Promise<ApiResponse<Supplier>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/status`, {
            body: { status },
            method: 'PATCH',
        });

        return { ...response, data: normalizeSupplier(response.data) };
    },
    createSupplier: async (input: SupplierFormInput): Promise<ApiResponse<Supplier>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/supplier/suppliers', {
            body: toBackendSupplierPayload(input),
            method: 'POST',
        });

        return { ...response, data: normalizeSupplier(response.data) };
    },
    createUserAccess: (supplierId: string, input: SupplierUserAccessCreateInput) =>
        httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/user-accesses`, {
            body: {
                access_type: input.accessType || null,
                is_primary: input.isPrimary ?? false,
                user: {
                    email: input.email,
                    name: input.name || null,
                },
            },
            method: 'POST',
        }),
    deactivateAddress: (addressId: string) =>
        httpClient<ApiResponse<BackendRecord>>(`/api/supplier/supplier-addresses/${addressId}`, {
            body: { is_active: false },
            method: 'PATCH',
        }),
    deactivateBankAccount: (supplierId: string, bankAccountId: string) =>
        httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/bank-accounts/${bankAccountId}`, {
            body: { is_active: false },
            method: 'PUT',
        }),
    deactivateContact: (contactId: string) =>
        httpClient<ApiResponse<BackendRecord>>(`/api/supplier/supplier-contacts/${contactId}`, {
            body: { is_active: false },
            method: 'PATCH',
        }),
    deactivateSupplier: (supplierId: string) => supplierApi.changeStatus(supplierId, 'inactive'),
    deactivateUserAccess: (supplierId: string, accessId: string, reason?: string) =>
        httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/user-accesses/${accessId}/deactivate`, {
            body: { reason: reason || null },
            method: 'PATCH',
        }),
    getFinanceDefaults: async (supplierId: string): Promise<ApiResponse<SupplierFinanceDefaults>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/finance-defaults`);

        return { ...response, data: normalizeFinanceDefaults(response.data, supplierId) };
    },
    getSupplier: async (supplierId: string): Promise<ApiResponse<Supplier>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}`);

        return { ...response, data: normalizeSupplier(response.data) };
    },
    getTaxProfile: async (supplierId: string): Promise<ApiResponse<SupplierTaxProfile>> => {
        const response = await httpClient<ApiResponse<BackendRecord | null>>(`/api/supplier/suppliers/${supplierId}/tax-profile`);

        return { ...response, data: normalizeTaxProfile(response.data, supplierId) };
    },
    linkUserAccess: (supplierId: string, input: SupplierUserAccessLinkInput) =>
        httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/link-user`, {
            body: {
                access_type: input.accessType || null,
                is_primary: input.isPrimary ?? false,
                user_id: Number(input.userId),
            },
            method: 'POST',
        }),
    listAddresses: async (supplierId: string): Promise<ApiCollectionResponse<SupplierAddress>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/supplier/supplier-addresses', { query: { supplier_id: supplierId } });

        return { ...response, data: response.data.map(normalizeAddress) };
    },
    listBankAccounts: async (supplierId: string): Promise<ApiCollectionResponse<SupplierBankAccount>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/bank-accounts`);

        return { ...response, data: response.data.map((item) => normalizeBankAccount(item, supplierId)) };
    },
    listContacts: async (supplierId: string): Promise<ApiCollectionResponse<SupplierContact>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/supplier/supplier-contacts', { query: { supplier_id: supplierId } });

        return { ...response, data: response.data.map(normalizeContact) };
    },
    listSuppliers: async (query: SupplierListQuery = {}): Promise<ApiCollectionResponse<Supplier>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/supplier/suppliers', {
            query: {
                page: query.page,
                per_page: query.perPage ?? 25,
                search: query.search,
                status: query.status,
            },
        });

        return { ...response, data: response.data.map(normalizeSupplier) };
    },
    lookupSuppliers: async (query: Pick<SupplierListQuery, 'perPage' | 'search'> = {}): Promise<ApiCollectionResponse<SupplierLookupOption>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/supplier/suppliers-lookup', {
            query: {
                limit: query.perPage ?? 25,
                q: query.search,
            },
        });

        return { ...response, data: response.data.map(normalizeLookup) };
    },
    listUserAccess: async (supplierId: string): Promise<ApiCollectionResponse<SupplierUserAccess>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/user-accesses`);

        return { ...response, data: response.data.map((item) => normalizeUserAccess(item, supplierId)) };
    },
    setPrimaryBankAccount: (supplierId: string, bankAccountId: string) =>
        httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/bank-accounts/${bankAccountId}`, {
            body: { is_primary: true },
            method: 'PUT',
        }),
    unblockSupplier: (supplierId: string) => supplierApi.changeStatus(supplierId, 'active'),
    unlinkUserAccess: (supplierId: string, accessId: string) =>
        httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/user-accesses/${accessId}`, { method: 'DELETE' }),
    updateFinanceDefaults: (supplierId: string, input: unknown) =>
        httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/finance-defaults`, { body: input, method: 'PUT' }),
    updateSupplier: async (supplierId: string, input: SupplierFormInput): Promise<ApiResponse<Supplier>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}`, {
            body: toBackendSupplierPayload(input),
            method: 'PUT',
        });

        return { ...response, data: normalizeSupplier(response.data) };
    },
    updateTaxProfile: async (supplierId: string, input: SupplierTaxProfile): Promise<ApiResponse<SupplierTaxProfile>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/tax-profile`, {
            body: {
                is_tax_exempt: input.isTaxExempt,
                tax_identifier: input.taxIdentifier || null,
                tax_type: input.taxType || null,
                vat_identifier: input.vatIdentifier || null,
                withholding_rate: input.withholdingRate || null,
            },
            method: 'PUT',
        });

        return { ...response, data: normalizeTaxProfile(response.data, supplierId) };
    },
    upsertAddress: async (supplierId: string, input: SupplierAddress): Promise<ApiResponse<SupplierAddress>> => {
        const hasExistingId = input.id.trim() !== '';
        const response = await httpClient<ApiResponse<BackendRecord>>(
            hasExistingId ? `/api/supplier/supplier-addresses/${input.id}` : '/api/supplier/supplier-addresses',
            {
                body: toBackendAddressPayload(supplierId, input),
                method: hasExistingId ? 'PATCH' : 'POST',
            },
        );

        return { ...response, data: normalizeAddress(response.data) };
    },
    upsertBankAccount: async (supplierId: string, input: SupplierBankAccount): Promise<ApiResponse<SupplierBankAccount>> => {
        const hasExistingId = input.id.trim() !== '';
        const response = await httpClient<ApiResponse<BackendRecord>>(
            hasExistingId
                ? `/api/supplier/suppliers/${supplierId}/bank-accounts/${input.id}`
                : `/api/supplier/suppliers/${supplierId}/bank-accounts`,
            {
                body: toBackendBankAccountPayload(input),
                method: hasExistingId ? 'PUT' : 'POST',
            },
        );

        return { ...response, data: normalizeBankAccount(response.data, supplierId) };
    },
    upsertContact: async (supplierId: string, input: SupplierContact): Promise<ApiResponse<SupplierContact>> => {
        const hasExistingId = input.id.trim() !== '';
        const response = await httpClient<ApiResponse<BackendRecord>>(
            hasExistingId ? `/api/supplier/supplier-contacts/${input.id}` : '/api/supplier/supplier-contacts',
            {
                body: toBackendContactPayload(supplierId, input),
                method: hasExistingId ? 'PATCH' : 'POST',
            },
        );

        return { ...response, data: normalizeContact(response.data) };
    },
};
