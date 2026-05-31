import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type {
    Customer,
    CustomerAddress,
    CustomerContact,
    CustomerCreditProfile,
    CustomerFinanceDefaults,
    CustomerFormInput,
    CustomerStatus,
    CustomerTaxProfile,
    CustomerUserAccess,
    CustomerUserAccessLinkInput,
    CustomerVehicle,
} from '../types/customer.types';

type BackendRecord = Record<string, unknown>;

type CustomerListQuery = {
    page?: number;
    perPage?: number;
    search?: string;
    status?: CustomerStatus;
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

function normalizeStatus(value: unknown): CustomerStatus {
    const status = asString(value, 'pending').toLowerCase();

    if (status === 'active' || status === 'blocked' || status === 'inactive') {
        return status;
    }

    return 'pending';
}

function normalizeCustomer(raw: BackendRecord): Customer {
    const metadata = asRecord(raw.metadata);
    const userAccounts = Array.isArray(raw.user_accounts) ? raw.user_accounts : [];

    return {
        code: asString(raw.customer_code ?? raw.code),
        contactPerson: asString(metadata.contact_person ?? raw.contact_person ?? raw.display_name ?? raw.customer_name ?? raw.name),
        createdAt: asString(raw.created_at ?? raw.createdAt),
        email: asString(raw.email),
        id: asString(raw.id),
        industry: asString(raw.customer_type ?? raw.industry),
        name: asString(raw.customer_name ?? raw.name ?? raw.display_name),
        notes: asString(raw.notes),
        phone: asString(raw.phone ?? raw.mobile),
        status: normalizeStatus(raw.status),
        taxNumber: asOptionalString(raw.tax_number ?? raw.vat_number ?? raw.taxNumber),
        userAccessStatus: raw.user_access_status === 'linked' || raw.has_user_access === true || userAccounts.length > 0 ? 'linked' : 'none',
    };
}

function normalizeContact(raw: BackendRecord): CustomerContact {
    return {
        customerId: asString(raw.customer_id),
        email: asString(raw.email),
        id: asString(raw.id),
        isPrimary: asBoolean(raw.is_primary),
        name: asString(raw.contact_name ?? raw.name),
        phone: asString(raw.phone ?? raw.mobile),
        role: asString(raw.designation ?? raw.role ?? raw.department),
    };
}

function normalizeAddress(raw: BackendRecord): CustomerAddress {
    return {
        city: asString(raw.city),
        country: asString(raw.country_name ?? raw.country),
        customerId: asString(raw.customer_id),
        id: asString(raw.id),
        isPrimary: asBoolean(raw.is_primary ?? raw.is_primary_billing ?? raw.is_primary_shipping),
        line1: asString(raw.address_line_1 ?? raw.address_line1 ?? raw.line1),
        line2: asOptionalString(raw.address_line_2 ?? raw.address_line2 ?? raw.line2),
        postalCode: asString(raw.postal_code),
        type: normalizeAddressType(raw.address_type ?? raw.type),
    };
}

function normalizeAddressType(value: unknown): CustomerAddress['type'] {
    const type = asString(value, 'billing').toLowerCase();

    if (type === 'delivery' || type === 'service') {
        return type;
    }

    return 'billing';
}

function normalizeVehicle(raw: BackendRecord): CustomerVehicle {
    return {
        customerId: asString(raw.customer_id),
        id: asString(raw.id),
        make: asString(raw.make ?? raw.vehicle_make),
        model: asString(raw.model ?? raw.vehicle_model),
        plateNumber: asString(raw.plate_number ?? raw.registration_number ?? raw.vehicle_number),
        status: asBoolean(raw.is_active, true) ? 'Active' : 'Inactive',
        vin: asOptionalString(raw.vin),
        year: asString(raw.year ?? raw.model_year),
    };
}

function normalizeTaxProfile(raw: BackendRecord, customerId: string): CustomerTaxProfile {
    return {
        customerId,
        exemptionReason: asOptionalString(raw.exemption_certificate_reference ?? raw.exemptionReason),
        taxGroup: asString(raw.tax_group_name ?? raw.tax_group ?? raw.tax_group_id),
        taxRegistrationNumber: asOptionalString(raw.tax_registration_number ?? raw.vat_number),
        taxStatus: raw.tax_exempt === true ? 'exempt' : asOptionalString(raw.tax_registration_number ?? raw.vat_number) ? 'registered' : 'unregistered',
    };
}

function normalizeCreditProfile(raw: BackendRecord, customerId: string): CustomerCreditProfile {
    return {
        agingSummary: asString(raw.aging_summary, 'Not available from backend'),
        backendPreviewStatus: asString(raw.credit_status ?? raw.status, 'Backend credit-check response'),
        creditLimit: asString(raw.credit_limit, 'Not configured'),
        creditStatus: asString(raw.credit_status ?? raw.status, 'Not available from backend'),
        customerId,
        outstandingBalance: asString(raw.outstanding_balance, 'Not available from backend'),
        paymentTerms: asString(raw.payment_terms ?? raw.credit_days, 'Not configured'),
    };
}

function normalizeFinanceDefaults(raw: BackendRecord, customerId: string): CustomerFinanceDefaults {
    return {
        arAccount: asString(raw.default_receivable_account_name ?? raw.default_receivable_account_id),
        costCenter: asString(raw.cost_center_name ?? raw.cost_center_id),
        currency: asString(raw.default_currency_code ?? raw.default_currency_id),
        customerId,
        paymentTerm: asString(raw.default_payment_term_name ?? raw.default_payment_term_id),
        revenueAccount: asString(raw.default_income_account_name ?? raw.default_income_account_id),
    };
}

function normalizeUserAccess(raw: BackendRecord, customerId: string): CustomerUserAccess {
    return {
        customerId,
        email: asString(raw.email ?? raw.user_email),
        id: asString(raw.id),
        lastLogin: asOptionalString(raw.last_login_at ?? raw.lastLogin),
        status: normalizeUserAccessStatus(raw.access_status ?? raw.status),
        userName: asString(raw.user_name ?? raw.name ?? raw.user_id, 'Linked user'),
    };
}

function normalizeUserAccessStatus(value: unknown): CustomerUserAccess['status'] {
    const status = asString(value, 'active').toLowerCase();

    if (status === 'inactive' || status === 'invited') {
        return status;
    }

    return 'active';
}

function toBackendCustomerPayload(input: CustomerFormInput): BackendRecord {
    return {
        create_user: false,
        customer_code: input.code,
        customer_name: input.name,
        customer_type: input.industry || null,
        display_name: input.name,
        email: input.email || null,
        metadata: {
            contact_person: input.contactPerson || null,
        },
        notes: input.notes || null,
        phone: input.phone || null,
        tax_number: input.taxNumber || null,
    };
}

function toBackendContactPayload(customerId: string, input: CustomerContact): BackendRecord {
    return {
        contact_name: input.name,
        customer_id: Number(customerId),
        designation: input.role || null,
        email: input.email || null,
        is_primary: input.isPrimary,
        phone: input.phone || null,
    };
}

function toBackendAddressPayload(customerId: string, input: CustomerAddress): BackendRecord {
    return {
        address_line_1: input.line1,
        address_line_2: input.line2 || null,
        address_type: input.type,
        city: input.city,
        country_name: input.country || null,
        customer_id: Number(customerId),
        is_primary: input.isPrimary,
        postal_code: input.postalCode || '00000',
    };
}

export const customerApi = {
    activateCustomer: (customerId: string) => customerApi.changeStatus(customerId, 'active'),
    blockCustomer: (customerId: string) => customerApi.changeStatus(customerId, 'blocked'),
    changeStatus: async (customerId: string, status: CustomerStatus): Promise<ApiResponse<Customer>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/customer/customers/${customerId}/status`, {
            body: { status },
            method: 'PATCH',
        });

        return { ...response, data: normalizeCustomer(response.data) };
    },
    createCustomer: async (input: CustomerFormInput): Promise<ApiResponse<Customer>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/customer/customers', {
            body: toBackendCustomerPayload(input),
            method: 'POST',
        });

        return { ...response, data: normalizeCustomer(response.data) };
    },
    deactivateCustomer: (customerId: string) => customerApi.changeStatus(customerId, 'inactive'),
    deactivateUserAccess: (customerId: string, accessId: string, reason?: string) =>
        httpClient<ApiResponse<unknown>>(`/api/customer/customers/${customerId}/user-accesses/${accessId}/deactivate`, {
            body: { reason: reason || null },
            method: 'PATCH',
        }),
    getCreditProfile: async (customerId: string): Promise<ApiResponse<CustomerCreditProfile>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/customer/customers/${customerId}/credit-check`, { method: 'POST' });

        return { ...response, data: normalizeCreditProfile(response.data, customerId) };
    },
    getCustomer: async (customerId: string): Promise<ApiResponse<Customer>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/customer/customers/${customerId}`);

        return { ...response, data: normalizeCustomer(response.data) };
    },
    getFinanceDefaults: async (customerId: string): Promise<ApiResponse<CustomerFinanceDefaults>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/customer/customers/${customerId}/finance-defaults`);

        return { ...response, data: normalizeFinanceDefaults(response.data, customerId) };
    },
    getTaxProfile: async (customerId: string): Promise<ApiResponse<CustomerTaxProfile>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/customer/customers/${customerId}/tax-profile`);

        return { ...response, data: normalizeTaxProfile(response.data, customerId) };
    },
    linkUserAccess: (customerId: string, input: CustomerUserAccessLinkInput) =>
        httpClient<ApiResponse<unknown>>(`/api/customer/customers/${customerId}/user-accesses/link-existing`, {
            body: {
                access_role: input.accessRole || null,
                invited: input.invited ?? false,
                is_primary: input.isPrimary ?? false,
                user_id: Number(input.userId),
            },
            method: 'POST',
        }),
    listAddresses: async (customerId: string): Promise<ApiCollectionResponse<CustomerAddress>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/customer/customer-addresses', { query: { customer_id: customerId } });

        return { ...response, data: response.data.map(normalizeAddress) };
    },
    listContacts: async (customerId: string): Promise<ApiCollectionResponse<CustomerContact>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/customer/customer-contacts', { query: { customer_id: customerId } });

        return { ...response, data: response.data.map(normalizeContact) };
    },
    listCustomers: async (query: CustomerListQuery = {}): Promise<ApiCollectionResponse<Customer>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/customer/customers', {
            query: {
                page: query.page,
                per_page: query.perPage ?? 50,
                search: query.search,
                status: query.status,
            },
        });

        return { ...response, data: response.data.map(normalizeCustomer) };
    },
    listUserAccess: async (customerId: string): Promise<ApiCollectionResponse<CustomerUserAccess>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/customer/customers/${customerId}/user-accesses`);

        return { ...response, data: response.data.map((item) => normalizeUserAccess(item, customerId)) };
    },
    listVehicles: async (customerId: string): Promise<ApiCollectionResponse<CustomerVehicle>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/customer/customer-vehicles', { query: { customer_id: customerId } });

        return { ...response, data: response.data.map(normalizeVehicle) };
    },
    unblockCustomer: (customerId: string) => customerApi.changeStatus(customerId, 'active'),
    updateCustomer: async (customerId: string, input: CustomerFormInput): Promise<ApiResponse<Customer>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/customer/customers/${customerId}`, {
            body: toBackendCustomerPayload(input),
            method: 'PUT',
        });

        return { ...response, data: normalizeCustomer(response.data) };
    },
    updateFinanceDefaults: (customerId: string, input: unknown) =>
        httpClient<ApiResponse<unknown>>(`/api/customer/customers/${customerId}/finance-defaults`, { body: input, method: 'PUT' }),
    upsertAddress: async (customerId: string, input: CustomerAddress): Promise<ApiResponse<CustomerAddress>> => {
        const hasExistingId = input.id.trim() !== '';
        const response = await httpClient<ApiResponse<BackendRecord>>(
            hasExistingId ? `/api/customer/customer-addresses/${input.id}` : '/api/customer/customer-addresses',
            {
                body: toBackendAddressPayload(customerId, input),
                method: hasExistingId ? 'PATCH' : 'POST',
            },
        );

        return { ...response, data: normalizeAddress(response.data) };
    },
    upsertContact: async (customerId: string, input: CustomerContact): Promise<ApiResponse<CustomerContact>> => {
        const hasExistingId = input.id.trim() !== '';
        const response = await httpClient<ApiResponse<BackendRecord>>(
            hasExistingId ? `/api/customer/customer-contacts/${input.id}` : '/api/customer/customer-contacts',
            {
                body: toBackendContactPayload(customerId, input),
                method: hasExistingId ? 'PATCH' : 'POST',
            },
        );

        return { ...response, data: normalizeContact(response.data) };
    },
    upsertVehicle: async (customerId: string, input: CustomerVehicle): Promise<ApiResponse<CustomerVehicle>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/customer/customer-vehicles', {
            body: {
                customer_id: Number(customerId),
                is_active: input.status.toLowerCase() === 'active',
                is_current: true,
                vehicle_id: Number(input.id),
            },
            method: 'POST',
        });

        return { ...response, data: normalizeVehicle(response.data) };
    },
};
