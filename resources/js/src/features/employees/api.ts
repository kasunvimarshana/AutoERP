import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type { EmployeeListFilters, EmployeePayload, EmployeeRecord } from './types';

export const employeesApi = {
    listEmployees(filters: EmployeeListFilters): Promise<PaginatedResult<EmployeeRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<EmployeeRecord>>('/employees', { query: toQuery(filters) }).then((payload) => unwrapPaginated<EmployeeRecord>(payload));
    },
    getEmployee(employeeId: number) {
        return apiClient.get<ApiResourceEnvelope<EmployeeRecord> | EmployeeRecord>(`/employees/${employeeId}`).then((payload) => unwrapResource<EmployeeRecord>(payload));
    },
    createEmployee(payload: EmployeePayload) {
        return apiClient.post<ApiResourceEnvelope<EmployeeRecord> | EmployeeRecord>('/employees', payload).then((result) => unwrapResource<EmployeeRecord>(result));
    },
    updateEmployee(employeeId: number, payload: EmployeePayload) {
        return apiClient.put<ApiResourceEnvelope<EmployeeRecord> | EmployeeRecord>(`/employees/${employeeId}`, payload).then((result) => unwrapResource<EmployeeRecord>(result));
    },
    deleteEmployee(employeeId: number) {
        return apiClient.delete<{ message: string }>(`/employees/${employeeId}`);
    },
};
