import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    customerAddresses,
    customerContacts,
    customerCreditProfiles,
    customerFinanceDefaults,
    customerTaxProfiles,
    customerUserAccess,
    customerVehicles,
    customers,
    getCustomerById,
} from '../mock/customerMock';
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

type BackendCustomer = Record<string, unknown>;

const CUSTOMER_API_MODE = import.meta.env.VITE_CUSTOMER_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return CUSTOMER_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (CUSTOMER_API_MODE === 'real') {
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

function normalizeStatus(value: unknown): CustomerStatus {
    const status = asString(value, 'pending').toLowerCase();

    if (['active', 'blocked', 'inactive', 'pending'].includes(status)) {
        return status as CustomerStatus;
    }

    return status === 'draft' ? 'pending' : 'active';
}

function normalizeCustomer(raw: BackendCustomer): Customer {
    return {
        code: asString(raw.customer_code ?? raw.code, 'CUS-MOCK'),
        contactPerson: asString(raw.contact_person ?? raw.display_name ?? raw.customer_name ?? raw.name, 'Not provided'),
        createdAt: asString(raw.created_at ?? raw.createdAt, 'Backend timestamp pending'),
        email: asString(raw.email, 'Not provided'),
        id: asString(raw.id),
        industry: asString(raw.customer_type ?? raw.industry, 'Customer'),
        name: asString(raw.customer_name ?? raw.name ?? raw.display_name, 'Unnamed customer'),
        phone: asString(raw.phone ?? raw.mobile, 'Not provided'),
        status: normalizeStatus(raw.status),
        taxNumber: asString(raw.tax_number ?? raw.vat_number ?? raw.taxNumber, ''),
        userAccessStatus: raw.user_access_status === 'linked' || raw.has_user_access === true ? 'linked' : 'none',
    };
}

function normalizeContact(raw: BackendCustomer): CustomerContact {
    return {
        customerId: asString(raw.customer_id),
        email: asString(raw.email, 'Not provided'),
        id: asString(raw.id),
        isPrimary: Boolean(raw.is_primary),
        name: asString(raw.contact_name ?? raw.name, 'Unnamed contact'),
        phone: asString(raw.phone ?? raw.mobile, 'Not provided'),
        role: asString(raw.designation ?? raw.role ?? raw.department, 'Contact'),
    };
}

function normalizeAddress(raw: BackendCustomer): CustomerAddress {
    return {
        city: asString(raw.city, 'Not provided'),
        country: asString(raw.country_name ?? raw.country, 'Not provided'),
        customerId: asString(raw.customer_id),
        id: asString(raw.id),
        isPrimary: Boolean(raw.is_primary ?? raw.is_primary_billing ?? raw.is_primary_shipping),
        line1: asString(raw.address_line1 ?? raw.address_line_1 ?? raw.line1, 'Not provided'),
        line2: asString(raw.address_line2 ?? raw.address_line_2 ?? raw.line2, ''),
        type: asString(raw.address_type ?? raw.type, 'billing') as CustomerAddress['type'],
    };
}

function normalizeVehicle(raw: BackendCustomer): CustomerVehicle {
    return {
        customerId: asString(raw.customer_id),
        id: asString(raw.id),
        make: asString(raw.make ?? raw.vehicle_make, 'Vehicle'),
        model: asString(raw.model ?? raw.vehicle_model, ''),
        plateNumber: asString(raw.plate_number ?? raw.registration_number ?? raw.vehicle_number, 'Not provided'),
        status: Boolean(raw.is_active ?? true) ? 'Active' : 'Inactive',
        vin: asString(raw.vin, ''),
        year: asString(raw.year ?? raw.model_year, ''),
    };
}

function normalizeTaxProfile(raw: BackendCustomer, customerId: string): CustomerTaxProfile {
    return {
        customerId,
        exemptionReason: asString(raw.exemption_certificate_reference ?? raw.exemptionReason, ''),
        taxGroup: asString(raw.tax_group_name ?? raw.tax_group ?? raw.tax_group_id, 'Backend tax group'),
        taxRegistrationNumber: asString(raw.tax_registration_number ?? raw.vat_number, ''),
        taxStatus: raw.tax_exempt === true ? 'exempt' : asString(raw.tax_registration_number ?? raw.vat_number, '') ? 'registered' : 'unregistered',
    };
}

function normalizeCreditProfile(raw: BackendCustomer, customerId: string): CustomerCreditProfile {
    return {
        agingSummary: asString(raw.aging_summary, 'Backend aging preview pending'),
        backendPreviewStatus: 'Backend credit-check response',
        creditLimit: asString(raw.credit_limit, 'Backend-owned credit limit'),
        creditStatus: asString(raw.credit_status ?? raw.status, 'Backend credit status pending'),
        customerId,
        outstandingBalance: asString(raw.outstanding_balance, 'Backend-owned outstanding balance'),
        paymentTerms: asString(raw.payment_terms ?? raw.credit_days, 'Backend payment terms pending'),
    };
}

function normalizeFinanceDefaults(raw: BackendCustomer, customerId: string): CustomerFinanceDefaults {
    return {
        arAccount: asString(raw.default_receivable_account_name ?? raw.default_receivable_account_id, 'Backend AR account'),
        costCenter: asString(raw.cost_center_name ?? raw.cost_center_id, 'Backend cost center'),
        currency: asString(raw.default_currency_code ?? raw.default_currency_id, 'Backend currency'),
        customerId,
        paymentTerm: asString(raw.default_payment_term_name ?? raw.default_payment_term_id, 'Backend payment term'),
        revenueAccount: asString(raw.default_income_account_name ?? raw.default_income_account_id, 'Backend revenue account'),
    };
}

function normalizeUserAccess(raw: BackendCustomer, customerId: string): CustomerUserAccess {
    return {
        customerId,
        email: asString(raw.email ?? raw.user_email, 'Not provided'),
        id: asString(raw.id),
        lastLogin: asString(raw.last_login_at ?? raw.lastLogin, ''),
        status: asString(raw.status, 'active') as CustomerUserAccess['status'],
        userName: asString(raw.user_name ?? raw.name ?? raw.user_id, 'Linked user'),
    };
}

function toBackendCustomerPayload(input: CustomerFormInput) {
    return {
        create_user: false,
        customer_code: input.code,
        customer_name: input.name,
        customer_type: input.industry,
        display_name: input.name,
        email: input.email || null,
        metadata: {
            contact_person: input.contactPerson,
        },
        phone: input.phone || null,
        tax_number: input.taxNumber || null,
    };
}

export const customerApi = {
    activateCustomer: (customerId: string) => customerApi.changeStatus(customerId, 'active'),
    blockCustomer: (customerId: string) => customerApi.changeStatus(customerId, 'blocked'),
    changeStatus: (customerId: string, status: CustomerStatus) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>(`/api/customer/customers/${customerId}/status`, {
                    body: { status },
                    method: 'PATCH',
                });

                return { ...response, data: normalizeCustomer(response.data) };
            },
            () => mockResponse({ ...getCustomerById(customerId), status }),
        ),
    createCustomer: (input: CustomerFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>('/api/customer/customers', {
                    body: toBackendCustomerPayload(input),
                    method: 'POST',
                });

                return { ...response, data: normalizeCustomer(response.data) };
            },
            () => mockResponse({ ...customers[0], ...input, id: 'mock-customer' }),
        ),
    deactivateCustomer: (customerId: string) => customerApi.changeStatus(customerId, 'inactive'),
    deactivateUserAccess: (customerId: string, accessId: string, reason?: string) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<unknown>>(`/api/customer/customers/${customerId}/user-accesses/${accessId}/deactivate`, {
                    body: { reason: reason || null },
                    method: 'PATCH',
                }),
            () => mockResponse({ accessId, action: 'deactivate-user-access', customerId, reason }),
        ),
    getCreditProfile: (customerId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>(`/api/customer/customers/${customerId}/credit-check`, { method: 'POST' });

                return { ...response, data: normalizeCreditProfile(response.data, customerId) };
            },
            () => mockResponse(customerCreditProfiles.find((profile) => profile.customerId === customerId) ?? customerCreditProfiles[0]),
        ),
    getCustomer: (customerId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>(`/api/customer/customers/${customerId}`);

                return { ...response, data: normalizeCustomer(response.data) };
            },
            () => mockResponse(getCustomerById(customerId)),
        ),
    getFinanceDefaults: (customerId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>(`/api/customer/customers/${customerId}/finance-defaults`);

                return { ...response, data: normalizeFinanceDefaults(response.data, customerId) };
            },
            () => mockResponse(customerFinanceDefaults.find((defaults) => defaults.customerId === customerId) ?? customerFinanceDefaults[0]),
        ),
    getTaxProfile: (customerId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>(`/api/customer/customers/${customerId}/tax-profile`);

                return { ...response, data: normalizeTaxProfile(response.data, customerId) };
            },
            () => mockResponse(customerTaxProfiles.find((profile) => profile.customerId === customerId) ?? customerTaxProfiles[0]),
        ),
    linkUserAccess: (customerId: string, input: CustomerUserAccessLinkInput) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<unknown>>(`/api/customer/customers/${customerId}/user-accesses/link-existing`, {
                    body: {
                        access_role: input.accessRole || null,
                        invited: input.invited ?? false,
                        is_primary: input.isPrimary ?? false,
                        user_id: Number(input.userId),
                    },
                    method: 'POST',
                }),
            () => mockResponse({ customerId, ...input, action: 'link-user-access' }),
        ),
    listAddresses: (customerId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendCustomer>>('/api/customer/customer-addresses', { query: { customer_id: customerId } });

                return { ...response, data: response.data.map(normalizeAddress) };
            },
            () => mockCollectionResponse(customerAddresses.filter((address) => address.customerId === customerId)),
            [401, 403, 404, 419, 422],
        ),
    listContacts: (customerId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendCustomer>>('/api/customer/customer-contacts', { query: { customer_id: customerId } });

                return { ...response, data: response.data.map(normalizeContact) };
            },
            () => mockCollectionResponse(customerContacts.filter((contact) => contact.customerId === customerId)),
            [401, 403, 404, 419, 422],
        ),
    listCustomers: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendCustomer>>('/api/customer/customers');

                return { ...response, data: response.data.map(normalizeCustomer) };
            },
            () => mockCollectionResponse(customers),
        ),
    listUserAccess: (customerId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendCustomer>>(`/api/customer/customers/${customerId}/user-accesses`);

                return { ...response, data: response.data.map((item) => normalizeUserAccess(item, customerId)) };
            },
            () => mockCollectionResponse(customerUserAccess.filter((access) => access.customerId === customerId)),
        ),
    listVehicles: (customerId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendCustomer>>('/api/customer/customer-vehicles', { query: { customer_id: customerId } });

                return { ...response, data: response.data.map(normalizeVehicle) };
            },
            () => mockCollectionResponse(customerVehicles.filter((vehicle) => vehicle.customerId === customerId)),
            [401, 403, 404, 419, 422],
        ),
    unblockCustomer: (customerId: string) => customerApi.changeStatus(customerId, 'active'),
    updateCreditProfile: (customerId: string, input: CustomerCreditProfile) =>
        withMockFallback<ApiResponse<unknown>>(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>(`/api/customer/customers/${customerId}`, {
                    body: {
                        credit_profile: {
                            credit_days: Number.parseInt(input.paymentTerms, 10) || null,
                            credit_limit: Number.parseFloat(input.creditLimit.replace(/[^\d.]/g, '')) || null,
                            credit_hold: input.creditStatus.toLowerCase().includes('hold'),
                        },
                    },
                    method: 'PATCH',
                });

                return { ...response, data: normalizeCustomer(response.data) };
            },
            () => mockResponse({ customerId, input }),
        ),
    updateCustomer: (customerId: string, input: CustomerFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>(`/api/customer/customers/${customerId}`, {
                    body: toBackendCustomerPayload(input),
                    method: 'PUT',
                });

                return { ...response, data: normalizeCustomer(response.data) };
            },
            () => mockResponse({ ...getCustomerById(customerId), ...input }),
        ),
    updateFinanceDefaults: (customerId: string, input: unknown) =>
        withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/customer/customers/${customerId}/finance-defaults`, { body: input, method: 'PUT' }),
            () => mockResponse({ customerId, input }),
        ),
    updateTaxProfile: (customerId: string, input: CustomerTaxProfile) =>
        withMockFallback<ApiResponse<unknown>>(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>(`/api/customer/customers/${customerId}`, {
                    body: {
                        tax_profile: {
                            exemption_certificate_reference: input.exemptionReason || null,
                            tax_exempt: input.taxStatus === 'exempt',
                            tax_registration_number: input.taxRegistrationNumber || null,
                        },
                    },
                    method: 'PATCH',
                });

                return { ...response, data: normalizeCustomer(response.data) };
            },
            () => mockResponse({ ...input, customerId }),
        ),
    upsertAddress: (customerId: string, input: CustomerAddress) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>('/api/customer/customer-addresses', {
                    body: {
                        address_line1: input.line1,
                        address_line2: input.line2 || null,
                        city: input.city,
                        country_id: 1,
                        customer_id: customerId,
                        is_default: input.isPrimary,
                        postal_code: '00000',
                        type: input.type,
                    },
                    method: 'POST',
                });

                return { ...response, data: normalizeAddress(response.data) };
            },
            () => mockResponse({ ...input, customerId }),
        ),
    upsertContact: (customerId: string, input: CustomerContact) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendCustomer>>('/api/customer/customer-contacts', {
                    body: {
                        customer_id: customerId,
                        email: input.email || null,
                        is_primary: input.isPrimary,
                        name: input.name,
                        phone: input.phone || null,
                        role: input.role || null,
                    },
                    method: 'POST',
                });

                return { ...response, data: normalizeContact(response.data) };
            },
            () => mockResponse({ ...input, customerId }),
        ),
    upsertVehicle: (customerId: string, input: CustomerVehicle) =>
        withMockFallback(
            () =>
                httpClient<ApiResponse<BackendCustomer>>('/api/customer/customer-vehicles', {
                    body: {
                        customer_id: customerId,
                        is_active: input.status.toLowerCase() === 'active',
                        is_current: true,
                        vehicle_id: input.id,
                    },
                    method: 'POST',
                }).then((response) => ({ ...response, data: normalizeVehicle(response.data) })),
            () => mockResponse({ ...input, customerId }),
        ),
};
