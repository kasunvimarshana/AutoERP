import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { createLocallyFilteredLookupLoader, createQueryCachedLookupLoader } from '@/shared/api/lookupCache';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { PartyVehiclePayload } from '@/shared/types/partyVehicle';
import { clearVehicleOwnershipCurrent, createVehicleOwnership, endVehicleOwnership, getVehicleOwnership, listVehicleOwnerships, setVehicleOwnershipCurrent, updateVehicleOwnership } from '@/modules/vehicle/vehicleOwnershipApi';
import type {
    Customer,
    CustomerAddress,
    CustomerAddressPayload,
    CustomerBankAccount,
    CustomerBankAccountPayload,
    CustomerCategory,
    CustomerContact,
    CustomerContactPayload,
    CustomerCreditProfile,
    CustomerDocument,
    CustomerDocumentPayload,
    CustomerPayload,
    CustomerStatusHistory,
    CustomerSummary,
    CustomerWithRelationsPayload,
} from './customerTypes';

export const listCustomers = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<CustomerSummary>>(endpoints.customers, { params, signal }).then((response) => response.data);

export const getCustomer = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<Customer>>(`${endpoints.customers}/${id}`, { signal }).then((response) => response.data.data);

export const createCustomer = (payload: CustomerPayload) =>
    apiClient.post<ApiResource<Customer>>(endpoints.customers, payload).then((response) => response.data.data);

export const createCustomerWithRelations = (payload: CustomerWithRelationsPayload) =>
    apiClient.post<ApiResource<Customer>>(`${endpoints.customers}/with-relations`, payload).then((response) => response.data.data);

export const generateCustomerCode = (signal?: AbortSignal) =>
    apiClient.post<ApiResource<{ code: string }>>(`${endpoints.customers}/code-reservations`, undefined, { signal })
        .then((response) => response.data.data.code);

export const updateCustomer = (id: number, payload: Partial<CustomerPayload>) =>
    apiClient.put<ApiResource<Customer>>(`${endpoints.customers}/${id}`, payload).then((response) => response.data.data);

export const deleteCustomer = (id: number) => apiClient.delete(`${endpoints.customers}/${id}`);

export const setCustomerActive = (id: number, active: boolean) =>
    apiClient.patch<ApiResource<Customer>>(`${endpoints.customers}/${id}/${active ? 'activate' : 'deactivate'}`)
        .then((response) => response.data.data);

export const changeCustomerStatus = (id: number, status: string, reason?: string) =>
    apiClient.patch<ApiResource<Customer>>(`${endpoints.customers}/${id}/status`, { status, reason })
        .then((response) => response.data.data);

export function searchCustomers(params: LookupLoadParams, kind = 'active'): Promise<LookupResult<CustomerSummary>> {
    const loader = createQueryCachedLookupLoader<CustomerSummary>({
        key: `lookup:customers:${kind}`,
        load: (lookupParams) => requestLookup<CustomerSummary>(`${endpoints.customers}/lookup/${kind}`, lookupParams),
    });

    return loader(params);
}

export function searchCustomerCategories(params: LookupLoadParams): Promise<LookupResult<CustomerCategory>> {
    const loader = createLocallyFilteredLookupLoader<CustomerCategory>({
        key: 'lookup:customer-categories',
        load: (lookupParams) => requestLookup<CustomerCategory>(`${endpoints.customerCategories}/lookup`, lookupParams),
    });

    return loader(params);
}

export function searchCurrencies(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const loader = createLocallyFilteredLookupLoader<NamedResource>({
        key: 'lookup:currencies',
        load: (lookupParams) => requestLookup<NamedResource>(endpoints.currencies, lookupParams),
    });

    return loader(params);
}

const relationPath = (customerId: number, relation: string) => `${endpoints.customers}/${customerId}/${relation}`;

export const listCustomerContacts = (customerId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<CustomerContact>>(relationPath(customerId, 'contacts'), { params, signal }).then((response) => response.data);
export const createCustomerContact = (customerId: number, payload: CustomerContactPayload) =>
    apiClient.post<ApiResource<CustomerContact>>(relationPath(customerId, 'contacts'), payload).then((response) => response.data.data);
export const updateCustomerContact = (customerId: number, id: number, payload: CustomerContactPayload) =>
    apiClient.put<ApiResource<CustomerContact>>(`${relationPath(customerId, 'contacts')}/${id}`, payload).then((response) => response.data.data);
export const deleteCustomerContact = (customerId: number, id: number) => apiClient.delete(`${relationPath(customerId, 'contacts')}/${id}`);

export const listCustomerAddresses = (customerId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<CustomerAddress>>(relationPath(customerId, 'addresses'), { params, signal }).then((response) => response.data);
export const createCustomerAddress = (customerId: number, payload: CustomerAddressPayload) =>
    apiClient.post<ApiResource<CustomerAddress>>(relationPath(customerId, 'addresses'), payload).then((response) => response.data.data);
export const updateCustomerAddress = (customerId: number, id: number, payload: CustomerAddressPayload) =>
    apiClient.put<ApiResource<CustomerAddress>>(`${relationPath(customerId, 'addresses')}/${id}`, payload).then((response) => response.data.data);
export const deleteCustomerAddress = (customerId: number, id: number) => apiClient.delete(`${relationPath(customerId, 'addresses')}/${id}`);

export const listCustomerBankAccounts = (customerId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<CustomerBankAccount>>(relationPath(customerId, 'bank-accounts'), { params, signal }).then((response) => response.data);
export const createCustomerBankAccount = (customerId: number, payload: CustomerBankAccountPayload) =>
    apiClient.post<ApiResource<CustomerBankAccount>>(relationPath(customerId, 'bank-accounts'), payload).then((response) => response.data.data);
export const updateCustomerBankAccount = (customerId: number, id: number, payload: CustomerBankAccountPayload) =>
    apiClient.put<ApiResource<CustomerBankAccount>>(`${relationPath(customerId, 'bank-accounts')}/${id}`, payload).then((response) => response.data.data);
export const deleteCustomerBankAccount = (customerId: number, id: number) => apiClient.delete(`${relationPath(customerId, 'bank-accounts')}/${id}`);

export const listCustomerCategories = (customerId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<CustomerCategory>>(relationPath(customerId, 'categories'), { params, signal }).then((response) => response.data);
export const assignCustomerCategory = (customerId: number, categoryId: number) =>
    apiClient.post<ApiResource<CustomerCategory>>(relationPath(customerId, 'categories'), { category_id: categoryId }).then((response) => response.data.data);
export const removeCustomerCategory = (customerId: number, categoryId: number) => apiClient.delete(`${relationPath(customerId, 'categories')}/${categoryId}`);

export const listCustomerDocuments = (customerId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<CustomerDocument>>(relationPath(customerId, 'documents'), { params, signal }).then((response) => response.data);
export const createCustomerDocument = (customerId: number, payload: CustomerDocumentPayload) =>
    apiClient.post<ApiResource<CustomerDocument>>(relationPath(customerId, 'documents'), payload).then((response) => response.data.data);
export const updateCustomerDocument = (customerId: number, id: number, payload: CustomerDocumentPayload) =>
    apiClient.put<ApiResource<CustomerDocument>>(`${relationPath(customerId, 'documents')}/${id}`, payload).then((response) => response.data.data);
export const deleteCustomerDocument = (customerId: number, id: number) => apiClient.delete(`${relationPath(customerId, 'documents')}/${id}`);

export const getCustomerCreditProfile = (customerId: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<CustomerCreditProfile | null>>(relationPath(customerId, 'credit-profile'), { signal }).then((response) => response.data.data);
export const updateCustomerCreditProfile = (customerId: number, payload: CustomerCreditProfile) =>
    apiClient.put<ApiResource<CustomerCreditProfile>>(relationPath(customerId, 'credit-profile'), payload).then((response) => response.data.data);

export const listCustomerStatusHistory = (customerId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<CustomerStatusHistory>>(relationPath(customerId, 'status-history'), { params, signal }).then((response) => response.data);

export const listCustomerVehicles = (params: ListParams, signal?: AbortSignal) => listVehicleOwnerships('customer', params, signal);
export const getCustomerVehicle = getVehicleOwnership;
export const createCustomerVehicle = (payload: PartyVehiclePayload) => createVehicleOwnership('customer', payload);
export const updateCustomerVehicle = updateVehicleOwnership;
export const setCustomerVehicleCurrent = setVehicleOwnershipCurrent;
export const clearCustomerVehicleCurrent = clearVehicleOwnershipCurrent;
export const endCustomerVehicle = endVehicleOwnership;
