import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type {
    Department,
    DepartmentFormInput,
    Designation,
    DesignationFormInput,
    Employee,
    EmployeeAddress,
    EmployeeAddressFormInput,
    EmployeeAttendanceRecord,
    EmployeeContact,
    EmployeeContactFormInput,
    EmployeeDocument,
    EmployeeFormInput,
    EmployeeLeaveRecord,
    EmployeeSalaryProfile,
    EmployeeStatus,
    EmployeeUserAccess,
    EmployeeUserAccessCreateInput,
    EmployeeUserAccessLinkInput,
    EmploymentDetails,
    EmploymentDetailsFormInput,
    EmploymentType,
    EmploymentTypeFormInput,
    HrDashboardMetric,
} from '../types/hr.types';

type BackendRecord = Record<string, unknown>;

type EmployeeListQuery = {
    departmentId?: string;
    designationId?: string;
    page?: number;
    perPage?: number;
    search?: string;
    status?: EmployeeStatus;
};

type MasterListQuery = {
    page?: number;
    perPage?: number;
    search?: string;
    isActive?: boolean;
};

function asRecord(value: unknown): BackendRecord {
    return value && typeof value === 'object' && !Array.isArray(value) ? (value as BackendRecord) : {};
}

function asRecordArray(value: unknown): BackendRecord[] {
    return Array.isArray(value) ? value.filter((entry): entry is BackendRecord => typeof entry === 'object' && entry !== null && !Array.isArray(entry)) : [];
}

function asString(value: unknown, fallback = ''): string {
    if (value === null || value === undefined) {
        return fallback;
    }

    return String(value);
}

function asOptionalString(value: unknown): string | undefined {
    const normalized = asString(value).trim();

    return normalized === '' ? undefined : normalized;
}

function asBoolean(value: unknown, fallback = false): boolean {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value === 1;
    }

    if (typeof value === 'string') {
        return ['1', 'true', 'yes'].includes(value.toLowerCase());
    }

    return fallback;
}

function normalizeEmployeeStatus(value: unknown): EmployeeStatus {
    const status = asString(value, 'draft').toLowerCase();
    const allowed: EmployeeStatus[] = ['active', 'archived', 'draft', 'inactive', 'on_leave', 'resigned', 'suspended', 'terminated'];

    return allowed.includes(status as EmployeeStatus) ? (status as EmployeeStatus) : 'draft';
}

function normalizeContact(raw: BackendRecord): EmployeeContact {
    return {
        contactType: asString(raw.contact_type, 'mobile'),
        email: asOptionalString(raw.email),
        id: asString(raw.id),
        isEmergency: asBoolean(raw.is_emergency),
        isPrimary: asBoolean(raw.is_primary),
        mobile: asOptionalString(raw.mobile),
        name: asString(raw.contact_name ?? raw.name),
        phone: asOptionalString(raw.phone ?? raw.mobile),
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
        stateProvince: asOptionalString(raw.state_province),
    };
}

function normalizeEmploymentDetails(raw: BackendRecord, employee: BackendRecord = {}): EmploymentDetails {
    return {
        department: asString(raw.department_name ?? raw.department_id ?? employee.department_name ?? employee.department_id),
        departmentId: asOptionalString(raw.department_id ?? employee.department_id),
        designation: asString(raw.designation_name ?? raw.designation_id ?? employee.designation_name ?? employee.designation_id),
        designationId: asOptionalString(raw.designation_id ?? employee.designation_id),
        employmentStatus: normalizeEmployeeStatus(raw.employment_status ?? employee.employment_status),
        employmentType: asString(raw.employment_type_name ?? raw.employment_type_id ?? employee.employment_type_name ?? employee.employment_type_id),
        employmentTypeId: asOptionalString(raw.employment_type_id ?? employee.employment_type_id),
        joiningDate: asString(raw.joining_date ?? employee.joining_date),
        leavingDate: asOptionalString(raw.leaving_date ?? employee.leaving_date),
        manager: asString(raw.reporting_manager_name ?? raw.reporting_manager_id ?? employee.reporting_manager_name ?? employee.reporting_manager_id),
        probationEndDate: asOptionalString(raw.probation_end_date),
        reportingManagerId: asOptionalString(raw.reporting_manager_id ?? employee.reporting_manager_id),
        workLocation: asString(raw.work_location_name ?? raw.work_location_id),
    };
}

function normalizeUserAccess(raw: BackendRecord): EmployeeUserAccess {
    return {
        accessRole: asString(raw.access_role, 'employee_portal'),
        accessStatus: asString(raw.access_status ?? raw.status, 'active'),
        activatedAt: asOptionalString(raw.activated_at),
        id: asString(raw.id),
        invitedAt: asOptionalString(raw.invited_at),
        isPrimary: asBoolean(raw.is_primary),
        userEmail: asString(raw.user_email ?? raw.email ?? raw.user_id),
        userName: asString(raw.user_name ?? raw.name ?? raw.user_id),
    };
}

function normalizeDocument(raw: BackendRecord): EmployeeDocument {
    return {
        documentNumber: asString(raw.document_number ?? raw.reference_number ?? raw.id),
        documentType: asString(raw.document_type ?? raw.type),
        expiryDate: asOptionalString(raw.expiry_date),
        id: asString(raw.id),
        status: asString(raw.status, 'active'),
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
    const employmentDetailsRecord = asRecord(raw.employment_details);
    const salaryProfileRecord = asRecord(raw.salary_profile);
    const status = normalizeEmployeeStatus(raw.employment_status ?? raw.status);

    return {
        addresses: asRecordArray(raw.addresses).map(normalizeAddress),
        audit: [],
        code: asString(raw.employee_code ?? raw.code),
        contacts: asRecordArray(raw.contacts).map(normalizeContact),
        department: asString(raw.department_name ?? raw.department_id),
        departmentId: asOptionalString(raw.department_id),
        designation: asString(raw.designation_name ?? raw.designation_id),
        designationId: asOptionalString(raw.designation_id),
        displayName: asString(raw.display_name ?? raw.full_name ?? raw.first_name),
        documents: asRecordArray(raw.documents).map(normalizeDocument),
        email: asOptionalString(raw.email),
        employmentDetails: normalizeEmploymentDetails(employmentDetailsRecord, raw),
        employmentType: asString(raw.employment_type_name ?? raw.employment_type_id),
        employmentTypeId: asOptionalString(raw.employment_type_id),
        firstName: asString(raw.first_name),
        fullName: asString(raw.full_name ?? raw.display_name ?? raw.first_name),
        id: asString(raw.id),
        isActive: asBoolean(raw.is_active, status === 'active'),
        joiningDate: asString(raw.joining_date),
        lastName: asOptionalString(raw.last_name),
        mobile: asOptionalString(raw.mobile),
        notes: asOptionalString(raw.notes),
        phone: asOptionalString(raw.phone),
        reportingManager: asOptionalString(raw.reporting_manager_name ?? raw.reporting_manager_id),
        reportingManagerId: asOptionalString(raw.reporting_manager_id),
        salaryProfile: Object.keys(salaryProfileRecord).length ? normalizeSalaryProfile(salaryProfileRecord) : undefined,
        status,
        userAccess: asRecordArray(raw.user_accesses ?? raw.user_access ?? raw.user_accounts).map(normalizeUserAccess),
    };
}

function normalizeDepartment(raw: BackendRecord): Department {
    return {
        code: asString(raw.department_code ?? raw.code),
        description: asOptionalString(raw.description),
        employeeCount: asString(raw.employee_count, 'Backend count'),
        id: asString(raw.id),
        isActive: asBoolean(raw.is_active, true),
        manager: asString(raw.manager_employee_name ?? raw.manager_employee_id),
        managerEmployeeId: asOptionalString(raw.manager_employee_id),
        name: asString(raw.department_name ?? raw.name),
        parentDepartment: asOptionalString(raw.parent_department_name ?? raw.parent_id),
        parentId: asOptionalString(raw.parent_id),
        status: asBoolean(raw.is_active, true) ? 'active' : 'inactive',
        updatedAt: asString(raw.updated_at),
    };
}

function normalizeDesignation(raw: BackendRecord): Designation {
    return {
        code: asString(raw.designation_code ?? raw.code),
        department: asString(raw.department_name ?? raw.department_id),
        departmentId: asOptionalString(raw.department_id),
        description: asOptionalString(raw.description),
        employeeCount: asString(raw.employee_count, 'Backend count'),
        id: asString(raw.id),
        isActive: asBoolean(raw.is_active, true),
        name: asString(raw.designation_name ?? raw.name),
        status: asBoolean(raw.is_active, true) ? 'active' : 'inactive',
        updatedAt: asString(raw.updated_at),
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

function toNullableNumber(value: string | undefined): number | null {
    if (!value || value.trim() === '') {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) && number > 0 ? number : null;
}

function toBackendEmployeePayload(input: EmployeeFormInput): BackendRecord {
    const payload: BackendRecord = {
        create_user: false,
        department_id: toNullableNumber(input.departmentId),
        designation_id: toNullableNumber(input.designationId),
        display_name: input.displayName || null,
        email: input.email || null,
        employee_code: input.code,
        employment_status: input.status || 'draft',
        employment_type_id: toNullableNumber(input.employmentTypeId),
        first_name: input.firstName,
        joining_date: input.joiningDate || null,
        last_name: input.lastName || null,
        leaving_date: input.leavingDate || null,
        mobile: input.mobile || null,
        notes: input.notes || null,
        phone: input.phone || null,
        reporting_manager_id: toNullableNumber(input.reportingManagerId),
    };

    if (input.primaryContact) {
        const hasContactInput = [
            input.primaryContact.name,
            input.primaryContact.email,
            input.primaryContact.phone,
            input.primaryContact.mobile,
            input.primaryContact.relationship,
        ].some((value) => (value ?? '').trim() !== '');

        if (hasContactInput) {
            payload.contacts = [toBackendContactPayload(input.primaryContact)];
        }
    }

    if (input.primaryAddress) {
        const hasAddressInput = [
            input.primaryAddress.addressLine1,
            input.primaryAddress.city,
            input.primaryAddress.countryName,
            input.primaryAddress.postalCode,
        ].some((value) => (value ?? '').trim() !== '');

        if (hasAddressInput) {
            payload.addresses = [toBackendAddressPayload(input.primaryAddress)];
        }
    }

    return payload;
}

function toBackendContactPayload(input: EmployeeContact | EmployeeContactFormInput): BackendRecord {
    return {
        contact_name: input.name,
        contact_type: input.contactType || null,
        email: input.email || null,
        is_active: 'status' in input ? input.status !== 'inactive' : true,
        is_emergency: input.isEmergency ?? false,
        is_primary: input.isPrimary ?? false,
        mobile: input.mobile || null,
        phone: input.phone || null,
        relationship: input.relationship || null,
    };
}

function toBackendAddressPayload(input: EmployeeAddress | EmployeeAddressFormInput): BackendRecord {
    return {
        address_line_1: input.addressLine1,
        address_line_2: input.addressLine2 || null,
        address_type: input.addressType || 'current',
        city: input.city,
        country_name: input.countryName || null,
        is_active: 'isActive' in input ? input.isActive : true,
        is_primary: input.isPrimary ?? false,
        postal_code: input.postalCode || null,
        state_province: input.stateProvince || null,
    };
}

function toBackendEmploymentDetailsPayload(input: EmploymentDetailsFormInput): BackendRecord {
    return {
        department_id: toNullableNumber(input.departmentId),
        designation_id: toNullableNumber(input.designationId),
        employment_status: input.employmentStatus || null,
        employment_type_id: toNullableNumber(input.employmentTypeId),
        joining_date: input.joiningDate || null,
        leaving_date: input.leavingDate || null,
        probation_end_date: input.probationEndDate || null,
        reporting_manager_id: toNullableNumber(input.reportingManagerId),
    };
}

function toBackendDepartmentPayload(input: DepartmentFormInput): BackendRecord {
    return {
        department_code: input.code,
        department_name: input.name,
        description: input.description || null,
        is_active: input.isActive,
        manager_employee_id: toNullableNumber(input.managerEmployeeId),
        parent_id: toNullableNumber(input.parentId),
    };
}

function toBackendDesignationPayload(input: DesignationFormInput): BackendRecord {
    return {
        department_id: toNullableNumber(input.departmentId),
        designation_code: input.code,
        designation_name: input.name,
        description: input.description || null,
        is_active: input.isActive,
    };
}

function toBackendEmploymentTypePayload(input: EmploymentTypeFormInput): BackendRecord {
    return {
        description: input.description || null,
        employment_type_code: input.code,
        employment_type_name: input.name,
        is_active: input.isActive,
    };
}

export const hrApi = {
    addresses: {
        create: async (employeeId: string, input: EmployeeAddressFormInput): Promise<ApiResponse<EmployeeAddress>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${employeeId}/addresses`, {
                body: toBackendAddressPayload(input),
                method: 'POST',
            });

            return { ...response, data: normalizeAddress(response.data) };
        },
        list: async (employeeId: string): Promise<ApiCollectionResponse<EmployeeAddress>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/hr/employees/${employeeId}/addresses`);

            return { ...response, data: response.data.map(normalizeAddress) };
        },
        update: async (employeeId: string, addressId: string, input: EmployeeAddressFormInput): Promise<ApiResponse<EmployeeAddress>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${employeeId}/addresses/${addressId}`, {
                body: toBackendAddressPayload(input),
                method: 'PUT',
            });

            return { ...response, data: normalizeAddress(response.data) };
        },
    },
    attendance: {
        list: async (): Promise<ApiCollectionResponse<EmployeeAttendanceRecord>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/attendance-records');

            return {
                ...response,
                data: response.data.map((raw) => ({
                    attendanceDate: asString(raw.attendance_date),
                    backendTotal: asString(raw.backend_total ?? raw.total_hours, 'Backend calculated'),
                    employee: asString(raw.employee_name ?? raw.employee_id),
                    id: asString(raw.id),
                    status: asString(raw.status),
                })),
            };
        },
    },
    contacts: {
        create: async (employeeId: string, input: EmployeeContactFormInput): Promise<ApiResponse<EmployeeContact>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${employeeId}/contacts`, {
                body: toBackendContactPayload(input),
                method: 'POST',
            });

            return { ...response, data: normalizeContact(response.data) };
        },
        list: async (employeeId: string): Promise<ApiCollectionResponse<EmployeeContact>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/hr/employees/${employeeId}/contacts`);

            return { ...response, data: response.data.map(normalizeContact) };
        },
        update: async (employeeId: string, contactId: string, input: EmployeeContactFormInput): Promise<ApiResponse<EmployeeContact>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${employeeId}/contacts/${contactId}`, {
                body: toBackendContactPayload(input),
                method: 'PUT',
            });

            return { ...response, data: normalizeContact(response.data) };
        },
    },
    dashboard: {
        summary: async (): Promise<ApiCollectionResponse<HrDashboardMetric>> => {
            const [employees, activeEmployees, departments, designations] = await Promise.all([
                hrApi.employees.list({ perPage: 1 }),
                hrApi.employees.active(),
                hrApi.departments.list({ perPage: 1 }),
                hrApi.designations.list({ perPage: 1 }),
            ]);

            return {
                data: [
                    { label: 'Employees', tone: 'backend', value: asString(employees.meta?.total ?? employees.data.length) },
                    { label: 'Active employees', tone: 'backend', value: String(activeEmployees.data.length) },
                    { label: 'Departments', tone: 'backend', value: asString(departments.meta?.total ?? departments.data.length) },
                    { label: 'Designations', tone: 'backend', value: asString(designations.meta?.total ?? designations.data.length) },
                ],
            };
        },
    },
    departments: {
        create: async (input: DepartmentFormInput): Promise<ApiResponse<Department>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/hr/departments', {
                body: toBackendDepartmentPayload(input),
                method: 'POST',
            });

            return { ...response, data: normalizeDepartment(response.data) };
        },
        get: async (id: string): Promise<ApiResponse<Department>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/departments/${id}`);

            return { ...response, data: normalizeDepartment(response.data) };
        },
        list: async (query: MasterListQuery = {}): Promise<ApiCollectionResponse<Department>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/departments', {
                query: {
                    is_active: query.isActive,
                    page: query.page,
                    per_page: query.perPage ?? 100,
                    search: query.search,
                },
            });

            return { ...response, data: response.data.map(normalizeDepartment) };
        },
        update: async (id: string, input: DepartmentFormInput): Promise<ApiResponse<Department>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/departments/${id}`, {
                body: toBackendDepartmentPayload(input),
                method: 'PUT',
            });

            return { ...response, data: normalizeDepartment(response.data) };
        },
    },
    designations: {
        create: async (input: DesignationFormInput): Promise<ApiResponse<Designation>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/hr/designations', {
                body: toBackendDesignationPayload(input),
                method: 'POST',
            });

            return { ...response, data: normalizeDesignation(response.data) };
        },
        get: async (id: string): Promise<ApiResponse<Designation>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/designations/${id}`);

            return { ...response, data: normalizeDesignation(response.data) };
        },
        list: async (query: MasterListQuery & { departmentId?: string } = {}): Promise<ApiCollectionResponse<Designation>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/designations', {
                query: {
                    department_id: query.departmentId,
                    is_active: query.isActive,
                    page: query.page,
                    per_page: query.perPage ?? 100,
                    search: query.search,
                },
            });

            return { ...response, data: response.data.map(normalizeDesignation) };
        },
        update: async (id: string, input: DesignationFormInput): Promise<ApiResponse<Designation>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/designations/${id}`, {
                body: toBackendDesignationPayload(input),
                method: 'PUT',
            });

            return { ...response, data: normalizeDesignation(response.data) };
        },
    },
    documents: {
        list: async (): Promise<ApiCollectionResponse<EmployeeDocument>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/employee-documents');

            return { ...response, data: response.data.map(normalizeDocument) };
        },
    },
    employees: {
        active: async (): Promise<ApiCollectionResponse<Employee>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/employees/active');

            return { ...response, data: response.data.map(normalizeEmployee) };
        },
        byDepartment: async (departmentId: string): Promise<ApiCollectionResponse<Employee>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/hr/employees/by-department/${departmentId}`);

            return { ...response, data: response.data.map(normalizeEmployee) };
        },
        byDesignation: async (designationId: string): Promise<ApiCollectionResponse<Employee>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/hr/employees/by-designation/${designationId}`);

            return { ...response, data: response.data.map(normalizeEmployee) };
        },
        changeStatus: async (id: string, employmentStatus: EmployeeStatus, reason?: string): Promise<ApiResponse<Employee>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${id}/status`, {
                body: { employment_status: employmentStatus, reason: reason || null },
                method: 'PATCH',
            });

            return { ...response, data: normalizeEmployee(response.data) };
        },
        create: async (input: EmployeeFormInput): Promise<ApiResponse<Employee>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/hr/employees', {
                body: toBackendEmployeePayload(input),
                method: 'POST',
            });

            return { ...response, data: normalizeEmployee(response.data) };
        },
        get: async (id: string): Promise<ApiResponse<Employee>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${id}`);

            return { ...response, data: normalizeEmployee(response.data) };
        },
        list: async (query: EmployeeListQuery = {}): Promise<ApiCollectionResponse<Employee>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/employees', {
                query: {
                    department_id: query.departmentId,
                    designation_id: query.designationId,
                    employment_status: query.status,
                    page: query.page,
                    per_page: query.perPage ?? 50,
                    search: query.search,
                },
            });

            return { ...response, data: response.data.map(normalizeEmployee) };
        },
        lookup: async (query = ''): Promise<ApiCollectionResponse<Employee>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/employees/lookup', { query: { q: query } });

            return { ...response, data: response.data.map(normalizeEmployee) };
        },
        update: async (id: string, input: EmployeeFormInput): Promise<ApiResponse<Employee>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${id}`, {
                body: toBackendEmployeePayload(input),
                method: 'PUT',
            });

            return { ...response, data: normalizeEmployee(response.data) };
        },
        validateForAssignmentContext: (id: string, context: string) =>
            httpClient<ApiResponse<unknown>>(`/api/hr/employees/${id}/validate/assignment-context/${context}`),
    },
    employmentDetails: {
        get: async (employeeId: string): Promise<ApiResponse<EmploymentDetails>> => {
            const response = await httpClient<ApiResponse<BackendRecord | null>>(`/api/hr/employees/${employeeId}/employment-details`);

            return { ...response, data: normalizeEmploymentDetails(asRecord(response.data)) };
        },
        update: async (employeeId: string, input: EmploymentDetailsFormInput): Promise<ApiResponse<EmploymentDetails>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employees/${employeeId}/employment-details`, {
                body: toBackendEmploymentDetailsPayload(input),
                method: 'PUT',
            });

            return { ...response, data: normalizeEmploymentDetails(response.data) };
        },
    },
    employmentTypes: {
        create: async (input: EmploymentTypeFormInput): Promise<ApiResponse<EmploymentType>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/hr/employment-types', {
                body: toBackendEmploymentTypePayload(input),
                method: 'POST',
            });

            return { ...response, data: normalizeEmploymentType(response.data) };
        },
        list: async (query: MasterListQuery = {}): Promise<ApiCollectionResponse<EmploymentType>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/employment-types', {
                query: {
                    is_active: query.isActive,
                    page: query.page,
                    per_page: query.perPage ?? 100,
                    search: query.search,
                },
            });

            return { ...response, data: response.data.map(normalizeEmploymentType) };
        },
        update: async (id: string, input: EmploymentTypeFormInput): Promise<ApiResponse<EmploymentType>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/hr/employment-types/${id}`, {
                body: toBackendEmploymentTypePayload(input),
                method: 'PUT',
            });

            return { ...response, data: normalizeEmploymentType(response.data) };
        },
    },
    leave: {
        list: async (): Promise<ApiCollectionResponse<EmployeeLeaveRecord>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/leave-applications');

            return {
                ...response,
                data: response.data.map((raw) => ({
                    backendBalance: asString(raw.backend_balance, 'Backend calculated'),
                    employee: asString(raw.employee_name ?? raw.employee_id),
                    id: asString(raw.id),
                    leaveType: asString(raw.leave_type_name ?? raw.leave_type_id),
                    status: asString(raw.status),
                    window: [asString(raw.start_date), asString(raw.end_date)].filter(Boolean).join(' to '),
                })),
            };
        },
    },
    salaryProfiles: {
        list: async (): Promise<ApiCollectionResponse<EmployeeSalaryProfile>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/hr/employee-salary-assignments');

            return { ...response, data: response.data.map(normalizeSalaryProfile) };
        },
    },
    userAccess: {
        create: (employeeId: string, input: EmployeeUserAccessCreateInput) =>
            httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/user-accesses`, {
                body: {
                    access_role: input.accessRole || null,
                    invited: input.invited ?? true,
                    is_primary: input.isPrimary ?? false,
                    user: {
                        email: input.email,
                        name: input.name || null,
                    },
                },
                method: 'POST',
            }),
        deactivate: (employeeId: string, accessId: string, reason?: string) =>
            httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/user-accesses/${accessId}/deactivate`, {
                body: { reason: reason || null },
                method: 'PATCH',
            }),
        linkExisting: (employeeId: string, input: EmployeeUserAccessLinkInput) =>
            httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/user-accesses/link-existing`, {
                body: {
                    access_role: input.accessRole || null,
                    invited: input.invited ?? false,
                    is_primary: input.isPrimary ?? false,
                    user_id: Number(input.userId),
                },
                method: 'POST',
            }),
        list: async (employeeId: string): Promise<ApiCollectionResponse<EmployeeUserAccess>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/hr/employees/${employeeId}/user-accesses`);

            return { ...response, data: response.data.map(normalizeUserAccess) };
        },
        unlink: (employeeId: string, accessId: string) =>
            httpClient<ApiResponse<unknown>>(`/api/hr/employees/${employeeId}/user-accesses/${accessId}`, { method: 'DELETE' }),
    },
};

export const listEmployees = hrApi.employees.list;
export const createEmployee = hrApi.employees.create;
