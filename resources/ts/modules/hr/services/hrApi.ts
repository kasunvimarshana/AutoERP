import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    attendanceRecords,
    departments,
    designations,
    employees,
    employmentTypes,
    getDepartmentById,
    getDesignationById,
    getEmployeeById,
    hrDashboardMetrics,
    leaveRecords,
    salaryProfiles,
} from '../mock/hrMock';
import type {
    Department,
    Designation,
    Employee,
    EmployeeAddress,
    EmployeeAttendanceRecord,
    EmployeeContact,
    EmployeeDocument,
    EmployeeLeaveRecord,
    EmployeeSalaryProfile,
    EmployeeUserAccess,
    EmploymentDetails,
    EmploymentType,
    HrDashboardMetric,
} from '../types/hr.types';

type BackendRecord = Record<string, unknown>;

const HR_API_MODE = import.meta.env.VITE_HR_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return HR_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419, 422]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (HR_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asOptionalString(value: unknown) {
    return value === null || value === undefined || String(value).trim() === '' ? undefined : String(value);
}

function asBoolean(value: unknown, fallback = false) {
    if (typeof value === 'boolean') {
        return value;
    }

    if (value === 1 || value === '1' || value === 'true') {
        return true;
    }

    if (value === 0 || value === '0' || value === 'false') {
        return false;
    }

    return fallback;
}

function asRecordArray(value: unknown): BackendRecord[] {
    return Array.isArray(value) ? value.filter((entry): entry is BackendRecord => typeof entry === 'object' && entry !== null && !Array.isArray(entry)) : [];
}

function normalizeContact(raw: BackendRecord): EmployeeContact {
    return {
        contactType: asString(raw.contact_type, 'mobile'),
        email: asOptionalString(raw.email),
        id: asString(raw.id),
        isEmergency: asBoolean(raw.is_emergency),
        isPrimary: asBoolean(raw.is_primary),
        name: asString(raw.contact_name ?? raw.name),
        phone: asString(raw.phone ?? raw.mobile),
        relationship: asOptionalString(raw.relationship),
        status: asBoolean(raw.is_active, true) ? 'active' : 'inactive',
    };
}

function normalizeAddress(raw: BackendRecord): EmployeeAddress {
    return {
        addressLine1: asString(raw.address_line_1),
        addressLine2: asOptionalString(raw.address_line_2),
        addressType: asString(raw.address_type, 'current'),
        city: asString(raw.city),
        countryName: asString(raw.country_name ?? raw.country),
        id: asString(raw.id),
        isActive: asBoolean(raw.is_active, true),
        isPrimary: asBoolean(raw.is_primary),
        postalCode: asOptionalString(raw.postal_code),
    };
}

function normalizeEmploymentDetails(raw: BackendRecord, fallback: EmploymentDetails): EmploymentDetails {
    return {
        department: asString(raw.department_name ?? raw.department_id, fallback.department),
        designation: asString(raw.designation_name ?? raw.designation_id, fallback.designation),
        employmentStatus: asString(raw.employment_status, fallback.employmentStatus) as EmploymentDetails['employmentStatus'],
        employmentType: asString(raw.employment_type_name ?? raw.employment_type_id, fallback.employmentType),
        joiningDate: asString(raw.joining_date, fallback.joiningDate),
        leavingDate: asOptionalString(raw.leaving_date) ?? fallback.leavingDate,
        manager: asString(raw.reporting_manager_name ?? raw.reporting_manager_id, fallback.manager),
        probationEndDate: asOptionalString(raw.probation_end_date) ?? fallback.probationEndDate,
        workLocation: asString(raw.work_location_name ?? raw.work_location_id, fallback.workLocation),
    };
}

function normalizeUserAccess(raw: BackendRecord): EmployeeUserAccess {
    return {
        accessRole: asString(raw.access_role, 'employee_portal'),
        accessStatus: asString(raw.access_status, 'active'),
        activatedAt: asOptionalString(raw.activated_at),
        id: asString(raw.id),
        invitedAt: asOptionalString(raw.invited_at),
        isPrimary: asBoolean(raw.is_primary),
        userEmail: asString(raw.user_email ?? raw.email ?? raw.user_id),
        userName: asString(raw.user_name ?? raw.name ?? raw.user_id),
    };
}

function normalizeSalaryProfile(raw: BackendRecord): EmployeeSalaryProfile {
    return {
        backendPayablePreview: asString(raw.backend_payable_preview ?? raw.payable_preview, 'Backend calculated'),
        effectiveFrom: asString(raw.effective_from),
        employee: asString(raw.employee_name ?? raw.employee_id),
        id: asString(raw.id),
        paymentMethod: asString(raw.payment_method_name ?? raw.payment_method_id ?? raw.bank_account_reference),
        salaryType: asString(raw.salary_type, 'Backend profile'),
        status: asBoolean(raw.is_active, true) ? 'active' : 'inactive',
    };
}

function normalizeEmployee(raw: BackendRecord): Employee {
    const fallback = getEmployeeById(asString(raw.id ?? raw.employee_code));
    const employmentDetails = typeof raw.employment_details === 'object' && raw.employment_details !== null && !Array.isArray(raw.employment_details)
        ? normalizeEmploymentDetails(raw.employment_details as BackendRecord, fallback.employmentDetails)
        : fallback.employmentDetails;
    const salaryProfile = typeof raw.salary_profile === 'object' && raw.salary_profile !== null && !Array.isArray(raw.salary_profile)
        ? normalizeSalaryProfile(raw.salary_profile as BackendRecord)
        : fallback.salaryProfile;

    return {
        ...fallback,
        addresses: asRecordArray(raw.addresses).length ? asRecordArray(raw.addresses).map(normalizeAddress) : fallback.addresses,
        code: asString(raw.employee_code, fallback.code),
        contacts: asRecordArray(raw.contacts).length ? asRecordArray(raw.contacts).map(normalizeContact) : fallback.contacts,
        department: asString(raw.department_name ?? raw.department_id, fallback.department),
        designation: asString(raw.designation_name ?? raw.designation_id, fallback.designation),
        displayName: asString(raw.display_name ?? raw.full_name, fallback.displayName),
        email: asString(raw.email, fallback.email),
        employmentDetails,
        employmentType: asString(raw.employment_type_name ?? raw.employment_type_id, fallback.employmentType),
        firstName: asString(raw.first_name, fallback.firstName),
        fullName: asString(raw.full_name ?? raw.display_name, fallback.fullName),
        id: asString(raw.id, fallback.id),
        isActive: asBoolean(raw.is_active, fallback.isActive),
        joiningDate: asString(raw.joining_date, fallback.joiningDate),
        lastName: asString(raw.last_name, fallback.lastName),
        mobile: asString(raw.mobile, fallback.mobile),
        phone: asString(raw.phone, fallback.phone),
        reportingManager: asString(raw.reporting_manager_name ?? raw.reporting_manager_id, fallback.reportingManager),
        salaryProfile,
        status: asString(raw.employment_status ?? raw.status, fallback.status) as Employee['status'],
        userAccess: asRecordArray(raw.user_accesses ?? raw.user_access).length ? asRecordArray(raw.user_accesses ?? raw.user_access).map(normalizeUserAccess) : fallback.userAccess,
    };
}

function normalizeDepartment(raw: BackendRecord): Department {
    const fallback = getDepartmentById(asString(raw.id ?? raw.department_code));

    return {
        ...fallback,
        code: asString(raw.department_code, fallback.code),
        employeeCount: asString(raw.employee_count, fallback.employeeCount),
        id: asString(raw.id, fallback.id),
        isActive: asBoolean(raw.is_active, fallback.isActive),
        manager: asString(raw.manager_employee_name ?? raw.manager_employee_id, fallback.manager),
        name: asString(raw.department_name, fallback.name),
        parentDepartment: asString(raw.parent_department_name ?? raw.parent_id, fallback.parentDepartment),
        status: asBoolean(raw.is_active, fallback.isActive) ? 'active' : 'inactive',
        updatedAt: asString(raw.updated_at, fallback.updatedAt),
    };
}

function normalizeDesignation(raw: BackendRecord): Designation {
    const fallback = getDesignationById(asString(raw.id ?? raw.designation_code));

    return {
        ...fallback,
        code: asString(raw.designation_code, fallback.code),
        department: asString(raw.department_name ?? raw.department_id, fallback.department),
        employeeCount: asString(raw.employee_count, fallback.employeeCount),
        id: asString(raw.id, fallback.id),
        isActive: asBoolean(raw.is_active, fallback.isActive),
        name: asString(raw.designation_name, fallback.name),
        status: asBoolean(raw.is_active, fallback.isActive) ? 'active' : 'inactive',
        updatedAt: asString(raw.updated_at, fallback.updatedAt),
    };
}

function normalizeEmploymentType(raw: BackendRecord): EmploymentType {
    return {
        code: asString(raw.employment_type_code ?? raw.code),
        description: asString(raw.description),
        id: asString(raw.id),
        isActive: asBoolean(raw.is_active, true),
        name: asString(raw.employment_type_name ?? raw.name),
        status: asBoolean(raw.is_active, true) ? 'active' : 'inactive',
    };
}

export const hrApi = {
    dashboard: {
        summary: (): Promise<ApiCollectionResponse<HrDashboardMetric>> => mockCollectionResponse(hrDashboardMetrics),
    },
    employees: {
        list: (): Promise<ApiCollectionResponse<Employee>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/employees');
                return { ...response, data: response.data.map(normalizeEmployee) };
            },
            () => mockCollectionResponse(employees),
        ),
        get: (id: string): Promise<ApiResponse<Employee>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${id}`);
                return { ...response, data: normalizeEmployee(response.data) };
            },
            () => mockResponse(getEmployeeById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/hr/employees', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        changeStatus: (id: string, employmentStatus: Employee['status'], reason?: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${id}/status`, { body: { employment_status: employmentStatus, reason }, method: 'PATCH' }), () => mockResponse({ employmentStatus, id, reason })),
        lookup: (query = '') => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>('/api/hr/employees/lookup', { query: { q: query } }), () => mockCollectionResponse(employees)),
        active: () => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>('/api/hr/employees/active'), () => mockCollectionResponse(employees.filter((employee) => employee.isActive))),
        byDepartment: (departmentId: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>(`/api/hr/employees/by-department/${departmentId}`), () => mockCollectionResponse(employees)),
        byDesignation: (designationId: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>(`/api/hr/employees/by-designation/${designationId}`), () => mockCollectionResponse(employees)),
        validateForAssignmentContext: (id: string, context: string) => withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${id}/validate/assignment-context/${context}`),
            () => mockResponse({ context, employeeId: id, valid: 'Backend validation result' }),
        ),
    },
    contacts: {
        list: (employeeId: string): Promise<ApiCollectionResponse<EmployeeContact>> => withMockFallback(() => httpClient<ApiCollectionResponse<EmployeeContact>>(`/api/hr/employees/${employeeId}/contacts`), () => mockCollectionResponse(getEmployeeById(employeeId).contacts)),
        create: (employeeId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/contacts`, { body: input, method: 'POST' }), () => mockResponse({ employeeId, input })),
        update: (employeeId: string, contactId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/contacts/${contactId}`, { body: input, method: 'PUT' }), () => mockResponse({ contactId, employeeId, input })),
    },
    addresses: {
        list: (employeeId: string): Promise<ApiCollectionResponse<EmployeeAddress>> => withMockFallback(() => httpClient<ApiCollectionResponse<EmployeeAddress>>(`/api/hr/employees/${employeeId}/addresses`), () => mockCollectionResponse(getEmployeeById(employeeId).addresses)),
        create: (employeeId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/addresses`, { body: input, method: 'POST' }), () => mockResponse({ employeeId, input })),
        update: (employeeId: string, addressId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/addresses/${addressId}`, { body: input, method: 'PUT' }), () => mockResponse({ addressId, employeeId, input })),
    },
    employmentDetails: {
        get: (employeeId: string): Promise<ApiResponse<EmploymentDetails>> => withMockFallback(() => httpClient<ApiResponse<EmploymentDetails>>(`/api/hr/employees/${employeeId}/employment-details`), () => mockResponse(getEmployeeById(employeeId).employmentDetails)),
        update: (employeeId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/employment-details`, { body: input, method: 'PUT' }), () => mockResponse({ employeeId, input })),
    },
    userAccess: {
        list: (employeeId: string): Promise<ApiCollectionResponse<EmployeeUserAccess>> => withMockFallback(() => httpClient<ApiCollectionResponse<EmployeeUserAccess>>(`/api/hr/employees/${employeeId}/user-accesses`), () => mockCollectionResponse(getEmployeeById(employeeId).userAccess)),
        create: (employeeId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/user-accesses`, { body: input, method: 'POST' }), () => mockResponse({ employeeId, input })),
        linkExisting: (employeeId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/user-accesses/link-existing`, { body: input, method: 'POST' }), () => mockResponse({ employeeId, input })),
        deactivate: (employeeId: string, accessId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/user-accesses/${accessId}/deactivate`, { body: input, method: 'PATCH' }), () => mockResponse({ accessId, employeeId, input })),
        unlink: (employeeId: string, accessId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/user-accesses/${accessId}`, { method: 'DELETE' }), () => mockResponse({ accessId, employeeId })),
    },
    departments: {
        list: (): Promise<ApiCollectionResponse<Department>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/departments');
                return { ...response, data: response.data.map(normalizeDepartment) };
            },
            () => mockCollectionResponse(departments),
        ),
        get: (id: string): Promise<ApiResponse<Department>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/departments/${id}`);
                return { ...response, data: normalizeDepartment(response.data) };
            },
            () => mockResponse(getDepartmentById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/hr/departments', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/departments/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
    },
    designations: {
        list: (): Promise<ApiCollectionResponse<Designation>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/designations');
                return { ...response, data: response.data.map(normalizeDesignation) };
            },
            () => mockCollectionResponse(designations),
        ),
        get: (id: string): Promise<ApiResponse<Designation>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/designations/${id}`);
                return { ...response, data: normalizeDesignation(response.data) };
            },
            () => mockResponse(getDesignationById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/hr/designations', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/designations/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
    },
    employmentTypes: {
        list: (): Promise<ApiCollectionResponse<EmploymentType>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/employment-types');
                return { ...response, data: response.data.map(normalizeEmploymentType) };
            },
            () => mockCollectionResponse(employmentTypes),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/hr/employment-types', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/hr/employment-types/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
    },
    documents: {
        list: (): Promise<ApiCollectionResponse<EmployeeDocument>> => mockCollectionResponse(employees.flatMap((employee) => employee.documents)),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/hr/employee-documents', { body: input, method: 'POST' }), () => mockResponse(input)),
    },
    attendance: {
        list: (): Promise<ApiCollectionResponse<EmployeeAttendanceRecord>> => withMockFallback(() => httpClient<ApiCollectionResponse<EmployeeAttendanceRecord>>('/api/hr/attendance-records'), () => mockCollectionResponse(attendanceRecords)),
    },
    leave: {
        list: (): Promise<ApiCollectionResponse<EmployeeLeaveRecord>> => withMockFallback(() => httpClient<ApiCollectionResponse<EmployeeLeaveRecord>>('/api/hr/leave-applications'), () => mockCollectionResponse(leaveRecords)),
    },
    salaryProfiles: {
        list: (): Promise<ApiCollectionResponse<EmployeeSalaryProfile>> => withMockFallback(() => httpClient<ApiCollectionResponse<EmployeeSalaryProfile>>('/api/hr/employee-salary-assignments'), () => mockCollectionResponse(salaryProfiles)),
    },
};

export const listEmployees = hrApi.employees.list;
export const createEmployee = hrApi.employees.create;
