import type {
    Department,
    Designation,
    Employee,
    EmployeeAttendanceRecord,
    EmployeeLeaveRecord,
    EmployeeSalaryProfile,
    EmploymentType,
    HrDashboardMetric,
} from '../types/hr.types';

const audit = [
    { actor: 'Kasun Perera', id: 'audit-001', note: 'Employee profile created without user account.', timestamp: '2026-05-28 09:00', type: 'created' },
    { actor: 'HR Manager', id: 'audit-002', note: 'Employment details updated by backend workflow.', timestamp: '2026-05-29 11:20', type: 'employment' },
];

export const departments: Department[] = [
    { code: 'HR', employeeCount: 'Backend count', id: 'dept-001', isActive: true, manager: 'Kasun Perera', name: 'Human Resources', status: 'active', updatedAt: '2026-05-29' },
    { code: 'WORKSHOP', employeeCount: 'Backend count', id: 'dept-002', isActive: true, manager: 'Nimal Fernando', name: 'Workshop Operations', status: 'active', updatedAt: '2026-05-29' },
    { code: 'RENTAL', employeeCount: 'Backend count', id: 'dept-003', isActive: true, manager: 'Maya Silva', name: 'Rental Operations', status: 'active', updatedAt: '2026-05-28' },
];

export const designations: Designation[] = [
    { code: 'TECH', department: 'Workshop Operations', employeeCount: 'Backend count', id: 'des-001', isActive: true, name: 'Technician', status: 'active', updatedAt: '2026-05-29' },
    { code: 'DRIVER', department: 'Rental Operations', employeeCount: 'Backend count', id: 'des-002', isActive: true, name: 'Driver', status: 'active', updatedAt: '2026-05-29' },
    { code: 'SUP', department: 'Workshop Operations', employeeCount: 'Backend count', id: 'des-003', isActive: true, name: 'Supervisor', status: 'active', updatedAt: '2026-05-28' },
];

export const employmentTypes: EmploymentType[] = [
    { code: 'FULL_TIME', description: 'Permanent full-time employee', id: 'etype-001', isActive: true, name: 'Full Time', status: 'active' },
    { code: 'CONTRACT', description: 'Fixed-term contract employee', id: 'etype-002', isActive: true, name: 'Contract', status: 'active' },
    { code: 'CASUAL', description: 'Casual or daily employee', id: 'etype-003', isActive: true, name: 'Casual', status: 'active' },
];

export const salaryProfiles: EmployeeSalaryProfile[] = [
    { backendPayablePreview: 'Backend payroll preview', effectiveFrom: '2026-01-01', employee: 'Kasun Perera', id: 'sal-001', paymentMethod: 'Bank transfer', salaryType: 'Monthly', status: 'active' },
    { backendPayablePreview: 'Backend payroll preview', effectiveFrom: '2026-02-01', employee: 'Nimal Fernando', id: 'sal-002', paymentMethod: 'Bank transfer', salaryType: 'Monthly', status: 'active' },
];

export const employees: Employee[] = [
    {
        addresses: [
            { addressLine1: '12 Lake Road', addressType: 'current', city: 'Colombo', countryName: 'Sri Lanka', id: 'addr-001', isActive: true, isPrimary: true, postalCode: '00300' },
        ],
        audit,
        code: 'EMP-001',
        contacts: [
            { contactType: 'mobile', email: 'kasun@example.test', id: 'cont-001', isEmergency: false, isPrimary: true, name: 'Kasun Perera', phone: '+94 77 100 2000', status: 'active' },
            { contactType: 'emergency', id: 'cont-002', isEmergency: true, isPrimary: false, name: 'Anoma Perera', phone: '+94 77 200 3000', relationship: 'Spouse', status: 'active' },
        ],
        department: 'Human Resources',
        designation: 'Supervisor',
        displayName: 'Kasun Perera',
        documents: [{ documentNumber: 'HR-DOC-001', documentType: 'National ID', id: 'doc-001', status: 'verified' }],
        email: 'kasun@example.test',
        employmentDetails: { department: 'Human Resources', designation: 'Supervisor', employmentStatus: 'active', employmentType: 'Full Time', joiningDate: '2024-01-15', manager: 'Board', workLocation: 'Head Office' },
        employmentType: 'Full Time',
        firstName: 'Kasun',
        fullName: 'Kasun Perera',
        id: 'emp-001',
        isActive: true,
        joiningDate: '2024-01-15',
        lastName: 'Perera',
        mobile: '+94 77 100 2000',
        reportingManager: 'Board',
        salaryProfile: salaryProfiles[0],
        status: 'active',
        userAccess: [{ accessRole: 'hr_admin', accessStatus: 'active', activatedAt: '2024-01-16', id: 'ua-001', isPrimary: true, userEmail: 'kasun@example.test', userName: 'kasun' }],
    },
    {
        addresses: [{ addressLine1: '41 Workshop Lane', addressType: 'current', city: 'Kandy', countryName: 'Sri Lanka', id: 'addr-002', isActive: true, isPrimary: true }],
        audit,
        code: 'EMP-002',
        contacts: [{ contactType: 'mobile', id: 'cont-003', isEmergency: false, isPrimary: true, name: 'Nimal Fernando', phone: '+94 77 300 4000', status: 'active' }],
        department: 'Workshop Operations',
        designation: 'Technician',
        displayName: 'Nimal Fernando',
        documents: [{ documentNumber: 'TECH-CERT-104', documentType: 'Skill certificate', id: 'doc-002', status: 'active' }],
        employmentDetails: { department: 'Workshop Operations', designation: 'Technician', employmentStatus: 'active', employmentType: 'Full Time', joiningDate: '2024-03-10', manager: 'Kasun Perera', workLocation: 'Main Workshop' },
        employmentType: 'Full Time',
        firstName: 'Nimal',
        fullName: 'Nimal Fernando',
        id: 'emp-002',
        isActive: true,
        joiningDate: '2024-03-10',
        lastName: 'Fernando',
        mobile: '+94 77 300 4000',
        reportingManager: 'Kasun Perera',
        salaryProfile: salaryProfiles[1],
        status: 'active',
        userAccess: [],
    },
    {
        addresses: [],
        audit,
        code: 'EMP-003',
        contacts: [{ contactType: 'mobile', id: 'cont-004', isEmergency: false, isPrimary: true, name: 'Maya Silva', phone: '+94 77 500 6000', status: 'active' }],
        department: 'Rental Operations',
        designation: 'Driver',
        displayName: 'Maya Silva',
        documents: [{ documentNumber: 'DL-B45022', documentType: 'Driving license', expiryDate: '2028-12-31', id: 'doc-003', status: 'valid' }],
        employmentDetails: { department: 'Rental Operations', designation: 'Driver', employmentStatus: 'active', employmentType: 'Contract', joiningDate: '2025-02-01', manager: 'Kasun Perera', workLocation: 'Rental Yard' },
        employmentType: 'Contract',
        firstName: 'Maya',
        fullName: 'Maya Silva',
        id: 'emp-003',
        isActive: true,
        joiningDate: '2025-02-01',
        lastName: 'Silva',
        mobile: '+94 77 500 6000',
        reportingManager: 'Kasun Perera',
        status: 'active',
        userAccess: [{ accessRole: 'driver_portal', accessStatus: 'invited', id: 'ua-002', invitedAt: '2026-05-20', isPrimary: true, userEmail: 'maya@example.test', userName: 'maya.silva' }],
    },
    {
        addresses: [],
        audit,
        code: 'EMP-004',
        contacts: [],
        department: 'Workshop Operations',
        designation: 'Technician',
        displayName: 'Ruwan Jayasekara',
        documents: [],
        employmentDetails: { department: 'Workshop Operations', designation: 'Technician', employmentStatus: 'suspended', employmentType: 'Full Time', joiningDate: '2023-07-01', manager: 'Nimal Fernando', workLocation: 'Main Workshop' },
        employmentType: 'Full Time',
        firstName: 'Ruwan',
        fullName: 'Ruwan Jayasekara',
        id: 'emp-004',
        isActive: false,
        joiningDate: '2023-07-01',
        lastName: 'Jayasekara',
        reportingManager: 'Nimal Fernando',
        status: 'suspended',
        userAccess: [],
    },
    {
        addresses: [],
        audit: [
            ...audit,
            { actor: 'HR Manager', id: 'audit-003', note: 'Employee terminated; profile retained for historical references.', timestamp: '2026-05-29 15:10', type: 'status' },
        ],
        code: 'EMP-005',
        contacts: [{ contactType: 'mobile', id: 'cont-005', isEmergency: false, isPrimary: true, name: 'Tharindu Wijesinghe', phone: '+94 77 700 8000', status: 'inactive' }],
        department: 'Rental Operations',
        designation: 'Driver',
        displayName: 'Tharindu Wijesinghe',
        documents: [],
        employmentDetails: { department: 'Rental Operations', designation: 'Driver', employmentStatus: 'terminated', employmentType: 'Contract', joiningDate: '2023-05-10', leavingDate: '2026-05-12', manager: 'Maya Silva', workLocation: 'Rental Yard' },
        employmentType: 'Contract',
        firstName: 'Tharindu',
        fullName: 'Tharindu Wijesinghe',
        id: 'emp-005',
        isActive: false,
        joiningDate: '2023-05-10',
        lastName: 'Wijesinghe',
        reportingManager: 'Maya Silva',
        status: 'terminated',
        userAccess: [],
    },
];

export const attendanceRecords: EmployeeAttendanceRecord[] = [
    { attendanceDate: '2026-05-29', backendTotal: 'Backend attendance total', employee: 'Nimal Fernando', id: 'att-001', status: 'present' },
    { attendanceDate: '2026-05-29', backendTotal: 'Backend attendance total', employee: 'Maya Silva', id: 'att-002', status: 'on duty' },
];

export const leaveRecords: EmployeeLeaveRecord[] = [
    { backendBalance: 'Backend leave balance', employee: 'Kasun Perera', id: 'leave-001', leaveType: 'Annual', status: 'approved', window: '2026-06-03 to 2026-06-04' },
    { backendBalance: 'Backend leave balance', employee: 'Ruwan Jayasekara', id: 'leave-002', leaveType: 'No pay', status: 'pending', window: '2026-06-10' },
];

export const hrDashboardMetrics: HrDashboardMetric[] = [
    { label: 'Active employees', tone: 'success', value: 'Backend count' },
    { label: 'Departments', tone: 'info', value: 'Backend count' },
    { label: 'Designations', tone: 'info', value: 'Backend count' },
    { label: 'Employees on leave', tone: 'warning', value: 'Backend count' },
    { label: 'Without user access', tone: 'default', value: 'Backend count' },
    { label: 'Suspended', tone: 'danger', value: 'Backend count' },
];

export function getEmployeeById(id: string) {
    return employees.find((employee) => employee.id === id || employee.code === id) ?? employees[0];
}

export function getDepartmentById(id: string) {
    return departments.find((department) => department.id === id || department.code === id) ?? departments[0];
}

export function getDesignationById(id: string) {
    return designations.find((designation) => designation.id === id || designation.code === id) ?? designations[0];
}
