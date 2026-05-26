import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { organizationApi } from './api';
import type {
    OrganizationUnitListFilters,
    OrganizationUnitPayload,
    OrganizationUnitTypeListFilters,
    OrganizationUnitTypePayload,
    OrganizationUnitUserPayload,
} from './types';

const organizationKeys = {
    units: ['organization-units'] as const,
    unitLists: () => [...organizationKeys.units, 'list'] as const,
    unitList: (filters: OrganizationUnitListFilters) => [...organizationKeys.unitLists(), filters] as const,
    unitDetail: (organizationUnitId: number, include?: string) => [...organizationKeys.units, 'detail', organizationUnitId, include ?? ''] as const,
    unitTypes: ['organization-unit-types'] as const,
    unitTypeLists: () => [...organizationKeys.unitTypes, 'list'] as const,
    unitTypeList: (filters: OrganizationUnitTypeListFilters) => [...organizationKeys.unitTypeLists(), filters] as const,
    unitTypeDetail: (organizationUnitTypeId: number) => [...organizationKeys.unitTypes, 'detail', organizationUnitTypeId] as const,
    assignments: (organizationUnitId: number, tenantId: number) => ['organization-unit-users', organizationUnitId, tenantId] as const,
};

export function useOrganizationUnits(filters: OrganizationUnitListFilters) {
    return useQuery({
        queryKey: organizationKeys.unitList(filters),
        queryFn: () => organizationApi.listOrganizationUnits(filters),
    });
}

export function useOrganizationUnit(organizationUnitId: number, include?: string, enabled = true) {
    return useQuery({
        queryKey: organizationKeys.unitDetail(organizationUnitId, include),
        queryFn: () => organizationApi.getOrganizationUnit(organizationUnitId, include),
        enabled,
    });
}

export function useCreateOrganizationUnit() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: OrganizationUnitPayload) => organizationApi.createOrganizationUnit(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.unitLists() });
        },
    });
}

export function useUpdateOrganizationUnit(organizationUnitId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: Partial<OrganizationUnitPayload>) => organizationApi.updateOrganizationUnit(organizationUnitId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.unitDetail(organizationUnitId) });
            void queryClient.invalidateQueries({ queryKey: organizationKeys.unitLists() });
        },
    });
}

export function useDeleteOrganizationUnit() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (organizationUnitId: number) => organizationApi.deleteOrganizationUnit(organizationUnitId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.unitLists() });
        },
    });
}

export function useOrganizationUnitTypes(filters: OrganizationUnitTypeListFilters) {
    return useQuery({
        queryKey: organizationKeys.unitTypeList(filters),
        queryFn: () => organizationApi.listOrganizationUnitTypes(filters),
    });
}

export function useOrganizationUnitType(organizationUnitTypeId: number, enabled = true) {
    return useQuery({
        queryKey: organizationKeys.unitTypeDetail(organizationUnitTypeId),
        queryFn: () => organizationApi.getOrganizationUnitType(organizationUnitTypeId),
        enabled,
    });
}

export function useCreateOrganizationUnitType() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: OrganizationUnitTypePayload) => organizationApi.createOrganizationUnitType(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.unitTypeLists() });
        },
    });
}

export function useUpdateOrganizationUnitType(organizationUnitTypeId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: Partial<OrganizationUnitTypePayload>) => organizationApi.updateOrganizationUnitType(organizationUnitTypeId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.unitTypeDetail(organizationUnitTypeId) });
            void queryClient.invalidateQueries({ queryKey: organizationKeys.unitTypeLists() });
        },
    });
}

export function useDeleteOrganizationUnitType() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (organizationUnitTypeId: number) => organizationApi.deleteOrganizationUnitType(organizationUnitTypeId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.unitTypeLists() });
        },
    });
}

export function useOrganizationUnitUsers(organizationUnitId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: organizationKeys.assignments(organizationUnitId, tenantId),
        queryFn: () => organizationApi.listOrganizationUnitUsers(organizationUnitId, tenantId),
        enabled,
    });
}

export function useCreateOrganizationUnitUser(organizationUnitId: number, tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: OrganizationUnitUserPayload) => organizationApi.createOrganizationUnitUser(organizationUnitId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.assignments(organizationUnitId, tenantId) });
        },
    });
}

export function useUpdateOrganizationUnitUser(organizationUnitId: number, organizationUnitUserId: number, tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: Partial<OrganizationUnitUserPayload>) =>
            organizationApi.updateOrganizationUnitUser(organizationUnitId, organizationUnitUserId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.assignments(organizationUnitId, tenantId) });
        },
    });
}

export function useDeleteOrganizationUnitUser(organizationUnitId: number, tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (organizationUnitUserId: number) => organizationApi.deleteOrganizationUnitUser(organizationUnitId, organizationUnitUserId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: organizationKeys.assignments(organizationUnitId, tenantId) });
        },
    });
}
