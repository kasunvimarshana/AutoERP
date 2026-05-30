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
    name: string;
    phone?: string;
    relationship?: string;
    status: string;
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
};

export type EmploymentDetails = {
    department: string;
    designation: string;
    employmentStatus: EmployeeStatus;
    employmentType: string;
    joiningDate: string;
    leavingDate?: string;
    manager: string;
    probationEndDate?: string;
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
    designation: string;
    displayName: string;
    documents: EmployeeDocument[];
    email?: string;
    employmentDetails: EmploymentDetails;
    employmentType: string;
    firstName: string;
    fullName: string;
    id: string;
    isActive: boolean;
    joiningDate: string;
    lastName?: string;
    mobile?: string;
    phone?: string;
    reportingManager?: string;
    salaryProfile?: EmployeeSalaryProfile;
    status: EmployeeStatus;
    userAccess: EmployeeUserAccess[];
};

export type Department = {
    code: string;
    employeeCount: string;
    id: string;
    isActive: boolean;
    manager: string;
    name: string;
    parentDepartment?: string;
    status: string;
    updatedAt: string;
};

export type Designation = {
    code: string;
    department: string;
    employeeCount: string;
    id: string;
    isActive: boolean;
    name: string;
    status: string;
    updatedAt: string;
};

export type EmploymentType = {
    code: string;
    description: string;
    id: string;
    isActive: boolean;
    name: string;
    status: string;
};

export type HrDashboardMetric = {
    label: string;
    tone: string;
    value: string;
};
