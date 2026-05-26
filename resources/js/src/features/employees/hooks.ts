import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { employeesApi } from './api';
import type { EmployeeListFilters, EmployeePayload } from './types';

const employeeKeys = {
    all: ['employees'] as const,
    lists: () => [...employeeKeys.all, 'list'] as const,
    list: (filters: EmployeeListFilters) => [...employeeKeys.lists(), filters] as const,
    details: () => [...employeeKeys.all, 'detail'] as const,
    detail: (employeeId: number) => [...employeeKeys.details(), employeeId] as const,
};

export function useEmployees(filters: EmployeeListFilters) {
    return useQuery({
        queryKey: employeeKeys.list(filters),
        queryFn: () => employeesApi.listEmployees(filters),
    });
}

export function useEmployee(employeeId: number, enabled = true) {
    return useQuery({
        queryKey: employeeKeys.detail(employeeId),
        queryFn: () => employeesApi.getEmployee(employeeId),
        enabled,
    });
}

export function useCreateEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: EmployeePayload) => employeesApi.createEmployee(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
        },
    });
}

export function useUpdateEmployee(employeeId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: EmployeePayload) => employeesApi.updateEmployee(employeeId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: employeeKeys.detail(employeeId) });
            void queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
        },
    });
}

export function useDeleteEmployee() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (employeeId: number) => employeesApi.deleteEmployee(employeeId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: employeeKeys.lists() });
        },
    });
}
