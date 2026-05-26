import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    SupplierAddress,
    SupplierContact,
    SupplierListFilters,
    SupplierPayload,
    SupplierPriceListAssignment,
    SupplierProduct,
    SupplierRecord,
} from './types';

export const suppliersApi = {
    listSuppliers(filters: SupplierListFilters): Promise<PaginatedResult<SupplierRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<SupplierRecord>>('/suppliers', { query: toQuery(filters) }).then((payload) => unwrapPaginated<SupplierRecord>(payload));
    },
    getSupplier(supplierId: number) {
        return apiClient.get<ApiResourceEnvelope<SupplierRecord> | SupplierRecord>(`/suppliers/${supplierId}`).then((payload) => unwrapResource<SupplierRecord>(payload));
    },
    createSupplier(payload: SupplierPayload) {
        return apiClient.post<ApiResourceEnvelope<SupplierRecord> | SupplierRecord>('/suppliers', payload).then((result) => unwrapResource<SupplierRecord>(result));
    },
    updateSupplier(supplierId: number, payload: SupplierPayload) {
        return apiClient.put<ApiResourceEnvelope<SupplierRecord> | SupplierRecord>(`/suppliers/${supplierId}`, payload).then((result) => unwrapResource<SupplierRecord>(result));
    },
    deleteSupplier(supplierId: number) {
        return apiClient.delete<{ message: string }>(`/suppliers/${supplierId}`);
    },
    listSupplierAddresses(supplierId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<SupplierAddress>> {
        return apiClient
            .get<ApiPaginatedEnvelope<SupplierAddress>>(`/suppliers/${supplierId}/addresses`, { query: { tenant_id: tenantId, page, per_page: perPage } })
            .then((payload) => unwrapPaginated<SupplierAddress>(payload));
    },
    listSupplierContacts(supplierId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<SupplierContact>> {
        return apiClient
            .get<ApiPaginatedEnvelope<SupplierContact>>(`/suppliers/${supplierId}/contacts`, { query: { tenant_id: tenantId, page, per_page: perPage } })
            .then((payload) => unwrapPaginated<SupplierContact>(payload));
    },
    listSupplierProducts(supplierId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<SupplierProduct>> {
        return apiClient
            .get<ApiPaginatedEnvelope<SupplierProduct>>(`/suppliers/${supplierId}/products`, { query: { tenant_id: tenantId, page, per_page: perPage } })
            .then((payload) => unwrapPaginated<SupplierProduct>(payload));
    },
    listSupplierPriceLists(supplierId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<SupplierPriceListAssignment>> {
        return apiClient
            .get<ApiPaginatedEnvelope<SupplierPriceListAssignment>>(`/pricing/suppliers/${supplierId}/price-lists`, {
                query: { tenant_id: tenantId, page, per_page: perPage },
            })
            .then((payload) => unwrapPaginated<SupplierPriceListAssignment>(payload));
    },
};
