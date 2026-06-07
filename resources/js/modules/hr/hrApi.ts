import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
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
export const deleteEmployee = (id: number) => apiClient.delete(`${endpoints.hrEmployees}/${id}`);
export const setEmployeeActive = (id: number, active: boolean) => apiClient.patch<ApiResource<Employee>>(`${endpoints.hrEmployees}/${id}/${active ? 'activate' : 'deactivate'}`).then((r) => r.data.data);
export const changeEmployeeStatus = (id: number, status: string, reason?: string) => apiClient.patch<ApiResource<Employee>>(`${endpoints.hrEmployees}/${id}/status`, { status, reason }).then((r) => r.data.data);
export const changeEmployeeAvailability = (id: number, payload: EmployeeAvailabilityPayload) => apiClient.patch<ApiResource<Employee>>(`${endpoints.hrEmployees}/${id}/availability`, payload).then((r) => r.data.data);

export async function searchEmployees(search: string, signal: AbortSignal, kind = 'active'): Promise<EmployeeSummary[]> {
    const response = await apiClient.get<ApiCollection<EmployeeSummary>>(`${endpoints.hrEmployees}/lookup/${kind}`, { params: { search, per_page: 20 }, signal });
    return response.data.data;
}
type Master = HrDepartment | HrDesignation | HrEmploymentType | HrSkill | HrCertification | HrLicense;
async function searchMaster<T extends Master>(endpoint: string, search: string, signal: AbortSignal): Promise<T[]> {
    const response = await apiClient.get<ApiCollection<T>>(`${endpoint}/lookup`, { params: { search, per_page: 20 }, signal });
    return response.data.data;
}
export const searchDepartments = (q: string, s: AbortSignal) => searchMaster<HrDepartment>(endpoints.hrDepartments, q, s);
export const searchDesignations = (q: string, s: AbortSignal) => searchMaster<HrDesignation>(endpoints.hrDesignations, q, s);
export const searchEmploymentTypes = (q: string, s: AbortSignal) => searchMaster<HrEmploymentType>(endpoints.hrEmploymentTypes, q, s);
export const searchSkills = (q: string, s: AbortSignal) => searchMaster<HrSkill>(endpoints.hrSkills, q, s);
export const searchCertifications = (q: string, s: AbortSignal) => searchMaster<HrCertification>(endpoints.hrCertifications, q, s);
export const searchLicenses = (q: string, s: AbortSignal) => searchMaster<HrLicense>(endpoints.hrLicenses, q, s);

const path = (employeeId: number, relation: string) => `${endpoints.hrEmployees}/${employeeId}/${relation}`;
function relationApi<T, P>(relation: string) {
    return {
        list: (employeeId: number, page: number, signal: AbortSignal) => apiClient.get<ApiCollection<T>>(path(employeeId, relation), { params: { page, per_page: 20 }, signal }).then((r) => r.data),
        create: (employeeId: number, payload: P) => apiClient.post<ApiResource<T>>(path(employeeId, relation), payload).then((r) => r.data.data),
        update: (employeeId: number, id: number, payload: P) => apiClient.put<ApiResource<T>>(`${path(employeeId, relation)}/${id}`, payload).then((r) => r.data.data),
        remove: (employeeId: number, id: number) => apiClient.delete(`${path(employeeId, relation)}/${id}`),
    };
}
export const contactApi = relationApi<EmployeeContact, EmployeeContactPayload>('contacts');
export const addressApi = relationApi<EmployeeAddress, EmployeeAddressPayload>('addresses');
export const documentApi = relationApi<EmployeeDocument, EmployeeDocumentPayload>('documents');
export const skillApi = relationApi<EmployeeSkillAssignment, EmployeeSkillPayload>('skills');
export const certificationApi = relationApi<EmployeeCertificationAssignment, EmployeeCertificationPayload>('certifications');
export const licenseApi = relationApi<EmployeeLicenseAssignment, EmployeeLicensePayload>('licenses');
export const rateApi = relationApi<EmployeeRate, EmployeeRatePayload>('rates');
export const availabilityApi = {
    ...relationApi<EmployeeAvailability, EmployeeAvailabilityPayload>('availability'),
    create: (employeeId: number, payload: EmployeeAvailabilityPayload) => apiClient.post<ApiResource<EmployeeAvailability>>(path(employeeId, 'availability'), payload).then((r) => r.data.data),
};
export const listEmployeeStatusHistory = (employeeId: number, page: number, signal: AbortSignal) => apiClient.get<ApiCollection<EmployeeStatusHistory>>(path(employeeId, 'status-history'), { params: { page, per_page: 20 }, signal }).then((r) => r.data);
