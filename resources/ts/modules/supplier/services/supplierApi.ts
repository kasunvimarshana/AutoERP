import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    getSupplierById,
    supplierAddresses,
    supplierAuditEntries,
    supplierBankAccounts,
    supplierContacts,
    supplierFinanceDefaults,
    supplierPurchaseUsage,
    suppliers,
    supplierTaxProfiles,
    supplierUserAccess,
} from '../mock/supplierMock';
import type {
    Supplier,
    SupplierAddress,
    SupplierAuditEntry,
    SupplierBankAccount,
    SupplierContact,
    SupplierFinanceDefaults,
    SupplierFormInput,
    SupplierPurchaseUsageSummary,
    SupplierStatus,
    SupplierTaxProfile,
    SupplierUserAccess,
    SupplierUserAccessCreateInput,
    SupplierUserAccessLinkInput,
} from '../types/supplier.types';

type BackendRecord = Record<string, unknown>;

const SUPPLIER_API_MODE = import.meta.env.VITE_SUPPLIER_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return SUPPLIER_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (SUPPLIER_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function normalizeStatus(value: unknown): SupplierStatus {
    const status = asString(value, 'draft').toLowerCase();
    const allowed: SupplierStatus[] = ['active', 'archived', 'blocked', 'draft', 'inactive', 'pending_approval', 'suspended'];

    return allowed.includes(status as SupplierStatus) ? (status as SupplierStatus) : 'draft';
}

function normalizeSupplier(raw: BackendRecord): Supplier {
    return {
        category: asString(raw.category_name ?? raw.category ?? raw.supplier_category, 'Uncategorized'),
        code: asString(raw.supplier_code ?? raw.code, 'SUP-MOCK'),
        defaultCurrency: asString(raw.default_currency_code ?? raw.defaultCurrency, ''),
        displayName: asString(raw.display_name ?? raw.supplier_name ?? raw.name, 'Unnamed supplier'),
        email: asString(raw.email, 'Not provided'),
        id: asString(raw.id),
        legalName: asString(raw.legal_name, ''),
        mobile: asString(raw.mobile, ''),
        name: asString(raw.supplier_name ?? raw.name ?? raw.display_name, 'Unnamed supplier'),
        phone: asString(raw.phone, 'Not provided'),
        registrationNumber: asString(raw.registration_number, ''),
        status: normalizeStatus(raw.status),
        supplierType: asString(raw.supplier_type ?? raw.supplierType, 'general'),
        taxNumber: asString(raw.tax_number ?? raw.taxNumber, ''),
        updatedAt: asString(raw.updated_at ?? raw.updatedAt, 'Backend timestamp pending'),
        userAccessStatus: raw.user_access_status === 'linked' || raw.has_user_access === true ? 'linked' : 'none',
        vatNumber: asString(raw.vat_number ?? raw.vatNumber, ''),
        website: asString(raw.website, ''),
    };
}

function normalizeContact(raw: BackendRecord): SupplierContact {
    return {
        department: asString(raw.department, ''),
        designation: asString(raw.designation ?? raw.role, ''),
        email: asString(raw.email, 'Not provided'),
        id: asString(raw.id),
        isPrimary: Boolean(raw.is_primary),
        name: asString(raw.name ?? raw.contact_name, 'Unnamed contact'),
        phone: asString(raw.phone ?? raw.mobile, 'Not provided'),
        supplierId: asString(raw.supplier_id),
    };
}

function normalizeAddress(raw: BackendRecord): SupplierAddress {
    return {
        city: asString(raw.city, 'Not provided'),
        country: asString(raw.country_name ?? raw.country, 'Not provided'),
        id: asString(raw.id),
        isDefault: Boolean(raw.is_default ?? raw.is_default_billing ?? raw.is_default_shipping),
        line1: asString(raw.address_line1 ?? raw.address_line_1 ?? raw.line1, 'Not provided'),
        line2: asString(raw.address_line2 ?? raw.address_line_2 ?? raw.line2, ''),
        postalCode: asString(raw.postal_code, ''),
        supplierId: asString(raw.supplier_id),
        type: asString(raw.type ?? raw.address_type, 'registered') as SupplierAddress['type'],
    };
}

function normalizeBankAccount(raw: BackendRecord, supplierId: string): SupplierBankAccount {
    return {
        accountName: asString(raw.account_name, 'Not provided'),
        accountNumber: asString(raw.account_number, 'Not provided'),
        bankName: asString(raw.bank_name, 'Not provided'),
        branchName: asString(raw.branch_name, ''),
        currency: asString(raw.currency_code ?? raw.currency, 'Backend currency'),
        id: asString(raw.id),
        isActive: Boolean(raw.is_active ?? true),
        isPrimary: Boolean(raw.is_primary),
        supplierId,
    };
}

function normalizeTaxProfile(raw: BackendRecord | null | undefined, supplierId: string): SupplierTaxProfile {
    return {
        isTaxExempt: Boolean(raw?.is_tax_exempt),
        supplierId,
        taxIdentifier: asString(raw?.tax_identifier, ''),
        taxType: asString(raw?.tax_type, 'Backend tax type pending'),
        vatIdentifier: asString(raw?.vat_identifier, ''),
        withholdingRate: asString(raw?.withholding_rate, 'Backend-owned withholding preview'),
    };
}

function normalizeFinanceDefaults(raw: BackendRecord, supplierId: string): SupplierFinanceDefaults {
    return {
        creditLimit: asString(raw.credit_limit, 'Backend-owned credit/payable limit'),
        defaultCurrency: asString(raw.default_currency_code ?? raw.default_currency_id, 'Backend currency'),
        expenseAccount: asString(raw.default_expense_account_name ?? raw.default_expense_account_id, 'Backend expense account'),
        payableAccount: asString(raw.default_payable_account_name ?? raw.default_payable_account_id, 'Backend payable account'),
        paymentTerm: asString(raw.default_payment_term_name ?? raw.default_payment_term_id, 'Backend payment term'),
        supplierId,
    };
}

function normalizeUserAccess(raw: BackendRecord, supplierId: string): SupplierUserAccess {
    return {
        email: asString(raw.email ?? raw.user_email, 'Not provided'),
        id: asString(raw.id),
        invitedAt: asString(raw.invited_at, ''),
        isPrimary: Boolean(raw.is_primary),
        lastLogin: asString(raw.last_login_at ?? raw.lastLogin, ''),
        status: asString(raw.status, 'active') as SupplierUserAccess['status'],
        supplierId,
        userName: asString(raw.user_name ?? raw.name ?? raw.user_id, 'Linked user'),
    };
}

function toBackendSupplierPayload(input: SupplierFormInput) {
    return {
        create_user_access: false,
        display_name: input.displayName || input.name,
        email: input.email || null,
        legal_name: input.legalName || null,
        metadata: {},
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

export const supplierApi = {
    activateSupplier: (supplierId: string) => supplierApi.changeStatus(supplierId, 'active'),
    blockSupplier: (supplierId: string) => supplierApi.changeStatus(supplierId, 'blocked'),
    changeStatus: (supplierId: string, status: SupplierStatus) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/status`, {
                    body: { status },
                    method: 'PATCH',
                });

                return { ...response, data: normalizeSupplier(response.data) };
            },
            () => mockResponse({ ...getSupplierById(supplierId), status }),
        ),
    createSupplier: (input: SupplierFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/supplier/suppliers', {
                    body: toBackendSupplierPayload(input),
                    method: 'POST',
                });

                return { ...response, data: normalizeSupplier(response.data) };
            },
            () => mockResponse({ ...suppliers[0], ...input, id: 'mock-supplier', userAccessStatus: 'none' as const }),
        ),
    createUserAccess: (supplierId: string, input: SupplierUserAccessCreateInput) =>
        withMockFallback(
            () =>
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
            () => mockResponse({ action: 'create-user-access', input, supplierId }),
        ),
    deactivateAddress: (addressId: string) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<BackendRecord>>(`/api/supplier/supplier-addresses/${addressId}`, {
                    body: { is_active: false },
                    method: 'PATCH',
                }),
            () => mockResponse({ action: 'deactivate-address', addressId }),
        ),
    deactivateBankAccount: (supplierId: string, bankAccountId: string) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/bank-accounts/${bankAccountId}`, {
                    body: { is_active: false },
                    method: 'PUT',
                }),
            () => mockResponse({ action: 'deactivate-bank-account', bankAccountId, supplierId }),
        ),
    deactivateContact: (contactId: string) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<BackendRecord>>(`/api/supplier/supplier-contacts/${contactId}`, {
                    body: { is_active: false },
                    method: 'PATCH',
                }),
            () => mockResponse({ action: 'deactivate-contact', contactId }),
        ),
    deactivateSupplier: (supplierId: string) => supplierApi.changeStatus(supplierId, 'inactive'),
    deactivateUserAccess: (supplierId: string, accessId: string, reason?: string) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/user-accesses/${accessId}/deactivate`, {
                    body: { reason: reason || null },
                    method: 'PATCH',
                }),
            () => mockResponse({ accessId, action: 'deactivate-user-access', reason, supplierId }),
        ),
    getFinanceDefaults: (supplierId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/finance-defaults`);

                return { ...response, data: normalizeFinanceDefaults(response.data, supplierId) };
            },
            () => mockResponse(supplierFinanceDefaults.find((defaults) => defaults.supplierId === supplierId) ?? supplierFinanceDefaults[0]),
        ),
    getPurchaseUsageSummary: (supplierId: string): Promise<ApiResponse<SupplierPurchaseUsageSummary>> =>
        mockResponse(supplierPurchaseUsage.find((usage) => usage.supplierId === supplierId) ?? {
            backendPreviewStatus: 'Mock backend purchase usage',
            lastPurchaseDate: 'No purchase usage returned yet',
            openPurchaseOrders: 'Backend-owned open PO count',
            payableBalance: 'Backend-owned AP balance',
            supplierId,
            totalPurchases: 'Backend-owned purchase total',
        }),
    getSupplier: (supplierId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}`);

                return { ...response, data: normalizeSupplier(response.data) };
            },
            () => mockResponse(getSupplierById(supplierId)),
        ),
    getSupplierActivity: (_supplierId: string): Promise<ApiCollectionResponse<SupplierAuditEntry>> => mockCollectionResponse(supplierAuditEntries),
    getTaxProfile: (supplierId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord | null>>(`/api/supplier/suppliers/${supplierId}/tax-profile`);

                return { ...response, data: normalizeTaxProfile(response.data, supplierId) };
            },
            () => mockResponse(supplierTaxProfiles.find((profile) => profile.supplierId === supplierId) ?? supplierTaxProfiles[0]),
        ),
    linkUserAccess: (supplierId: string, input: SupplierUserAccessLinkInput) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/link-user`, {
                    body: {
                        access_type: input.accessType || null,
                        is_primary: input.isPrimary ?? false,
                        user_id: Number(input.userId),
                    },
                    method: 'POST',
                }),
            () => mockResponse({ action: 'link-user-access', input, supplierId }),
        ),
    listAddresses: (supplierId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/supplier/supplier-addresses', { query: { supplier_id: supplierId } });

                return { ...response, data: response.data.map(normalizeAddress) };
            },
            () => mockCollectionResponse(supplierAddresses.filter((address) => address.supplierId === supplierId)),
            [401, 403, 404, 419, 422],
        ),
    listBankAccounts: (supplierId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/bank-accounts`);

                return { ...response, data: response.data.map((item) => normalizeBankAccount(item, supplierId)) };
            },
            () => mockCollectionResponse(supplierBankAccounts.filter((account) => account.supplierId === supplierId)),
        ),
    listContacts: (supplierId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/supplier/supplier-contacts', { query: { supplier_id: supplierId } });

                return { ...response, data: response.data.map(normalizeContact) };
            },
            () => mockCollectionResponse(supplierContacts.filter((contact) => contact.supplierId === supplierId)),
            [401, 403, 404, 419, 422],
        ),
    listSuppliers: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/supplier/suppliers');

                return { ...response, data: response.data.map(normalizeSupplier) };
            },
            () => mockCollectionResponse(suppliers),
        ),
    listUserAccess: (supplierId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/user-accesses`);

                return { ...response, data: response.data.map((item) => normalizeUserAccess(item, supplierId)) };
            },
            () => mockCollectionResponse(supplierUserAccess.filter((access) => access.supplierId === supplierId)),
        ),
    setPrimaryBankAccount: (supplierId: string, bankAccountId: string) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/bank-accounts/${bankAccountId}`, {
                    body: { is_primary: true },
                    method: 'PUT',
                }),
            () => mockResponse({ action: 'set-primary-bank-account', bankAccountId, supplierId }),
        ),
    unblockSupplier: (supplierId: string) => supplierApi.changeStatus(supplierId, 'active'),
    unlinkUserAccess: (supplierId: string, accessId: string) =>
        withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/user-accesses/${accessId}`, { method: 'DELETE' }),
            () => mockResponse({ accessId, action: 'unlink-user-access', supplierId }),
        ),
    updateFinanceDefaults: (supplierId: string, input: unknown) =>
        withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/supplier/suppliers/${supplierId}/finance-defaults`, { body: input, method: 'PUT' }),
            () => mockResponse({ input, supplierId }),
        ),
    updateSupplier: (supplierId: string, input: SupplierFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}`, {
                    body: toBackendSupplierPayload(input),
                    method: 'PUT',
                });

                return { ...response, data: normalizeSupplier(response.data) };
            },
            () => mockResponse({ ...getSupplierById(supplierId), ...input }),
        ),
    updateTaxProfile: (supplierId: string, input: SupplierTaxProfile) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/tax-profile`, {
                    body: {
                        is_tax_exempt: input.isTaxExempt,
                        tax_identifier: input.taxIdentifier || null,
                        tax_type: input.taxType || null,
                        vat_identifier: input.vatIdentifier || null,
                    },
                    method: 'PUT',
                });

                return { ...response, data: normalizeTaxProfile(response.data, supplierId) };
            },
            () => mockResponse({ ...input, supplierId }),
        ),
    upsertAddress: (supplierId: string, input: SupplierAddress) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/supplier/supplier-addresses', {
                    body: {
                        address_line1: input.line1,
                        address_line2: input.line2 || null,
                        city: input.city,
                        country_id: null,
                        is_default: input.isDefault,
                        postal_code: input.postalCode || '00000',
                        supplier_id: supplierId,
                        type: input.type,
                    },
                    method: 'POST',
                });

                return { ...response, data: normalizeAddress(response.data) };
            },
            () => mockResponse({ ...input, supplierId }),
        ),
    upsertBankAccount: (supplierId: string, input: SupplierBankAccount) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/supplier/suppliers/${supplierId}/bank-accounts`, {
                    body: {
                        account_name: input.accountName,
                        account_number: input.accountNumber,
                        bank_name: input.bankName,
                        branch_name: input.branchName || null,
                        is_active: input.isActive,
                        is_primary: input.isPrimary,
                    },
                    method: 'POST',
                });

                return { ...response, data: normalizeBankAccount(response.data, supplierId) };
            },
            () => mockResponse({ ...input, supplierId }),
        ),
    upsertContact: (supplierId: string, input: SupplierContact) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/supplier/supplier-contacts', {
                    body: {
                        designation: input.designation || null,
                        email: input.email || null,
                        is_primary: input.isPrimary,
                        name: input.name,
                        phone: input.phone || null,
                        supplier_id: supplierId,
                    },
                    method: 'POST',
                });

                return { ...response, data: normalizeContact(response.data) };
            },
            () => mockResponse({ ...input, supplierId }),
        ),
};
