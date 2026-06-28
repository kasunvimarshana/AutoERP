import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { PartyVehiclePayload, PartyVehicleRelationship } from '@/shared/types/partyVehicle';
import type {
    Supplier,
    SupplierAddress,
    SupplierAddressPayload,
    SupplierBankAccount,
    SupplierBankAccountPayload,
    SupplierCategory,
    SupplierContact,
    SupplierContactPayload,
    SupplierCreditProfile,
    SupplierDocument,
    SupplierDocumentPayload,
    SupplierItemMapping,
    SupplierItemMappingPayload,
    SupplierPayload,
    SupplierStatusHistory,
    SupplierSummary,
    SupplierWithRelationsPayload,
} from './supplierTypes';

export const listSuppliers = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<SupplierSummary>>(endpoints.suppliers, { params, signal }).then((response) => response.data);

export const getSupplier = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<Supplier>>(`${endpoints.suppliers}/${id}`, { signal }).then((response) => response.data.data);

export const createSupplier = (payload: SupplierPayload) =>
    apiClient.post<ApiResource<Supplier>>(endpoints.suppliers, payload).then((response) => response.data.data);

export const createSupplierWithRelations = (payload: SupplierWithRelationsPayload) =>
    apiClient.post<ApiResource<Supplier>>(`${endpoints.suppliers}/with-relations`, payload).then((response) => response.data.data);

export const updateSupplier = (id: number, payload: Partial<SupplierPayload>) =>
    apiClient.put<ApiResource<Supplier>>(`${endpoints.suppliers}/${id}`, payload).then((response) => response.data.data);

export const deleteSupplier = (id: number) => apiClient.delete(`${endpoints.suppliers}/${id}`);

export const setSupplierActive = (id: number, active: boolean) =>
    apiClient.patch<ApiResource<Supplier>>(`${endpoints.suppliers}/${id}/${active ? 'activate' : 'deactivate'}`)
        .then((response) => response.data.data);

export const changeSupplierStatus = (id: number, status: string, reason?: string) =>
    apiClient.patch<ApiResource<Supplier>>(`${endpoints.suppliers}/${id}/status`, { status, reason })
        .then((response) => response.data.data);

export function searchSuppliers(params: LookupLoadParams, kind = 'active'): Promise<LookupResult<SupplierSummary>> {
    return requestLookup<SupplierSummary>(`${endpoints.suppliers}/lookup/${kind}`, params);
}

export function searchSupplierCategories(params: LookupLoadParams): Promise<LookupResult<SupplierCategory>> {
    return requestLookup<SupplierCategory>(`${endpoints.supplierCategories}/lookup`, params);
}

export function searchActiveItems(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return requestLookup<NamedResource>(`${endpoints.items}/lookup`, params);
}

export async function listItemVariantsForLookup(itemId: number, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(`${endpoints.items}/${itemId}/variants`, {
        params: { per_page: 50 },
        signal,
    });
    return response.data.data;
}

export function searchCurrencies(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return requestLookup<NamedResource>(endpoints.currencies, params);
}

const relationPath = (supplierId: number, relation: string) => `${endpoints.suppliers}/${supplierId}/${relation}`;

export const listSupplierContacts = (supplierId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<SupplierContact>>(relationPath(supplierId, 'contacts'), { params, signal }).then((response) => response.data);
export const createSupplierContact = (supplierId: number, payload: SupplierContactPayload) =>
    apiClient.post<ApiResource<SupplierContact>>(relationPath(supplierId, 'contacts'), payload).then((response) => response.data.data);
export const updateSupplierContact = (supplierId: number, id: number, payload: SupplierContactPayload) =>
    apiClient.put<ApiResource<SupplierContact>>(`${relationPath(supplierId, 'contacts')}/${id}`, payload).then((response) => response.data.data);
export const deleteSupplierContact = (supplierId: number, id: number) => apiClient.delete(`${relationPath(supplierId, 'contacts')}/${id}`);

export const listSupplierAddresses = (supplierId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<SupplierAddress>>(relationPath(supplierId, 'addresses'), { params, signal }).then((response) => response.data);
export const createSupplierAddress = (supplierId: number, payload: SupplierAddressPayload) =>
    apiClient.post<ApiResource<SupplierAddress>>(relationPath(supplierId, 'addresses'), payload).then((response) => response.data.data);
export const updateSupplierAddress = (supplierId: number, id: number, payload: SupplierAddressPayload) =>
    apiClient.put<ApiResource<SupplierAddress>>(`${relationPath(supplierId, 'addresses')}/${id}`, payload).then((response) => response.data.data);
export const deleteSupplierAddress = (supplierId: number, id: number) => apiClient.delete(`${relationPath(supplierId, 'addresses')}/${id}`);

export const listSupplierBankAccounts = (supplierId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<SupplierBankAccount>>(relationPath(supplierId, 'bank-accounts'), { params, signal }).then((response) => response.data);
export const createSupplierBankAccount = (supplierId: number, payload: SupplierBankAccountPayload) =>
    apiClient.post<ApiResource<SupplierBankAccount>>(relationPath(supplierId, 'bank-accounts'), payload).then((response) => response.data.data);
export const updateSupplierBankAccount = (supplierId: number, id: number, payload: SupplierBankAccountPayload) =>
    apiClient.put<ApiResource<SupplierBankAccount>>(`${relationPath(supplierId, 'bank-accounts')}/${id}`, payload).then((response) => response.data.data);
export const deleteSupplierBankAccount = (supplierId: number, id: number) => apiClient.delete(`${relationPath(supplierId, 'bank-accounts')}/${id}`);

export const listSupplierCategories = (supplierId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<SupplierCategory>>(relationPath(supplierId, 'categories'), { params, signal }).then((response) => response.data);
export const assignSupplierCategory = (supplierId: number, categoryId: number) =>
    apiClient.post<ApiResource<SupplierCategory>>(relationPath(supplierId, 'categories'), { category_id: categoryId }).then((response) => response.data.data);
export const removeSupplierCategory = (supplierId: number, categoryId: number) => apiClient.delete(`${relationPath(supplierId, 'categories')}/${categoryId}`);

export const listSupplierDocuments = (supplierId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<SupplierDocument>>(relationPath(supplierId, 'documents'), { params, signal }).then((response) => response.data);
export const createSupplierDocument = (supplierId: number, payload: SupplierDocumentPayload) =>
    apiClient.post<ApiResource<SupplierDocument>>(relationPath(supplierId, 'documents'), payload).then((response) => response.data.data);
export const updateSupplierDocument = (supplierId: number, id: number, payload: SupplierDocumentPayload) =>
    apiClient.put<ApiResource<SupplierDocument>>(`${relationPath(supplierId, 'documents')}/${id}`, payload).then((response) => response.data.data);
export const deleteSupplierDocument = (supplierId: number, id: number) => apiClient.delete(`${relationPath(supplierId, 'documents')}/${id}`);

export const listSupplierItemMappings = (supplierId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<SupplierItemMapping>>(relationPath(supplierId, 'item-mappings'), { params, signal }).then((response) => response.data);
export const createSupplierItemMapping = (supplierId: number, payload: SupplierItemMappingPayload) =>
    apiClient.post<ApiResource<SupplierItemMapping>>(relationPath(supplierId, 'item-mappings'), payload).then((response) => response.data.data);
export const updateSupplierItemMapping = (supplierId: number, id: number, payload: SupplierItemMappingPayload) =>
    apiClient.put<ApiResource<SupplierItemMapping>>(`${relationPath(supplierId, 'item-mappings')}/${id}`, payload).then((response) => response.data.data);
export const deleteSupplierItemMapping = (supplierId: number, id: number) => apiClient.delete(`${relationPath(supplierId, 'item-mappings')}/${id}`);

export const getSupplierCreditProfile = (supplierId: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<SupplierCreditProfile | null>>(relationPath(supplierId, 'credit-profile'), { signal }).then((response) => response.data.data);
export const updateSupplierCreditProfile = (supplierId: number, payload: SupplierCreditProfile) =>
    apiClient.put<ApiResource<SupplierCreditProfile>>(relationPath(supplierId, 'credit-profile'), payload).then((response) => response.data.data);

export const listSupplierStatusHistory = (supplierId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<SupplierStatusHistory>>(relationPath(supplierId, 'status-history'), { params, signal }).then((response) => response.data);

export const listSupplierVehicles = (params: ListParams, signal?: AbortSignal) => apiClient.get<ApiCollection<PartyVehicleRelationship>>(endpoints.supplierVehicles, { params, signal }).then((r) => r.data);
export const getSupplierVehicle = (id: number, signal?: AbortSignal) => apiClient.get<ApiResource<PartyVehicleRelationship>>(`${endpoints.supplierVehicles}/${id}`, { signal }).then((r) => r.data.data);
export const createSupplierVehicle = (payload: PartyVehiclePayload) => apiClient.post<ApiResource<PartyVehicleRelationship>>(endpoints.supplierVehicles, payload).then((r) => r.data.data);
export const updateSupplierVehicle = (id: number, payload: Partial<PartyVehiclePayload>) => apiClient.patch<ApiResource<PartyVehicleRelationship>>(`${endpoints.supplierVehicles}/${id}`, payload).then((r) => r.data.data);
export const setSupplierVehicleCurrent = (id: number) => apiClient.post<ApiResource<PartyVehicleRelationship>>(`${endpoints.supplierVehicles}/${id}/set-current`).then((r) => r.data.data);
export const clearSupplierVehicleCurrent = (id: number) => apiClient.post<ApiResource<PartyVehicleRelationship>>(`${endpoints.supplierVehicles}/${id}/clear-current`).then((r) => r.data.data);
export const endSupplierVehicle = (id: number) => apiClient.delete<ApiResource<PartyVehicleRelationship>>(`${endpoints.supplierVehicles}/${id}`).then((r) => r.data.data);
