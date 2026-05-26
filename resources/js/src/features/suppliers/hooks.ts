import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { suppliersApi } from './api';
import type { SupplierListFilters, SupplierPayload } from './types';

const supplierKeys = {
    all: ['suppliers'] as const,
    lists: () => [...supplierKeys.all, 'list'] as const,
    list: (filters: SupplierListFilters) => [...supplierKeys.lists(), filters] as const,
    details: () => [...supplierKeys.all, 'detail'] as const,
    detail: (supplierId: number) => [...supplierKeys.details(), supplierId] as const,
    addresses: (supplierId: number, tenantId: number) => [...supplierKeys.all, supplierId, 'addresses', tenantId] as const,
    contacts: (supplierId: number, tenantId: number) => [...supplierKeys.all, supplierId, 'contacts', tenantId] as const,
    products: (supplierId: number, tenantId: number) => [...supplierKeys.all, supplierId, 'products', tenantId] as const,
    pricing: (supplierId: number, tenantId: number) => [...supplierKeys.all, supplierId, 'pricing', tenantId] as const,
};

export function useSuppliers(filters: SupplierListFilters) {
    return useQuery({
        queryKey: supplierKeys.list(filters),
        queryFn: () => suppliersApi.listSuppliers(filters),
    });
}

export function useSupplier(supplierId: number, enabled = true) {
    return useQuery({
        queryKey: supplierKeys.detail(supplierId),
        queryFn: () => suppliersApi.getSupplier(supplierId),
        enabled,
    });
}

export function useCreateSupplier() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: SupplierPayload) => suppliersApi.createSupplier(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: supplierKeys.lists() });
        },
    });
}

export function useUpdateSupplier(supplierId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: SupplierPayload) => suppliersApi.updateSupplier(supplierId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: supplierKeys.detail(supplierId) });
            void queryClient.invalidateQueries({ queryKey: supplierKeys.lists() });
        },
    });
}

export function useDeleteSupplier() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (supplierId: number) => suppliersApi.deleteSupplier(supplierId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: supplierKeys.lists() });
        },
    });
}

export function useSupplierAddresses(supplierId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: supplierKeys.addresses(supplierId, tenantId),
        queryFn: () => suppliersApi.listSupplierAddresses(supplierId, tenantId),
        enabled,
    });
}

export function useSupplierContacts(supplierId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: supplierKeys.contacts(supplierId, tenantId),
        queryFn: () => suppliersApi.listSupplierContacts(supplierId, tenantId),
        enabled,
    });
}

export function useSupplierProducts(supplierId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: supplierKeys.products(supplierId, tenantId),
        queryFn: () => suppliersApi.listSupplierProducts(supplierId, tenantId),
        enabled,
    });
}

export function useSupplierPriceLists(supplierId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: supplierKeys.pricing(supplierId, tenantId),
        queryFn: () => suppliersApi.listSupplierPriceLists(supplierId, tenantId),
        enabled,
    });
}
