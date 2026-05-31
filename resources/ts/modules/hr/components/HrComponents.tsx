import { FormEvent, useState, type ReactNode } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { hrApi } from '../services/hrApi';
import type {
    Department,
    DepartmentFormInput,
    Designation,
    DesignationFormInput,
    EmployeeAttendanceRecord,
    EmployeeLeaveRecord,
    EmployeeSalaryProfile,
    EmploymentType,
    EmploymentTypeFormInput,
    HrDashboardMetric,
} from '../types/hr.types';

function Label({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}</div>;
}

function booleanFromForm(value: FormDataEntryValue | null, fallback = false): boolean {
    return value === null ? fallback : String(value) === 'true';
}

function fieldError(errors: Record<string, string[]>, key: string): string | undefined {
    return errors[key]?.[0];
}

function handleApiError(
    error: unknown,
    fallbackMessage: string,
    setErrors: (errors: Record<string, string[]>) => void,
    setFormError: (message: string) => void,
) {
    if (error instanceof ApiError) {
        setErrors(error.errors);
        setFormError(error.message);
        return;
    }

    setFormError(error instanceof Error ? error.message : fallbackMessage);
}

export function HrPageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: string; title: string }) {
    return <PageHeader actions={actions} eyebrow="HR" subtitle={subtitle} title={title} />;
}

export function HrDashboardCards({ metrics }: { metrics: HrDashboardMetric[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {metrics.map((metric) => (
                <Card className="p-5" key={metric.label}>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{metric.label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{metric.value}</p>
                    <p className="mt-2 text-xs font-semibold text-slate-500">{metric.tone} source</p>
                </Card>
            ))}
        </div>
    );
}

export function DepartmentTable({ rows }: { rows: Department[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Code', key: 'code' },
                { header: 'Name', key: 'name' },
                { header: 'Manager', key: 'manager' },
                { header: 'Employees', key: 'employeeCount' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/hr/departments/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function DepartmentForm({ department, departments = [], mode }: { department?: Department; departments?: Department[]; mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const departmentOptions = departments
        .filter((row) => row.id !== department?.id)
        .map((row) => ({ label: row.name, value: row.id }));

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const input: DepartmentFormInput = {
            code: String(formData.get('department_code') ?? ''),
            description: String(formData.get('description') ?? ''),
            isActive: booleanFromForm(formData.get('is_active'), true),
            managerEmployeeId: String(formData.get('manager_employee_id') ?? ''),
            name: String(formData.get('department_name') ?? ''),
            parentId: String(formData.get('parent_id') ?? ''),
        };

        try {
            const response = mode === 'edit' && department
                ? await hrApi.departments.update(department.id, input)
                : await hrApi.departments.create(input);

            navigate(mode === 'edit' ? `/hr/departments/${response.data.id}/edit` : '/hr/departments', { replace: true });
        } catch (error) {
            handleApiError(error, 'Unable to save department.', setErrors, setFormError);
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            <FormSection description="Departments are HR master data used by employee profiles and lookup filters." title="Department">
                {formError ? <div className="mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Field label="Department code"><Input defaultValue={department?.code} name="department_code" /><FieldError message={fieldError(errors, 'department_code')} /></Field>
                    <Field label="Department name"><Input defaultValue={department?.name} name="department_name" /><FieldError message={fieldError(errors, 'department_name')} /></Field>
                    <Field label="Parent department"><Select defaultValue={department?.parentId} name="parent_id" options={departmentOptions} placeholder="None" /><FieldError message={fieldError(errors, 'parent_id')} /></Field>
                    <Field label="Manager employee ID"><Input defaultValue={department?.managerEmployeeId} name="manager_employee_id" placeholder="Optional employee ID" /><FieldError message={fieldError(errors, 'manager_employee_id')} /></Field>
                    <Field label="Active"><Select defaultValue={String(department?.isActive ?? true)} name="is_active" options={[{ label: 'Active', value: 'true' }, { label: 'Inactive', value: 'false' }]} /></Field>
                    <div className="md:col-span-2 xl:col-span-3"><Field label="Description"><Textarea defaultValue={department?.description} name="description" /></Field></div>
                </div>
                <div className="mt-5 flex justify-end gap-3">
                    <Link to="/hr/departments"><Button variant="secondary">Cancel</Button></Link>
                    <Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Department' : 'Create Department'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function DepartmentTreeView({ rows }: { rows: Department[] }) {
    if (rows.length === 0) {
        return <PreviewPanel rows={[{ label: 'Departments', value: 'No department records yet' }]} status="Hierarchy" title="Department Tree" />;
    }

    return (
        <PreviewPanel status="Hierarchy" title="Department Tree">
            <div className="space-y-2">
                {rows.map((row) => (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={row.id}>
                        {row.name} / Manager: {row.manager || 'Not set'}
                    </div>
                ))}
            </div>
        </PreviewPanel>
    );
}

export function DesignationTable({ rows }: { rows: Designation[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Code', key: 'code' },
                { header: 'Name', key: 'name' },
                { header: 'Department', key: 'department' },
                { header: 'Employees', key: 'employeeCount' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/hr/designations/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function DesignationForm({ departments = [], designation, mode }: { departments?: Department[]; designation?: Designation; mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const departmentOptions = departments.map((department) => ({ label: department.name, value: department.id }));

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const input: DesignationFormInput = {
            code: String(formData.get('designation_code') ?? ''),
            departmentId: String(formData.get('department_id') ?? ''),
            description: String(formData.get('description') ?? ''),
            isActive: booleanFromForm(formData.get('is_active'), true),
            name: String(formData.get('designation_name') ?? ''),
        };

        try {
            const response = mode === 'edit' && designation
                ? await hrApi.designations.update(designation.id, input)
                : await hrApi.designations.create(input);

            navigate(mode === 'edit' ? `/hr/designations/${response.data.id}/edit` : '/hr/designations', { replace: true });
        } catch (error) {
            handleApiError(error, 'Unable to save designation.', setErrors, setFormError);
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            <FormSection description="Designations classify employee roles such as technician, driver, supervisor, and staff." title="Designation">
                {formError ? <div className="mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Field label="Designation code"><Input defaultValue={designation?.code} name="designation_code" /><FieldError message={fieldError(errors, 'designation_code')} /></Field>
                    <Field label="Designation name"><Input defaultValue={designation?.name} name="designation_name" /><FieldError message={fieldError(errors, 'designation_name')} /></Field>
                    <Field label="Department"><Select defaultValue={designation?.departmentId} name="department_id" options={departmentOptions} placeholder="No department" /><FieldError message={fieldError(errors, 'department_id')} /></Field>
                    <Field label="Active"><Select defaultValue={String(designation?.isActive ?? true)} name="is_active" options={[{ label: 'Active', value: 'true' }, { label: 'Inactive', value: 'false' }]} /></Field>
                    <div className="md:col-span-2 xl:col-span-3"><Field label="Description"><Textarea defaultValue={designation?.description} name="description" /></Field></div>
                </div>
                <div className="mt-5 flex justify-end gap-3">
                    <Link to="/hr/designations"><Button variant="secondary">Cancel</Button></Link>
                    <Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Designation' : 'Create Designation'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function EmploymentTypeForm({ onSaved, type }: { onSaved?: (type: EmploymentType) => void; type?: EmploymentType }) {
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const input: EmploymentTypeFormInput = {
            code: String(formData.get('employment_type_code') ?? ''),
            description: String(formData.get('description') ?? ''),
            isActive: booleanFromForm(formData.get('is_active'), true),
            name: String(formData.get('employment_type_name') ?? ''),
        };

        try {
            const response = type
                ? await hrApi.employmentTypes.update(type.id, input)
                : await hrApi.employmentTypes.create(input);
            onSaved?.(response.data);
            event.currentTarget.reset();
        } catch (error) {
            handleApiError(error, 'Unable to save employment type.', setErrors, setFormError);
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            <FormSection description="Employment types are reusable HR master data." title="Employment Type">
                {formError ? <div className="mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Field label="Code"><Input defaultValue={type?.code} name="employment_type_code" /><FieldError message={fieldError(errors, 'employment_type_code')} /></Field>
                    <Field label="Name"><Input defaultValue={type?.name} name="employment_type_name" /><FieldError message={fieldError(errors, 'employment_type_name')} /></Field>
                    <Field label="Active"><Select defaultValue={String(type?.isActive ?? true)} name="is_active" options={[{ label: 'Active', value: 'true' }, { label: 'Inactive', value: 'false' }]} /></Field>
                    <div className="md:col-span-2 xl:col-span-3"><Field label="Description"><Textarea defaultValue={type?.description} name="description" /></Field></div>
                </div>
                <div className="mt-5 flex justify-end">
                    <Button disabled={isSubmitting} type="submit" variant="blue">Save Employment Type</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function EmploymentTypeTable({ rows }: { rows: EmploymentType[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Code', key: 'code' },
                { header: 'Name', key: 'name' },
                { header: 'Description', key: 'description' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function AttendancePanel({ rows }: { rows: EmployeeAttendanceRecord[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Date', key: 'attendanceDate' },
                { header: 'Employee', key: 'employee' },
                { header: 'Backend total', key: 'backendTotal' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function LeavePanel({ rows }: { rows: EmployeeLeaveRecord[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Employee', key: 'employee' },
                { header: 'Leave type', key: 'leaveType' },
                { header: 'Window', key: 'window' },
                { header: 'Backend balance', key: 'backendBalance' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function SalaryProfilePanel({ profile }: { profile?: EmployeeSalaryProfile }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Salary type', value: profile?.salaryType ?? 'Backend profile' },
                { label: 'Payment method', value: profile?.paymentMethod ?? 'Backend payment method' },
                { label: 'Effective from', value: profile?.effectiveFrom ?? 'Backend effective date' },
                { label: 'Payable preview', value: profile?.backendPayablePreview ?? 'Backend calculated' },
            ]}
            status="Payroll"
            title="Salary Profile"
        />
    );
}

export function SalaryProfileTable({ rows }: { rows: EmployeeSalaryProfile[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Employee', key: 'employee' },
                { header: 'Type', key: 'salaryType' },
                { header: 'Effective from', key: 'effectiveFrom' },
                { header: 'Backend payable', key: 'backendPayablePreview' },
                { header: 'Payment method', key: 'paymentMethod' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}
