/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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

// --- Shifts & Rostering API ---
export const listShifts = () => apiClient.get<ApiResource<import('./hrTypes').HrShift[]>>(endpoints.hrShifts).then((r) => r.data.data);
export const createShift = (payload: import('./hrTypes').HrShiftPayload) => apiClient.post<ApiResource<import('./hrTypes').HrShift>>(endpoints.hrShifts, payload).then((r) => r.data.data);
export const updateShift = (id: number, payload: Partial<import('./hrTypes').HrShiftPayload>) => apiClient.put<ApiResource<import('./hrTypes').HrShift>>(`${endpoints.hrShifts}/${id}`, payload).then((r) => r.data.data);
export const deleteShift = (id: number) => apiClient.delete(`${endpoints.hrShifts}/${id}`);

export const getRoster = (params: { start_date: string; end_date: string; employee_ids?: number[]; department_id?: number }) =>
    apiClient.get<ApiResource<Array<{ employee: { id: number; employee_number: string; display_name: string }; days: Record<string, { shift: import('./hrTypes').HrShift | null; is_working: boolean; is_rest_day: boolean; is_holiday: boolean; holiday_name?: string | null; source: string }> }>>>(endpoints.hrRoster, { params }).then((r) => r.data.data);

export const assignShift = (payload: { employee_id: number; shift_id: number; assignment_type: string; day_of_week?: number; start_date?: string; end_date?: string; is_custom_timing?: boolean; custom_start_time?: string; custom_end_time?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrShiftAssignment>>(endpoints.hrShiftAssignments, payload).then((r) => r.data.data);

export const cancelShiftAssignment = (id: number, reason: string) =>
    apiClient.delete<ApiResource<import('./hrTypes').HrShiftAssignment>>(`${endpoints.hrShiftAssignments}/${id}`, { data: { reason } }).then((r) => r.data.data);

export const requestShiftSwap = (payload: { requester_employee_id: number; target_employee_id: number; swap_date: string; target_swap_date: string; reason: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrShiftSwap>>(endpoints.hrShiftSwaps, payload).then((r) => r.data.data);

export const peerActionShiftSwap = (swapId: number, peerEmployeeId: number, accept: boolean) =>
    apiClient.patch<ApiResource<import('./hrTypes').HrShiftSwap>>(`${endpoints.hrShiftSwaps}/${swapId}/peer-action`, { peer_employee_id: peerEmployeeId, accept }).then((r) => r.data.data);

export const managerActionShiftSwap = (swapId: number, approve: boolean, notes?: string) =>
    apiClient.patch<ApiResource<import('./hrTypes').HrShiftSwap>>(`${endpoints.hrShiftSwaps}/${swapId}/manager-action`, { approve, notes }).then((r) => r.data.data);

export const reallocateShiftCover = (payload: { absent_employee_id: number; cover_employee_id: number; shift_id: number; reallocation_date: string; reason_code?: string; notes?: string }) =>
    apiClient.post(endpoints.hrShiftReallocations, payload).then((r) => r.data.data);

// --- Attendance API ---
export const punchAttendance = (payload: { employee_id: number; punch_type: string; punch_time?: string; punch_source?: string; notes?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrAttendancePunch>>(endpoints.hrAttendancePunch, payload).then((r) => r.data.data);

export const getTodayAttendance = (employeeId: number) =>
    apiClient.get<ApiResource<{ date: string; record: import('./hrTypes').HrAttendanceRecord | null; punches: import('./hrTypes').HrAttendancePunch[] }>>(`${endpoints.hrAttendanceToday}/${employeeId}`).then((r) => r.data.data);

export const listAttendanceRecords = (params: { start_date?: string; end_date?: string; employee_id?: number; department_id?: number; status?: string; page?: number; per_page?: number }) =>
    apiClient.get<ApiCollection<import('./hrTypes').HrAttendanceRecord>>(endpoints.hrAttendanceRecords, { params }).then((r) => r.data);

export const requestAttendanceRegularization = (payload: { employee_id: number; attendance_date: string; regularization_type: string; reason_code: string; employee_notes: string; proposed_clock_in?: string; proposed_clock_out?: string; attendance_record_id?: number }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrAttendanceRegularization>>(endpoints.hrAttendanceRegularize, payload).then((r) => r.data.data);

export const listAttendanceRegularizations = (params?: { status?: string; employee_id?: number }) =>
    apiClient.get<ApiResource<import('./hrTypes').HrAttendanceRegularization[]>>(endpoints.hrAttendanceRegularizations, { params }).then((r) => r.data.data);

export const actionAttendanceRegularization = (id: number, approve: boolean, notes?: string) =>
    apiClient.patch<ApiResource<import('./hrTypes').HrAttendanceRegularization>>(`${endpoints.hrAttendanceRegularizations}/${id}/action`, { approve, notes }).then((r) => r.data.data);

export const directAdjustAttendanceRecord = (recordId: number, payload: { new_status: string; new_worked_minutes: number; new_overtime_minutes: number; adjustment_reason: string }) =>
    apiClient.post<ApiResource<unknown>>(`${endpoints.hrAttendanceRecords}/${recordId}/direct-adjust`, payload).then((r) => r.data.data);

// --- Leaves API ---
export const listLeaveTypes = () =>
    apiClient.get<ApiResource<import('./hrTypes').HrLeaveType[]>>(endpoints.hrLeaveTypes).then((r) => r.data.data);

export const createLeaveType = (payload: Partial<import('./hrTypes').HrLeaveType>) =>
    apiClient.post<ApiResource<import('./hrTypes').HrLeaveType>>(endpoints.hrLeaveTypes, payload).then((r) => r.data.data);

export const listLeavePolicies = () =>
    apiClient.get<ApiResource<import('./hrTypes').HrLeavePolicy[]>>(endpoints.hrLeavePolicies).then((r) => r.data.data);

export const createLeavePolicy = (payload: Partial<import('./hrTypes').HrLeavePolicy>) =>
    apiClient.post<ApiResource<import('./hrTypes').HrLeavePolicy>>(endpoints.hrLeavePolicies, payload).then((r) => r.data.data);

export const listLeaveBalances = (params?: { employee_id?: number; calendar_year?: number }) =>
    apiClient.get<ApiResource<import('./hrTypes').HrLeaveBalance[]>>(endpoints.hrLeaveBalances, { params }).then((r) => r.data.data);

export const listLeaveRequests = (params?: { employee_id?: number; status?: string; leave_type_id?: number; page?: number; per_page?: number }) =>
    apiClient.get<ApiCollection<import('./hrTypes').HrLeaveRequest>>(endpoints.hrLeaveRequests, { params }).then((r) => r.data);

export const applyLeave = (payload: { employee_id: number; leave_type_id: number; start_date: string; end_date: string; total_days: string; reason: string; duration_type?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrLeaveRequest>>(endpoints.hrLeaveRequests, payload).then((r) => r.data.data);

export const actionLeaveRequest = (requestId: number, approve: boolean, notes?: string) =>
    apiClient.patch<ApiResource<import('./hrTypes').HrLeaveRequest>>(`${endpoints.hrLeaveRequests}/${requestId}/action`, { approve, notes }).then((r) => r.data.data);

export const cancelLeaveRequest = (requestId: number, reason: string) =>
    apiClient.post<ApiResource<import('./hrTypes').HrLeaveRequest>>(`${endpoints.hrLeaveRequests}/${requestId}/cancel`, { reason }).then((r) => r.data.data);

export const claimCompOff = (payload: { employee_id: number; worked_date: string; reason: string; claimed_days?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrCompOffClaim>>(endpoints.hrCompOffClaim, payload).then((r) => r.data.data);

export const actionCompOffClaim = (claimId: number, approve: boolean) =>
    apiClient.patch<ApiResource<import('./hrTypes').HrCompOffClaim>>(`${endpoints.hrCompOffClaims}/${claimId}/action`, { approve }).then((r) => r.data.data);

// --- Biometric Hardware API ---
export const listBiometricDevices = (params?: { status?: string; protocol?: string; search?: string; page?: number; per_page?: number }) =>
    apiClient.get<ApiCollection<import('./hrTypes').HrBiometricDevice>>(endpoints.hrBiometricDevices, { params }).then((r) => r.data);

export const getBiometricDevice = (id: number) =>
    apiClient.get<ApiResource<import('./hrTypes').HrBiometricDevice>>(`${endpoints.hrBiometricDevices}/${id}`).then((r) => r.data.data);

export const createBiometricDevice = (payload: import('./hrTypes').HrBiometricDevicePayload) =>
    apiClient.post<ApiResource<import('./hrTypes').HrBiometricDevice>>(endpoints.hrBiometricDevices, payload).then((r) => r.data.data);

export const updateBiometricDevice = (id: number, payload: Partial<import('./hrTypes').HrBiometricDevicePayload> & { expected_version: number }) =>
    apiClient.put<ApiResource<import('./hrTypes').HrBiometricDevice>>(`${endpoints.hrBiometricDevices}/${id}`, payload).then((r) => r.data.data);

export const deleteBiometricDevice = (id: number) =>
    apiClient.delete(`${endpoints.hrBiometricDevices}/${id}`).then((r) => r.data);

export const pingBiometricDevice = (id: number) =>
    apiClient.post<ApiResource<import('./hrTypes').DeviceHealthResult>>(`${endpoints.hrBiometricDevices}/${id}/ping`).then((r) => r.data.data);

export const syncBiometricDevice = (id: number, async = false) =>
    apiClient.post(`${endpoints.hrBiometricDevices}/${id}/sync`, { async }).then((r) => r.data);

export const listBiometricDeviceUsers = (deviceId: number) =>
    apiClient.get<ApiResource<import('./hrTypes').HrBiometricDeviceUser[]>>(`${endpoints.hrBiometricDevices}/${deviceId}/users`).then((r) => r.data.data);

export const mapBiometricDeviceUser = (deviceId: number, payload: { biometric_user_id: string; employee_id: number; card_number?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrBiometricDeviceUser>>(`${endpoints.hrBiometricDevices}/${deviceId}/users`, payload).then((r) => r.data.data);

export const unmapBiometricDeviceUser = (deviceId: number, mappingId: number) =>
    apiClient.delete(`${endpoints.hrBiometricDevices}/${deviceId}/users/${mappingId}`).then((r) => r.data);

export const listBiometricRawLogs = (deviceId: number, params?: { sync_status?: string; date?: string; page?: number; per_page?: number }) =>
    apiClient.get<ApiCollection<import('./hrTypes').HrBiometricRawPunch>>(`${endpoints.hrBiometricDevices}/${deviceId}/raw-logs`, { params }).then((r) => r.data);

export const reprocessBiometricDeviceLogs = (deviceId: number) =>
    apiClient.post<{ message: string; count: number }>(`${endpoints.hrBiometricDevices}/${deviceId}/reprocess`).then((r) => r.data);

export const uploadBiometricFileLog = (deviceId: number, file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    return apiClient.post<{ message: string; stats: Record<string, number> }>(`${endpoints.hrBiometricDevices}/${deviceId}/upload-log`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    }).then((r) => r.data);
};

export function searchBiometricDevices(params: LookupLoadParams): Promise<LookupResult<import('./hrTypes').HrBiometricDevice>> {
    return requestLookup<import('./hrTypes').HrBiometricDevice>(`${endpoints.hrBiometricDevices}/lookup`, params);
}

// --- Attendance Capture & Anomaly API ---
export const recordWebPunch = (payload: { employee_id: number; punch_type: string; notes?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrAttendancePunch>>(endpoints.hrAttendanceCaptureWeb, payload).then((r) => r.data.data);

export const recordMobilePunch = (payload: { employee_id: number; punch_type: string; latitude: number; longitude: number; accuracy_meters?: number; notes?: string; geofence_id?: number }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrAttendancePunch>>(endpoints.hrAttendanceCaptureMobile, payload).then((r) => r.data.data);

export const recordRfidPunch = (payload: { card_uid: string; punch_type: string; device_id?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrAttendancePunch>>(endpoints.hrAttendanceCaptureRfid, payload).then((r) => r.data.data);

export const recordBarcodePunch = (payload: { barcode: string; punch_type: string; device_id?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrAttendancePunch>>(endpoints.hrAttendanceCaptureBarcode, payload).then((r) => r.data.data);

export const getKioskQrToken = (kioskId: number) =>
    apiClient.get<ApiResource<{ token: string; expires_in_seconds: number; kiosk_code: string }>>(`${endpoints.hrAttendanceCaptureKiosks}/${kioskId}/qr-token`).then((r) => r.data.data);

export const scanKioskQr = (payload: { employee_id: number; qr_token: string; punch_type: string; latitude?: number; longitude?: number }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrAttendancePunch>>(endpoints.hrAttendanceCaptureQrScan, payload).then((r) => r.data.data);

export const previewAttendanceImport = (payload: { file?: File; csv_content?: string }) => {
    if (payload.file) {
        const formData = new FormData();
        formData.append('file', payload.file);
        return apiClient.post<ApiResource<import('./hrTypes').BatchImportPreview>>(endpoints.hrAttendanceCaptureImportPreview, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }).then((r) => r.data.data);
    }
    return apiClient.post<ApiResource<import('./hrTypes').BatchImportPreview>>(endpoints.hrAttendanceCaptureImportPreview, { csv_content: payload.csv_content }).then((r) => r.data.data);
};

export const commitAttendanceImport = (payload: { file?: File; file_name?: string; csv_content?: string }) => {
    if (payload.file) {
        const formData = new FormData();
        formData.append('file', payload.file);
        return apiClient.post<ApiResource<unknown>>(endpoints.hrAttendanceCaptureImportCommit, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }).then((r) => r.data.data);
    }
    return apiClient.post<ApiResource<unknown>>(endpoints.hrAttendanceCaptureImportCommit, {
        file_name: payload.file_name,
        csv_content: payload.csv_content,
    }).then((r) => r.data.data);
};

export const listAttendanceAnomalies = (params?: { date?: string; employee_id?: number; anomaly_type?: string; page?: number }) =>
    apiClient.get<ApiCollection<import('./hrTypes').HrAttendanceRecord>>(endpoints.hrAttendanceAnomalies, { params }).then((r) => r.data);

export const scanAttendanceAnomalies = (params?: { date?: string; employee_id?: number }) =>
    apiClient.post<ApiResource<{ total_scanned: number; anomalies_detected: number }>>(`${endpoints.hrAttendanceAnomalies}/scan`, params).then((r) => r.data.data);

export const quickResolveAttendanceAnomaly = (recordId: number, payload: { in_time?: string; out_time?: string; resolution_reason: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrAttendanceRecord>>(`${endpoints.hrAttendanceAnomalies}/${recordId}/quick-resolve`, payload).then((r) => r.data.data);

export const listEmployeeCredentials = (employeeId: number) =>
    apiClient.get<ApiResource<import('./hrTypes').HrEmployeeAttendanceCredential[]>>(`${endpoints.hrEmployees}/${employeeId}/credentials`).then((r) => r.data.data);

export const storeEmployeeCredential = (employeeId: number, payload: { credential_type: string; credential_value: string; label?: string; expires_at?: string }) =>
    apiClient.post<ApiResource<import('./hrTypes').HrEmployeeAttendanceCredential>>(`${endpoints.hrEmployees}/${employeeId}/credentials`, payload).then((r) => r.data.data);

export const updateEmployeeCredentialStatus = (employeeId: number, credentialId: number, payload: { status: string; revocation_reason?: string }) =>
    apiClient.patch<ApiResource<import('./hrTypes').HrEmployeeAttendanceCredential>>(`${endpoints.hrEmployees}/${employeeId}/credentials/${credentialId}/status`, payload).then((r) => r.data.data);

export const deleteEmployeeCredential = (employeeId: number, credentialId: number) =>
    apiClient.delete(`${endpoints.hrEmployees}/${employeeId}/credentials/${credentialId}`).then((r) => r.data);

export const listAttendanceGeofences = () =>
    apiClient.get<ApiResource<import('./hrTypes').HrAttendanceGeofence[]>>(endpoints.hrAttendanceGeofences).then((r) => r.data.data);

export const listAttendanceKiosks = () =>
    apiClient.get<ApiResource<import('./hrTypes').HrAttendanceKiosk[]>>(endpoints.hrAttendanceKiosks).then((r) => r.data.data);
