export type EmployeeStatus =
    | 'draft'
    | 'active'
    | 'inactive'
    | 'on_leave'
    | 'suspended'
    | 'terminated'
    | 'resigned'
    | 'archived';

export type EmployeeContact = {
    contactType: string;
    email?: string;
    id: string;
    isEmergency: boolean;
    isPrimary: boolean;
    mobile?: string;
    name: string;
    phone?: string;
    relationship?: string;
    status: string;
};

export type EmployeeContactFormInput = {
    contactType?: string;
    email?: string;
    isEmergency?: boolean;
    isPrimary?: boolean;
    mobile?: string;
    name: string;
    phone?: string;
    relationship?: string;
};

export type EmployeeAddress = {
    addressLine1: string;
    addressLine2?: string;
    addressType: string;
    city: string;
    countryName: string;
    id: string;
    isActive: boolean;
    isPrimary: boolean;
    postalCode?: string;
    stateProvince?: string;
};

export type EmployeeAddressFormInput = {
    addressLine1: string;
    addressLine2?: string;
    addressType?: string;
    city: string;
    countryName?: string;
    isPrimary?: boolean;
    postalCode?: string;
    stateProvince?: string;
};

export type EmploymentDetails = {
    department: string;
    departmentId?: string;
    designation: string;
    designationId?: string;
    employmentStatus: EmployeeStatus;
    employmentType: string;
    employmentTypeId?: string;
    joiningDate: string;
    leavingDate?: string;
    manager: string;
    probationEndDate?: string;
    reportingManagerId?: string;
    workLocation: string;
};

export type EmployeeUserAccess = {
    accessRole: string;
    accessStatus: string;
    activatedAt?: string;
    id: string;
    invitedAt?: string;
    isPrimary: boolean;
    userEmail: string;
    userName: string;
};

export type EmployeeDocument = {
    documentNumber: string;
    documentType: string;
    expiryDate?: string;
    id: string;
    status: string;
};

export type EmployeeAttendanceRecord = {
    attendanceDate: string;
    backendTotal: string;
    employee: string;
    id: string;
    status: string;
};

export type EmployeeLeaveRecord = {
    backendBalance: string;
    employee: string;
    id: string;
    leaveType: string;
    status: string;
    window: string;
};

export type EmployeeSalaryProfile = {
    backendPayablePreview: string;
    effectiveFrom: string;
    employee: string;
    id: string;
    paymentMethod: string;
    salaryType: string;
    status: string;
};

export type EmployeeAuditEntry = {
    actor: string;
    id: string;
    note: string;
    timestamp: string;
    type: string;
};

export type Employee = {
    addresses: EmployeeAddress[];
    audit: EmployeeAuditEntry[];
    code: string;
    contacts: EmployeeContact[];
    department: string;
    departmentId?: string;
    designation: string;
    designationId?: string;
    displayName: string;
    documents: EmployeeDocument[];
    email?: string;
    employmentDetails: EmploymentDetails;
    employmentType: string;
    employmentTypeId?: string;
    firstName: string;
    fullName: string;
    id: string;
    isActive: boolean;
    joiningDate: string;
    lastName?: string;
    mobile?: string;
    notes?: string;
    phone?: string;
    reportingManager?: string;
    reportingManagerId?: string;
    salaryProfile?: EmployeeSalaryProfile;
    status: EmployeeStatus;
    userAccess: EmployeeUserAccess[];
};

export type EmployeeFormInput = {
    code: string;
    departmentId?: string;
    designationId?: string;
    displayName?: string;
    email?: string;
    employmentTypeId?: string;
    firstName: string;
    joiningDate?: string;
    lastName?: string;
    leavingDate?: string;
    mobile?: string;
    notes?: string;
    phone?: string;
    primaryAddress?: EmployeeAddressFormInput;
    primaryContact?: EmployeeContactFormInput;
    reportingManagerId?: string;
    status: EmployeeStatus;
};

export type EmploymentDetailsFormInput = {
    departmentId?: string;
    designationId?: string;
    employmentStatus?: EmployeeStatus;
    employmentTypeId?: string;
    joiningDate?: string;
    leavingDate?: string;
    probationEndDate?: string;
    reportingManagerId?: string;
};

export type EmployeeUserAccessCreateInput = {
    accessRole?: string;
    email: string;
    invited?: boolean;
    isPrimary?: boolean;
    name?: string;
};

export type EmployeeUserAccessLinkInput = {
    accessRole?: string;
    invited?: boolean;
    isPrimary?: boolean;
    userId: string;
};

export type Department = {
    code: string;
    description?: string;
    employeeCount: string;
    id: string;
    isActive: boolean;
    manager: string;
    managerEmployeeId?: string;
    name: string;
    parentDepartment?: string;
    parentId?: string;
    status: string;
    updatedAt: string;
};

export type DepartmentFormInput = {
    code: string;
    description?: string;
    isActive: boolean;
    managerEmployeeId?: string;
    name: string;
    parentId?: string;
};

export type Designation = {
    code: string;
    department: string;
    departmentId?: string;
    description?: string;
    employeeCount: string;
    id: string;
    isActive: boolean;
    name: string;
    status: string;
    updatedAt: string;
};

export type DesignationFormInput = {
    code: string;
    departmentId?: string;
    description?: string;
    isActive: boolean;
    name: string;
};

export type EmploymentType = {
    code: string;
    description: string;
    id: string;
    isActive: boolean;
    name: string;
    status: string;
};

export type EmploymentTypeFormInput = {
    code: string;
    description?: string;
    isActive: boolean;
    name: string;
};

export type HrDashboardMetric = {
    label: string;
    tone: string;
    value: string;
};
