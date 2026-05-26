import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { customersApi } from './api';
import type { CustomerListFilters, CustomerPayload } from './types';

const customerKeys = {
    all: ['customers'] as const,
    lists: () => [...customerKeys.all, 'list'] as const,
    list: (filters: CustomerListFilters) => [...customerKeys.lists(), filters] as const,
    details: () => [...customerKeys.all, 'detail'] as const,
    detail: (customerId: number) => [...customerKeys.details(), customerId] as const,
    addresses: (customerId: number, tenantId: number) => [...customerKeys.all, customerId, 'addresses', tenantId] as const,
    contacts: (customerId: number, tenantId: number) => [...customerKeys.all, customerId, 'contacts', tenantId] as const,
    pricing: (customerId: number, tenantId: number) => [...customerKeys.all, customerId, 'pricing', tenantId] as const,
};

export function useCustomers(filters: CustomerListFilters) {
    return useQuery({
        queryKey: customerKeys.list(filters),
        queryFn: () => customersApi.listCustomers(filters),
    });
}

export function useCustomer(customerId: number, enabled = true) {
    return useQuery({
        queryKey: customerKeys.detail(customerId),
        queryFn: () => customersApi.getCustomer(customerId),
        enabled,
    });
}

export function useCreateCustomer() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CustomerPayload) => customersApi.createCustomer(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: customerKeys.lists() });
        },
    });
}

export function useUpdateCustomer(customerId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CustomerPayload) => customersApi.updateCustomer(customerId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: customerKeys.detail(customerId) });
            void queryClient.invalidateQueries({ queryKey: customerKeys.lists() });
        },
    });
}

export function useDeleteCustomer() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (customerId: number) => customersApi.deleteCustomer(customerId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: customerKeys.lists() });
        },
    });
}

export function useCustomerAddresses(customerId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: customerKeys.addresses(customerId, tenantId),
        queryFn: () => customersApi.listCustomerAddresses(customerId, tenantId),
        enabled,
    });
}

export function useCustomerContacts(customerId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: customerKeys.contacts(customerId, tenantId),
        queryFn: () => customersApi.listCustomerContacts(customerId, tenantId),
        enabled,
    });
}

export function useCustomerPriceLists(customerId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: customerKeys.pricing(customerId, tenantId),
        queryFn: () => customersApi.listCustomerPriceLists(customerId, tenantId),
        enabled,
    });
}
