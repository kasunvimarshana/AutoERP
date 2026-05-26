import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    CustomerAddress,
    CustomerContact,
    CustomerListFilters,
    CustomerPayload,
    CustomerPriceListAssignment,
    CustomerRecord,
} from './types';

export const customersApi = {
    listCustomers(filters: CustomerListFilters): Promise<PaginatedResult<CustomerRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<CustomerRecord>>('/customers', { query: toQuery(filters) }).then((payload) => unwrapPaginated<CustomerRecord>(payload));
    },
    getCustomer(customerId: number) {
        return apiClient.get<ApiResourceEnvelope<CustomerRecord> | CustomerRecord>(`/customers/${customerId}`).then((payload) => unwrapResource<CustomerRecord>(payload));
    },
    createCustomer(payload: CustomerPayload) {
        return apiClient.post<ApiResourceEnvelope<CustomerRecord> | CustomerRecord>('/customers', payload).then((result) => unwrapResource<CustomerRecord>(result));
    },
    updateCustomer(customerId: number, payload: CustomerPayload) {
        return apiClient.put<ApiResourceEnvelope<CustomerRecord> | CustomerRecord>(`/customers/${customerId}`, payload).then((result) => unwrapResource<CustomerRecord>(result));
    },
    deleteCustomer(customerId: number) {
        return apiClient.delete<{ message: string }>(`/customers/${customerId}`);
    },
    listCustomerAddresses(customerId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<CustomerAddress>> {
        return apiClient
            .get<ApiPaginatedEnvelope<CustomerAddress>>(`/customers/${customerId}/addresses`, { query: { tenant_id: tenantId, page, per_page: perPage } })
            .then((payload) => unwrapPaginated<CustomerAddress>(payload));
    },
    listCustomerContacts(customerId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<CustomerContact>> {
        return apiClient
            .get<ApiPaginatedEnvelope<CustomerContact>>(`/customers/${customerId}/contacts`, { query: { tenant_id: tenantId, page, per_page: perPage } })
            .then((payload) => unwrapPaginated<CustomerContact>(payload));
    },
    listCustomerPriceLists(customerId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<CustomerPriceListAssignment>> {
        return apiClient
            .get<ApiPaginatedEnvelope<CustomerPriceListAssignment>>(`/pricing/customers/${customerId}/price-lists`, {
                query: { tenant_id: tenantId, page, per_page: perPage },
            })
            .then((payload) => unwrapPaginated<CustomerPriceListAssignment>(payload));
    },
};
