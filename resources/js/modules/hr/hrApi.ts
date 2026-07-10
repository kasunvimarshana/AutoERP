import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type {
    Employee, EmployeeAddress, EmployeeAddressPayload, EmployeeAvailability, EmployeeAvailabilityPayload,
    EmployeeCertificationAssignment, EmployeeCertificationPayload, EmployeeContact, EmployeeContactPayload,
    EmployeeDocument, EmployeeDocumentPayload, EmployeeLicenseAssignment, EmployeeLicensePayload, EmployeePayload,
    EmployeeRate, EmployeeRatePayload, EmployeeSkillAssignment, EmployeeSkillPayload, EmployeeStatusHistory,
    EmployeeSummary, EmployeeWithRelationsPayload, HrCertification, HrDepartment, HrDesignation, HrEmploymentType,
    HrLicense, HrSkill,
} from './hrTypes';

export const listEmployees = (params: ListParams, signal?: AbortSignal) => apiClient.get<ApiCollection<EmployeeSummary>>(endpoints.hrEmployees, { params, signal }).then((r) => r.data);
export const getEmployee = (id: number, signal?: AbortSignal) => apiClient.get<ApiResource<Employee>>(`${endpoints.hrEmployees}/${id}`, { signal }).then((r) => r.data.data);
export const createEmployee = (payload: EmployeePayload) => apiClient.post<ApiResource<Employee>>(endpoints.hrEmployees, payload).then((r) => r.data.data);
export const createEmployeeWithRelations = (payload: EmployeeWithRelationsPayload) => apiClient.post<ApiResource<Employee>>(`${endpoints.hrEmployees}/with-relations`, payload).then((r) => r.data.data);
export const updateEmployee = (id: number, payload: Partial<EmployeePayload>) => apiClient.put<ApiResource<Employee>>(`${endpoints.hrEmployees}/${id}`, payload).then((r) => r.data.data);
export const deleteEmployee = (id: number, rowVersion: number) => apiClient.delete(`${endpoints.hrEmployees}/${id}`, { data: { row_version: rowVersion } });
export const setEmployeeActive = (id: number, active: boolean, rowVersion: number) => apiClient.patch<ApiResource<Employee>>(`${endpoints.hrEmployees}/${id}/${active ? 'activate' : 'deactivate'}`, { row_version: rowVersion }).then((r) => r.data.data);
export const changeEmployeeStatus = (id: number, status: string, rowVersion: number, reason?: string) => apiClient.patch<ApiResource<Employee>>(`${endpoints.hrEmployees}/${id}/status`, { status, row_version: rowVersion, reason }).then((r) => r.data.data);
export const changeEmployeeAvailability = (id: number, payload: EmployeeAvailabilityPayload) => apiClient.patch<ApiResource<Employee>>(`${endpoints.hrEmployees}/${id}/availability`, payload).then((r) => r.data.data);

export function searchEmployees(params: LookupLoadParams, kind = 'active'): Promise<LookupResult<EmployeeSummary>> {
    return requestLookup<EmployeeSummary>(`${endpoints.hrEmployees}/lookup/${kind}`, params);
}
type Master = HrDepartment | HrDesignation | HrEmploymentType | HrSkill | HrCertification | HrLicense;
function searchMaster<T extends Master>(endpoint: string, params: LookupLoadParams): Promise<LookupResult<T>> {
    return requestLookup<T>(`${endpoint}/lookup`, params);
}
export const searchDepartments = (params: LookupLoadParams) => searchMaster<HrDepartment>(endpoints.hrDepartments, params);
export const searchDesignations = (params: LookupLoadParams) => searchMaster<HrDesignation>(endpoints.hrDesignations, params);
export const searchEmploymentTypes = (params: LookupLoadParams) => searchMaster<HrEmploymentType>(endpoints.hrEmploymentTypes, params);
export const searchSkills = (params: LookupLoadParams) => searchMaster<HrSkill>(endpoints.hrSkills, params);
export const searchCertifications = (params: LookupLoadParams) => searchMaster<HrCertification>(endpoints.hrCertifications, params);
export const searchLicenses = (params: LookupLoadParams) => searchMaster<HrLicense>(endpoints.hrLicenses, params);

const path = (employeeId: number, relation: string) => `${endpoints.hrEmployees}/${employeeId}/${relation}`;
function relationApi<T, P>(relation: string) {
    return {
        list: (employeeId: number, page: number, signal: AbortSignal) => apiClient.get<ApiCollection<T>>(path(employeeId, relation), { params: { page, per_page: 20 }, signal }).then((r) => r.data),
        create: (employeeId: number, payload: P) => apiClient.post<ApiResource<T>>(path(employeeId, relation), payload).then((r) => r.data.data),
        update: (employeeId: number, id: number, payload: P) => apiClient.put<ApiResource<T>>(`${path(employeeId, relation)}/${id}`, payload).then((r) => r.data.data),
        remove: (employeeId: number, id: number) => apiClient.delete(`${path(employeeId, relation)}/${id}`),
    };
}
function createOnlyRelationApi<T, P>(relation: string) {
    return {
        list: (employeeId: number, page: number, signal: AbortSignal) => apiClient.get<ApiCollection<T>>(path(employeeId, relation), { params: { page, per_page: 20 }, signal }).then((r) => r.data),
        create: (employeeId: number, payload: P) => apiClient.post<ApiResource<T>>(path(employeeId, relation), payload).then((r) => r.data.data),
    };
}
export const contactApi = relationApi<EmployeeContact, EmployeeContactPayload>('contacts');
export const addressApi = relationApi<EmployeeAddress, EmployeeAddressPayload>('addresses');
export const documentApi = relationApi<EmployeeDocument, EmployeeDocumentPayload>('documents');
export const skillApi = relationApi<EmployeeSkillAssignment, EmployeeSkillPayload>('skills');
export const certificationApi = relationApi<EmployeeCertificationAssignment, EmployeeCertificationPayload>('certifications');
export const licenseApi = relationApi<EmployeeLicenseAssignment, EmployeeLicensePayload>('licenses');
export const rateApi = createOnlyRelationApi<EmployeeRate, EmployeeRatePayload>('rates');
export const availabilityApi = {
    ...relationApi<EmployeeAvailability, EmployeeAvailabilityPayload>('availability'),
    create: (employeeId: number, payload: EmployeeAvailabilityPayload) => apiClient.post<ApiResource<EmployeeAvailability>>(path(employeeId, 'availability'), payload).then((r) => r.data.data),
};
export const listEmployeeStatusHistory = (employeeId: number, page: number, signal: AbortSignal) => apiClient.get<ApiCollection<EmployeeStatusHistory>>(path(employeeId, 'status-history'), { params: { page, per_page: 20 }, signal }).then((r) => r.data);
