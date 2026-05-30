import { useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { departments, designations, employmentTypes } from '../mock/hrMock';
import type {
    Department,
    Designation,
    Employee,
    EmployeeAddress,
    EmployeeAttendanceRecord,
    EmployeeAuditEntry,
    EmployeeContact,
    EmployeeDocument,
    EmployeeLeaveRecord,
    EmployeeSalaryProfile,
    EmploymentType,
    HrDashboardMetric,
} from '../types/hr.types';

function Label({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}</div>;
}

const departmentOptions = departments.map((department) => ({ label: department.name, value: department.id }));
const designationOptions = designations.map((designation) => ({ label: designation.name, value: designation.id }));
const employmentTypeOptions = employmentTypes.map((type) => ({ label: type.name, value: type.id }));
const employeeStatusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'On leave', value: 'on_leave' },
    { label: 'Suspended', value: 'suspended' },
    { label: 'Terminated', value: 'terminated' },
    { label: 'Resigned', value: 'resigned' },
    { label: 'Archived', value: 'archived' },
];

export function HrPageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: string; title: string }) {
    return <PageHeader actions={actions} eyebrow="HR" subtitle={subtitle} title={title} />;
}

export function HrDashboardCards({ metrics }: { metrics: HrDashboardMetric[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            {metrics.map((metric) => (
                <Card className="p-5" key={metric.label}>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{metric.label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{metric.value}</p>
                    <p className="mt-2 text-xs font-semibold text-slate-500">{metric.tone} from backend/mock</p>
                </Card>
            ))}
        </div>
    );
}

export function EmployeeTable({ rows }: { rows: Employee[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Employee', key: 'code', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/hr/employees/${row.id}`}>{row.code}</Link> },
                { header: 'Name', key: 'displayName' },
                { header: 'Department', key: 'department' },
                { header: 'Designation', key: 'designation' },
                { header: 'Employment type', key: 'employmentType' },
                { header: 'User access', key: 'userAccess', render: (row) => (row.userAccess.length ? 'Linked / invited' : 'No user access') },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/hr/employees/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function EmployeeSummaryCard({ employee }: { employee: Employee }) {
    return (
        <Card className="p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{employee.code}</p>
                    <h2 className="mt-1 text-xl font-bold text-slate-950">{employee.displayName}</h2>
                    <p className="mt-2 text-sm text-slate-500">{employee.department} / {employee.designation}</p>
                </div>
                <StatusBadge status={employee.status} />
            </div>
            <div className="mt-5 grid gap-3 text-sm md:grid-cols-2">
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3"><p className="text-slate-500">Employment type</p><p className="font-semibold text-slate-900">{employee.employmentType}</p></div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3"><p className="text-slate-500">Joining date</p><p className="font-semibold text-slate-900">{employee.joiningDate}</p></div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3"><p className="text-slate-500">Reporting manager</p><p className="font-semibold text-slate-900">{employee.reportingManager ?? employee.employmentDetails.manager}</p></div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3"><p className="text-slate-500">Mobile</p><p className="font-semibold text-slate-900">{employee.mobile ?? 'Not set'}</p></div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3"><p className="text-slate-500">User access</p><p className="font-semibold text-slate-900">{employee.userAccess.length ? 'Optional link exists' : 'Employee only'}</p></div>
            </div>
        </Card>
    );
}

export function EmployeeForm({ employee }: { employee?: Employee }) {
    const [activeTab, setActiveTab] = useState('profile');
    const tabs = [
        { label: 'Profile', value: 'profile' },
        { label: 'Contacts & Addresses', value: 'contact' },
        { label: 'Employment Details', value: 'employment' },
        { label: 'User Access', value: 'user' },
        { label: 'Review', value: 'review' },
    ];

    return (
        <div className="space-y-5">
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} trailing={<StatusBadge status="User optional" />} /></Card>
            {activeTab === 'profile' ? (
                <FormSection description="Employee is an HR profile. Creating an employee does not create a login user." title="Employee Profile">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Employee code"><Input defaultValue={employee?.code} placeholder="Backend can validate/sequence" /></Field>
                        <Field label="First name"><Input defaultValue={employee?.firstName} /></Field>
                        <Field label="Last name"><Input defaultValue={employee?.lastName} /></Field>
                        <Field label="Display name"><Input defaultValue={employee?.displayName} /></Field>
                        <Field label="Email"><Input defaultValue={employee?.email} type="email" /></Field>
                        <Field label="Mobile"><Input defaultValue={employee?.mobile} /></Field>
                        <Field label="Date of birth"><Input type="date" /></Field>
                        <Field label="National ID / passport"><Input placeholder="Optional identity reference" /></Field>
                        <Field label="Status"><Input readOnly value={employee?.status ?? 'draft'} /></Field>
                        <div className="xl:col-span-3"><Field label="Notes"><Textarea placeholder="HR notes" /></Field></div>
                    </div>
                </FormSection>
            ) : null}
            {activeTab === 'contact' ? (
                <div className="space-y-5">
                    <FormSection description="Contacts and addresses are HR-owned employee data." title="Contacts & Addresses">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <Field label="Primary phone"><Input defaultValue={employee?.phone} /></Field>
                            <Field label="Emergency contact"><Input placeholder="Name and relationship" /></Field>
                            <Field label="Address type"><Select options={[{ label: 'Current', value: 'current' }, { label: 'Permanent', value: 'permanent' }]} /></Field>
                            <Field label="Address line"><Input placeholder="Address line" /></Field>
                            <Field label="City"><Input placeholder="City" /></Field>
                            <Field label="Country"><Input placeholder="Country" /></Field>
                        </div>
                    </FormSection>
                    {employee ? <><EmployeeContactsTable rows={employee.contacts} /><EmployeeAddressesTable rows={employee.addresses} /></> : null}
                </div>
            ) : null}
            {activeTab === 'employment' ? (
                <FormSection description="Department, designation, manager and employment status are validated by backend." title="Employment Details">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Department"><Select defaultValue={employee?.department} options={departmentOptions} /></Field>
                        <Field label="Designation"><Select defaultValue={employee?.designation} options={designationOptions} /></Field>
                        <Field label="Employment type"><Select defaultValue={employee?.employmentType} options={employmentTypeOptions} /></Field>
                        <Field label="Joining date"><Input defaultValue={employee?.joiningDate} type="date" /></Field>
                        <Field label="Leaving date"><Input type="date" /></Field>
                        <Field label="Reporting manager"><Input defaultValue={employee?.reportingManager ?? employee?.employmentDetails.manager} placeholder="Manager employee selector" /></Field>
                        <Field label="Work location"><Input defaultValue={employee?.employmentDetails.workLocation} /></Field>
                        <Field label="Employment status"><Select defaultValue={employee?.status} options={employeeStatusOptions} /></Field>
                    </div>
                </FormSection>
            ) : null}
            {activeTab === 'user' ? <EmployeeUserAccessPanel employee={employee} /> : null}
            {activeTab === 'review' ? (
                <div className="grid gap-5 xl:grid-cols-3">
                    <PreviewPanel rows={[
                        { label: 'Employee profile', value: employee?.displayName ?? 'New employee' },
                        { label: 'User access', value: 'Optional; no automatic user creation' },
                        { label: 'VehicleService lookup', value: 'Backend validates active employee' },
                        { label: 'VehicleRental lookup', value: 'Backend validates active employee' },
                    ]} status="Review" title="Employee Review" />
                    {employee ? <EmploymentDetailsPanel employee={employee} /> : null}
                    {employee ? <SalaryProfilePanel profile={employee.salaryProfile} /> : null}
                </div>
            ) : null}
        </div>
    );
}

export function EmployeeContactsTable({ rows }: { rows: EmployeeContact[] }) {
    return <DataTable columns={[{ header: 'Name', key: 'name' }, { header: 'Type', key: 'contactType' }, { header: 'Phone', key: 'phone' }, { header: 'Email', key: 'email' }, { header: 'Primary', key: 'isPrimary', render: (row) => (row.isPrimary ? 'Primary' : '') }, { header: 'Emergency', key: 'isEmergency', render: (row) => (row.isEmergency ? 'Emergency' : '') }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function EmployeeAddressesTable({ rows }: { rows: EmployeeAddress[] }) {
    return <DataTable columns={[{ header: 'Type', key: 'addressType' }, { header: 'Address', key: 'addressLine1' }, { header: 'City', key: 'city' }, { header: 'Country', key: 'countryName' }, { header: 'Primary', key: 'isPrimary', render: (row) => (row.isPrimary ? 'Primary' : '') }, { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'active' : 'inactive'} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function EmploymentDetailsPanel({ employee }: { employee: Employee }) {
    return <PreviewPanel rows={[{ label: 'Department', value: employee.employmentDetails.department }, { label: 'Designation', value: employee.employmentDetails.designation }, { label: 'Employment type', value: employee.employmentDetails.employmentType }, { label: 'Reporting manager', value: employee.reportingManager ?? employee.employmentDetails.manager }, { label: 'Work location', value: employee.employmentDetails.workLocation }, { label: 'Status', value: employee.employmentDetails.employmentStatus }]} status="Employment" title="Employment Details" />;
}

export function EmployeeUserAccessPanel({ employee }: { employee?: Employee }) {
    const rows = employee?.userAccess ?? [];
    return (
        <div className="space-y-5">
            <PreviewPanel rows={[
                { label: 'Employee identity', value: 'Stored in HR employee tables' },
                { label: 'Login identity', value: 'Linked separately only when requested' },
                { label: 'Auto-create user', value: 'No' },
            ]} status="Optional" title="Employee User Access" />
            <FormSection description="Link an existing user or invite access separately. Employee creation remains independent." title="Link User Access">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field label="Existing user"><Input placeholder="User selector" /></Field>
                    <Field label="Access role"><Input placeholder="employee_portal, driver_portal..." /></Field>
                    <Field label="Primary access"><Select options={[{ label: 'Primary', value: 'true' }, { label: 'Secondary', value: 'false' }]} /></Field>
                    <Field label="Action"><Button variant="secondary">Link / Invite</Button></Field>
                </div>
            </FormSection>
            <DataTable columns={[{ header: 'User', key: 'userName' }, { header: 'Email', key: 'userEmail' }, { header: 'Role', key: 'accessRole' }, { header: 'Primary', key: 'isPrimary', render: (row) => (row.isPrimary ? 'Primary' : '') }, { header: 'Status', key: 'accessStatus', render: (row) => <StatusBadge status={row.accessStatus} /> }]} getRowKey={(row) => row.id} rows={rows} />
        </div>
    );
}

export function EmployeeDocumentsPanel({ rows }: { rows: EmployeeDocument[] }) {
    return <DataTable columns={[{ header: 'Document', key: 'documentNumber' }, { header: 'Type', key: 'documentType' }, { header: 'Expiry', key: 'expiryDate' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function EmployeeStatusActionPanel({ employee }: { employee: Employee }) {
    return (
        <Card className="p-5">
            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">Status Actions</h3>
            <p className="mt-1 text-sm text-slate-500">Status transitions are backend-owned and recorded in employee status history.</p>
            <div className="mt-4 flex flex-wrap gap-2">
                <Button variant="secondary">Activate</Button>
                <Button variant="secondary">Suspend</Button>
                <Button variant="danger">Terminate</Button>
                <Button variant="secondary">Archive</Button>
            </div>
            <p className="mt-4 text-sm font-semibold text-slate-600">Current backend/mock status: {employee.status}</p>
        </Card>
    );
}

export function DepartmentTable({ rows }: { rows: Department[] }) {
    return <DataTable columns={[{ header: 'Code', key: 'code' }, { header: 'Name', key: 'name' }, { header: 'Manager', key: 'manager' }, { header: 'Employees', key: 'employeeCount' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }, { header: 'Actions', key: 'actions', render: (row) => <Link to={`/hr/departments/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function DepartmentForm({ department }: { department?: Department }) {
    return (
        <FormSection description="Departments are HR master data and can be used by employee lookup and assignment validation." title="Department">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Department code"><Input defaultValue={department?.code} /></Field>
                <Field label="Department name"><Input defaultValue={department?.name} /></Field>
                <Field label="Parent department"><Select defaultValue={department?.parentDepartment} options={departmentOptions} placeholder="None" /></Field>
                <Field label="Manager employee"><Input defaultValue={department?.manager} placeholder="Employee selector" /></Field>
                <Field label="Active"><Select defaultValue={String(department?.isActive ?? true)} options={[{ label: 'Active', value: 'true' }, { label: 'Inactive', value: 'false' }]} /></Field>
            </div>
        </FormSection>
    );
}

export function DepartmentTreeView({ rows }: { rows: Department[] }) {
    return <PreviewPanel status="Hierarchy" title="Department Tree">{rows.map((row) => <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={row.id}>{row.name} / Manager: {row.manager}</div>)}</PreviewPanel>;
}

export function DesignationTable({ rows }: { rows: Designation[] }) {
    return <DataTable columns={[{ header: 'Code', key: 'code' }, { header: 'Name', key: 'name' }, { header: 'Department', key: 'department' }, { header: 'Employees', key: 'employeeCount' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }, { header: 'Actions', key: 'actions', render: (row) => <Link to={`/hr/designations/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function DesignationForm({ designation }: { designation?: Designation }) {
    return (
        <FormSection description="Designations classify employee roles such as technician, driver, supervisor, and future staff categories." title="Designation">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Designation code"><Input defaultValue={designation?.code} /></Field>
                <Field label="Designation name"><Input defaultValue={designation?.name} /></Field>
                <Field label="Department"><Select defaultValue={designation?.department} options={departmentOptions} /></Field>
                <Field label="Active"><Select defaultValue={String(designation?.isActive ?? true)} options={[{ label: 'Active', value: 'true' }, { label: 'Inactive', value: 'false' }]} /></Field>
            </div>
        </FormSection>
    );
}

export function EmploymentTypeForm({ type }: { type?: EmploymentType }) {
    return (
        <FormSection description="Employment types are reusable HR master data." title="Employment Type">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Code"><Input defaultValue={type?.code} /></Field>
                <Field label="Name"><Input defaultValue={type?.name} /></Field>
                <Field label="Active"><Select defaultValue={String(type?.isActive ?? true)} options={[{ label: 'Active', value: 'true' }, { label: 'Inactive', value: 'false' }]} /></Field>
                <div className="md:col-span-2 xl:col-span-3"><Field label="Description"><Textarea defaultValue={type?.description} /></Field></div>
            </div>
        </FormSection>
    );
}

export function EmploymentTypeTable({ rows }: { rows: EmploymentType[] }) {
    return <DataTable columns={[{ header: 'Code', key: 'code' }, { header: 'Name', key: 'name' }, { header: 'Description', key: 'description' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function AttendancePanel({ rows }: { rows: EmployeeAttendanceRecord[] }) {
    return <DataTable columns={[{ header: 'Date', key: 'attendanceDate' }, { header: 'Employee', key: 'employee' }, { header: 'Backend total', key: 'backendTotal' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function LeavePanel({ rows }: { rows: EmployeeLeaveRecord[] }) {
    return <DataTable columns={[{ header: 'Employee', key: 'employee' }, { header: 'Leave type', key: 'leaveType' }, { header: 'Window', key: 'window' }, { header: 'Backend balance', key: 'backendBalance' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function SalaryProfilePanel({ profile }: { profile?: EmployeeSalaryProfile }) {
    return <PreviewPanel rows={[{ label: 'Salary type', value: profile?.salaryType ?? 'Backend profile' }, { label: 'Payment method', value: profile?.paymentMethod ?? 'Backend payment method' }, { label: 'Effective from', value: profile?.effectiveFrom ?? 'Backend effective date' }, { label: 'Payable preview', value: profile?.backendPayablePreview ?? 'Backend calculated' }]} status="Payroll" title="Salary Profile" />;
}

export function SalaryProfileTable({ rows }: { rows: EmployeeSalaryProfile[] }) {
    return <DataTable columns={[{ header: 'Employee', key: 'employee' }, { header: 'Type', key: 'salaryType' }, { header: 'Effective from', key: 'effectiveFrom' }, { header: 'Backend payable', key: 'backendPayablePreview' }, { header: 'Payment method', key: 'paymentMethod' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function EmployeeActivityTimeline({ rows }: { rows: EmployeeAuditEntry[] }) {
    return (
        <div className="space-y-3">
            {rows.map((entry) => (
                <Card className="p-4" key={entry.id}>
                    <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div><p className="font-semibold text-slate-900">{entry.note}</p><p className="mt-1 text-sm text-slate-500">{entry.actor} / {entry.type}</p></div>
                        <div className="text-sm text-slate-500">{entry.timestamp}</div>
                    </div>
                </Card>
            ))}
        </div>
    );
}
