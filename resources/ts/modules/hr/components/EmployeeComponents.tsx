import { FormEvent, useMemo, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { ConfirmDialog } from '../../../shared/components/ui/ConfirmDialog';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import type {
    Department,
    Designation,
    Employee,
    EmployeeAddress,
    EmployeeAddressFormInput,
    EmployeeAuditEntry,
    EmployeeContact,
    EmployeeContactFormInput,
    EmployeeDocument,
    EmployeeFormInput,
    EmployeeSalaryProfile,
    EmployeeStatus,
    EmployeeUserAccess,
    EmployeeUserAccessCreateInput,
    EmployeeUserAccessLinkInput,
    EmploymentDetails,
    EmploymentDetailsFormInput,
    EmploymentType,
} from '../types/hr.types';

export type EmployeeFieldErrors = Record<string, string[]>;

export const employeeStatusOptions: Array<{ label: string; value: EmployeeStatus }> = [
    { label: 'Draft', value: 'draft' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'On leave', value: 'on_leave' },
    { label: 'Suspended', value: 'suspended' },
    { label: 'Terminated', value: 'terminated' },
    { label: 'Resigned', value: 'resigned' },
    { label: 'Archived', value: 'archived' },
];

type EmployeeLookups = {
    departments?: Department[];
    designations?: Designation[];
    employmentTypes?: EmploymentType[];
};

function Label({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return <div className="space-y-2">{label ? <Label>{label}</Label> : null}{children}</div>;
}

function formString(formData: FormData, key: string): string {
    return String(formData.get(key) ?? '').trim();
}

function formBoolean(formData: FormData, key: string, fallback = false): boolean {
    const value = formData.get(key);

    return value === null ? fallback : String(value) === 'true';
}

function firstFieldError(errors: EmployeeFieldErrors, ...keys: string[]): string | undefined {
    for (const key of keys) {
        const error = errors[key]?.[0];
        if (error) {
            return error;
        }
    }

    return undefined;
}

function toOptions(rows: Array<{ id: string; name: string }>) {
    return rows.map((row) => ({ label: row.name, value: row.id }));
}

function lookupName(id: string | undefined, rows: Array<{ id: string; name: string }>, fallback: string) {
    if (!id) {
        return fallback;
    }

    return rows.find((row) => row.id === id)?.name ?? fallback;
}

function employeeDepartment(employee: Employee, departments: Department[] = []) {
    return lookupName(employee.departmentId, departments, employee.department || 'No department');
}

function employeeDesignation(employee: Employee, designations: Designation[] = []) {
    return lookupName(employee.designationId, designations, employee.designation || 'No designation');
}

function employeeEmploymentType(employee: Employee, employmentTypes: EmploymentType[] = []) {
    return lookupName(employee.employmentTypeId, employmentTypes, employee.employmentType || 'No employment type');
}

function hasContactInput(input: EmployeeContactFormInput) {
    return [input.name, input.email, input.phone, input.mobile, input.relationship].some((value) => (value ?? '').trim() !== '');
}

function hasAddressInput(input: EmployeeAddressFormInput) {
    return [input.addressLine1, input.city, input.countryName, input.postalCode].some((value) => (value ?? '').trim() !== '');
}

function buildEmployeeInput(formData: FormData, includeCreateExtras: boolean): EmployeeFormInput {
    const input: EmployeeFormInput = {
        code: formString(formData, 'employee_code'),
        departmentId: formString(formData, 'department_id') || undefined,
        designationId: formString(formData, 'designation_id') || undefined,
        displayName: formString(formData, 'display_name') || undefined,
        email: formString(formData, 'email') || undefined,
        employmentTypeId: formString(formData, 'employment_type_id') || undefined,
        firstName: formString(formData, 'first_name'),
        joiningDate: formString(formData, 'joining_date') || undefined,
        lastName: formString(formData, 'last_name') || undefined,
        leavingDate: formString(formData, 'leaving_date') || undefined,
        mobile: formString(formData, 'mobile') || undefined,
        notes: formString(formData, 'notes') || undefined,
        phone: formString(formData, 'phone') || undefined,
        reportingManagerId: formString(formData, 'reporting_manager_id') || undefined,
        status: (formString(formData, 'employment_status') || 'draft') as EmployeeStatus,
    };

    if (!includeCreateExtras) {
        return input;
    }

    const primaryContact: EmployeeContactFormInput = {
        contactType: formString(formData, 'primary_contact_type') || 'primary',
        email: formString(formData, 'primary_contact_email') || undefined,
        isEmergency: formBoolean(formData, 'primary_contact_is_emergency'),
        isPrimary: formBoolean(formData, 'primary_contact_is_primary', true),
        mobile: formString(formData, 'primary_contact_mobile') || undefined,
        name: formString(formData, 'primary_contact_name'),
        phone: formString(formData, 'primary_contact_phone') || undefined,
        relationship: formString(formData, 'primary_contact_relationship') || undefined,
    };

    if (hasContactInput(primaryContact)) {
        input.primaryContact = primaryContact;
    }

    const primaryAddress: EmployeeAddressFormInput = {
        addressLine1: formString(formData, 'primary_address_line_1'),
        addressLine2: formString(formData, 'primary_address_line_2') || undefined,
        addressType: formString(formData, 'primary_address_type') || 'current',
        city: formString(formData, 'primary_address_city'),
        countryName: formString(formData, 'primary_address_country_name') || undefined,
        isPrimary: formBoolean(formData, 'primary_address_is_primary', true),
        postalCode: formString(formData, 'primary_address_postal_code') || undefined,
        stateProvince: formString(formData, 'primary_address_state_province') || undefined,
    };

    if (hasAddressInput(primaryAddress)) {
        input.primaryAddress = primaryAddress;
    }

    return input;
}

export function EmployeeStatusBadge({ status }: { status: EmployeeStatus | string }) {
    return <StatusBadge status={status.replace('_', ' ')} />;
}

export function EmployeeTable({ departments = [], designations = [], employmentTypes = [], rows }: EmployeeLookups & { rows: Employee[] }) {
    return (
        <DataTable
            columns={[
                {
                    header: 'Employee',
                    key: 'employee',
                    render: (row) => (
                        <div>
                            <Link className="font-semibold text-blue-700 hover:underline" to={`/hr/employees/${row.id}`}>
                                {row.code || row.fullName}
                            </Link>
                            <p className="mt-1 text-xs text-slate-500">{row.fullName || row.displayName}</p>
                        </div>
                    ),
                },
                { header: 'Department', key: 'department', render: (row) => employeeDepartment(row, departments) },
                { header: 'Designation', key: 'designation', render: (row) => employeeDesignation(row, designations) },
                { header: 'Employment type', key: 'employmentType', render: (row) => employeeEmploymentType(row, employmentTypes) },
                { header: 'User access', key: 'userAccess', render: (row) => (row.userAccess.length > 0 ? 'Linked' : 'Employee only') },
                { header: 'Status', key: 'status', render: (row) => <EmployeeStatusBadge status={row.status} /> },
                {
                    header: 'Actions',
                    key: 'actions',
                    render: (row) => (
                        <div className="flex flex-wrap gap-2">
                            <Link to={`/hr/employees/${row.id}`}>
                                <Button variant="ghost">View</Button>
                            </Link>
                            <Link to={`/hr/employees/${row.id}/edit`}>
                                <Button variant="secondary">Edit</Button>
                            </Link>
                        </div>
                    ),
                },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function EmployeeSummaryCard({ departments = [], designations = [], employee, employmentTypes = [] }: EmployeeLookups & { employee: Employee }) {
    const rows = [
        { label: 'Department', value: employeeDepartment(employee, departments) },
        { label: 'Designation', value: employeeDesignation(employee, designations) },
        { label: 'Employment type', value: employeeEmploymentType(employee, employmentTypes) },
        { label: 'Joining date', value: employee.joiningDate || 'Not set' },
        { label: 'Reporting manager', value: employee.reportingManager ?? employee.employmentDetails.manager ?? 'Not set' },
        { label: 'User access', value: employee.userAccess.length > 0 ? 'Linked separately' : 'No login access' },
        { label: 'Email', value: employee.email ?? 'Not set' },
        { label: 'Mobile', value: employee.mobile ?? employee.phone ?? 'Not set' },
    ];

    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{employee.code}</p>
                    <h2 className="mt-1 text-xl font-bold text-slate-950">{employee.displayName || employee.fullName}</h2>
                    <p className="mt-2 text-sm text-slate-500">Employee profile is separate from login identity.</p>
                </div>
                <EmployeeStatusBadge status={employee.status} />
            </div>
            <div className="mt-5 grid gap-3 text-sm md:grid-cols-2">
                {rows.map((row) => (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-3" key={`${employee.id}:${row.label}`}>
                        <p className="text-slate-500">{row.label}</p>
                        <p className="font-semibold text-slate-900">{row.value}</p>
                    </div>
                ))}
            </div>
        </Card>
    );
}

export function EmployeeForm({
    departments = [],
    designations = [],
    employee,
    employmentTypes = [],
    errors = {},
    globalError,
    isSaving,
    mode,
    onSubmit,
}: EmployeeLookups & {
    employee?: Employee;
    errors?: EmployeeFieldErrors;
    globalError?: string;
    isSaving?: boolean;
    mode: 'create' | 'edit';
    onSubmit: (input: EmployeeFormInput) => Promise<void> | void;
}) {
    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const input = buildEmployeeInput(new FormData(event.currentTarget), mode === 'create');

        try {
            await onSubmit(input);
        } catch {
            // Parent owns API error normalization and display.
        }
    }

    return (
        <form className="space-y-5" onSubmit={handleSubmit}>
            {globalError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{globalError}</div> : null}

            <FormSection description="Employee profile data is stored in HR. This form never creates a login user." title="Basic Info">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Field label="Employee code">
                        <Input defaultValue={employee?.code} name="employee_code" placeholder="EMP-0001" />
                        <FieldError message={firstFieldError(errors, 'employee_code')} />
                    </Field>
                    <Field label="First name">
                        <Input defaultValue={employee?.firstName} name="first_name" />
                        <FieldError message={firstFieldError(errors, 'first_name')} />
                    </Field>
                    <Field label="Last name">
                        <Input defaultValue={employee?.lastName} name="last_name" />
                        <FieldError message={firstFieldError(errors, 'last_name')} />
                    </Field>
                    <Field label="Display name">
                        <Input defaultValue={employee?.displayName} name="display_name" />
                        <FieldError message={firstFieldError(errors, 'display_name')} />
                    </Field>
                    <Field label="Email">
                        <Input defaultValue={employee?.email} name="email" type="email" />
                        <FieldError message={firstFieldError(errors, 'email')} />
                    </Field>
                    <Field label="Phone">
                        <Input defaultValue={employee?.phone} name="phone" />
                        <FieldError message={firstFieldError(errors, 'phone')} />
                    </Field>
                    <Field label="Mobile">
                        <Input defaultValue={employee?.mobile} name="mobile" />
                        <FieldError message={firstFieldError(errors, 'mobile')} />
                    </Field>
                    <Field label="Status">
                        {mode === 'create' ? (
                            <>
                                <Select defaultValue="draft" name="employment_status" options={employeeStatusOptions} />
                                <FieldError message={firstFieldError(errors, 'employment_status')} />
                            </>
                        ) : (
                            <div className="flex h-11 items-center rounded-lg border border-slate-200 bg-slate-50/60 px-3">
                                <input name="employment_status" type="hidden" value={employee?.status ?? 'draft'} />
                                <EmployeeStatusBadge status={employee?.status ?? 'draft'} />
                            </div>
                        )}
                    </Field>
                    <div className="md:col-span-2 xl:col-span-3">
                        <Field label="Notes">
                            <Textarea defaultValue={employee?.notes} name="notes" placeholder="Internal HR notes" />
                            <FieldError message={firstFieldError(errors, 'notes')} />
                        </Field>
                    </div>
                </div>
            </FormSection>

            <FormSection description="Department, designation, employment type, manager, and dates are validated by the HR backend." title="Employment Details">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Field label="Department">
                        <Select defaultValue={employee?.departmentId ?? employee?.employmentDetails.departmentId} name="department_id" options={toOptions(departments)} placeholder="No department" />
                        <FieldError message={firstFieldError(errors, 'department_id')} />
                    </Field>
                    <Field label="Designation">
                        <Select defaultValue={employee?.designationId ?? employee?.employmentDetails.designationId} name="designation_id" options={toOptions(designations)} placeholder="No designation" />
                        <FieldError message={firstFieldError(errors, 'designation_id')} />
                    </Field>
                    <Field label="Employment type">
                        <Select defaultValue={employee?.employmentTypeId ?? employee?.employmentDetails.employmentTypeId} name="employment_type_id" options={toOptions(employmentTypes)} placeholder="No employment type" />
                        <FieldError message={firstFieldError(errors, 'employment_type_id')} />
                    </Field>
                    <Field label="Joining date">
                        <Input defaultValue={employee?.joiningDate} name="joining_date" type="date" />
                        <FieldError message={firstFieldError(errors, 'joining_date')} />
                    </Field>
                    <Field label="Leaving date">
                        <Input defaultValue={employee?.employmentDetails.leavingDate} name="leaving_date" type="date" />
                        <FieldError message={firstFieldError(errors, 'leaving_date')} />
                    </Field>
                    <Field label="Reporting manager ID">
                        <Input defaultValue={employee?.reportingManagerId ?? employee?.employmentDetails.reportingManagerId} name="reporting_manager_id" placeholder="Optional employee ID" />
                        <FieldError message={firstFieldError(errors, 'reporting_manager_id')} />
                    </Field>
                </div>
            </FormSection>

            {mode === 'create' ? (
                <FormSection description="Optional starter contact and address records are saved through nested HR backend validation." title="Optional Contact / Address">
                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="rounded-lg border border-slate-200 p-4">
                            <h3 className="text-sm font-bold text-slate-900">Primary Contact</h3>
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <Field label="Name">
                                    <Input name="primary_contact_name" />
                                    <FieldError message={firstFieldError(errors, 'contacts.0.contact_name')} />
                                </Field>
                                <Field label="Type">
                                    <Input defaultValue="primary" name="primary_contact_type" />
                                    <FieldError message={firstFieldError(errors, 'contacts.0.contact_type')} />
                                </Field>
                                <Field label="Email">
                                    <Input name="primary_contact_email" type="email" />
                                    <FieldError message={firstFieldError(errors, 'contacts.0.email')} />
                                </Field>
                                <Field label="Phone">
                                    <Input name="primary_contact_phone" />
                                    <FieldError message={firstFieldError(errors, 'contacts.0.phone')} />
                                </Field>
                                <Field label="Mobile">
                                    <Input name="primary_contact_mobile" />
                                    <FieldError message={firstFieldError(errors, 'contacts.0.mobile')} />
                                </Field>
                                <Field label="Relationship">
                                    <Input name="primary_contact_relationship" />
                                    <FieldError message={firstFieldError(errors, 'contacts.0.relationship')} />
                                </Field>
                                <Field label="Primary">
                                    <Select defaultValue="true" name="primary_contact_is_primary" options={[{ label: 'Yes', value: 'true' }, { label: 'No', value: 'false' }]} />
                                </Field>
                                <Field label="Emergency">
                                    <Select defaultValue="false" name="primary_contact_is_emergency" options={[{ label: 'No', value: 'false' }, { label: 'Yes', value: 'true' }]} />
                                </Field>
                            </div>
                        </div>

                        <div className="rounded-lg border border-slate-200 p-4">
                            <h3 className="text-sm font-bold text-slate-900">Primary Address</h3>
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <Field label="Address type">
                                    <Select defaultValue="current" name="primary_address_type" options={[{ label: 'Current', value: 'current' }, { label: 'Permanent', value: 'permanent' }, { label: 'Temporary', value: 'temporary' }, { label: 'Other', value: 'other' }]} />
                                    <FieldError message={firstFieldError(errors, 'addresses.0.address_type')} />
                                </Field>
                                <Field label="Line 1">
                                    <Input name="primary_address_line_1" />
                                    <FieldError message={firstFieldError(errors, 'addresses.0.address_line_1')} />
                                </Field>
                                <Field label="Line 2">
                                    <Input name="primary_address_line_2" />
                                    <FieldError message={firstFieldError(errors, 'addresses.0.address_line_2')} />
                                </Field>
                                <Field label="City">
                                    <Input name="primary_address_city" />
                                    <FieldError message={firstFieldError(errors, 'addresses.0.city')} />
                                </Field>
                                <Field label="State / Province">
                                    <Input name="primary_address_state_province" />
                                    <FieldError message={firstFieldError(errors, 'addresses.0.state_province')} />
                                </Field>
                                <Field label="Postal code">
                                    <Input name="primary_address_postal_code" />
                                    <FieldError message={firstFieldError(errors, 'addresses.0.postal_code')} />
                                </Field>
                                <Field label="Country">
                                    <Input name="primary_address_country_name" />
                                    <FieldError message={firstFieldError(errors, 'addresses.0.country_name')} />
                                </Field>
                                <Field label="Primary">
                                    <Select defaultValue="true" name="primary_address_is_primary" options={[{ label: 'Yes', value: 'true' }, { label: 'No', value: 'false' }]} />
                                </Field>
                            </div>
                        </div>
                    </div>
                </FormSection>
            ) : (
                <PreviewPanel
                    rows={[
                        { label: 'Contacts', value: 'Managed on the employee detail page' },
                        { label: 'Addresses', value: 'Managed on the employee detail page' },
                    ]}
                    status="Separate records"
                    title="Contact / Address Management"
                />
            )}

            <PreviewPanel
                rows={[
                    { label: 'Employee profile', value: 'Saved in HR employee tables' },
                    { label: 'Login user', value: 'Not created by this form' },
                    { label: 'User access', value: 'Linked explicitly from the detail page' },
                ]}
                status="User optional"
                title="User Access Boundary"
            />

            <div className="flex flex-wrap justify-end gap-3">
                <Link to="/hr/employees">
                    <Button variant="secondary">Cancel</Button>
                </Link>
                <Button disabled={isSaving} type="submit" variant="blue">{mode === 'edit' ? 'Update Employee' : 'Create Employee'}</Button>
            </div>
        </form>
    );
}

export function EmployeeContactsPanel({
    errors = {},
    globalError,
    isSaving,
    onCreate,
    rows,
}: {
    errors?: EmployeeFieldErrors;
    globalError?: string;
    isSaving?: boolean;
    onCreate: (input: EmployeeContactFormInput) => Promise<void> | void;
    rows: EmployeeContact[];
}) {
    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);

        try {
            await onCreate({
                contactType: formString(formData, 'contact_type') || 'primary',
                email: formString(formData, 'email') || undefined,
                isEmergency: formBoolean(formData, 'is_emergency'),
                isPrimary: formBoolean(formData, 'is_primary'),
                mobile: formString(formData, 'mobile') || undefined,
                name: formString(formData, 'contact_name'),
                phone: formString(formData, 'phone') || undefined,
                relationship: formString(formData, 'relationship') || undefined,
            });
            form.reset();
        } catch {
            // Parent owns API error normalization and display.
        }
    }

    return (
        <div className="space-y-5">
            <FormSection description="Contacts are employee-scoped HR records. Backend validates and persists them." title="Add Contact">
                <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={handleSubmit}>
                    {globalError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-2 xl:col-span-4">{globalError}</div> : null}
                    <Field label="Name">
                        <Input name="contact_name" />
                        <FieldError message={firstFieldError(errors, 'contact_name')} />
                    </Field>
                    <Field label="Type">
                        <Input defaultValue="primary" name="contact_type" />
                        <FieldError message={firstFieldError(errors, 'contact_type')} />
                    </Field>
                    <Field label="Email">
                        <Input name="email" type="email" />
                        <FieldError message={firstFieldError(errors, 'email')} />
                    </Field>
                    <Field label="Phone">
                        <Input name="phone" />
                        <FieldError message={firstFieldError(errors, 'phone')} />
                    </Field>
                    <Field label="Mobile">
                        <Input name="mobile" />
                        <FieldError message={firstFieldError(errors, 'mobile')} />
                    </Field>
                    <Field label="Relationship">
                        <Input name="relationship" />
                        <FieldError message={firstFieldError(errors, 'relationship')} />
                    </Field>
                    <Field label="Primary">
                        <Select defaultValue="false" name="is_primary" options={[{ label: 'No', value: 'false' }, { label: 'Yes', value: 'true' }]} />
                    </Field>
                    <Field label="Emergency">
                        <Select defaultValue="false" name="is_emergency" options={[{ label: 'No', value: 'false' }, { label: 'Yes', value: 'true' }]} />
                    </Field>
                    <div className="md:col-span-2 xl:col-span-4">
                        <Button disabled={isSaving} type="submit" variant="secondary">Add Contact</Button>
                    </div>
                </form>
            </FormSection>

            {rows.length === 0 ? <EmptyState description="No employee contacts have been saved yet." title="No contacts" /> : (
                <DataTable
                    columns={[
                        { header: 'Name', key: 'name' },
                        { header: 'Type', key: 'contactType' },
                        { header: 'Phone', key: 'phone' },
                        { header: 'Mobile', key: 'mobile' },
                        { header: 'Email', key: 'email' },
                        { header: 'Primary', key: 'isPrimary', render: (row) => (row.isPrimary ? 'Primary' : '') },
                        { header: 'Emergency', key: 'isEmergency', render: (row) => (row.isEmergency ? 'Emergency' : '') },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={rows}
                />
            )}
        </div>
    );
}

export function EmployeeAddressesPanel({
    errors = {},
    globalError,
    isSaving,
    onCreate,
    rows,
}: {
    errors?: EmployeeFieldErrors;
    globalError?: string;
    isSaving?: boolean;
    onCreate: (input: EmployeeAddressFormInput) => Promise<void> | void;
    rows: EmployeeAddress[];
}) {
    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);

        try {
            await onCreate({
                addressLine1: formString(formData, 'address_line_1'),
                addressLine2: formString(formData, 'address_line_2') || undefined,
                addressType: formString(formData, 'address_type') || 'current',
                city: formString(formData, 'city'),
                countryName: formString(formData, 'country_name') || undefined,
                isPrimary: formBoolean(formData, 'is_primary'),
                postalCode: formString(formData, 'postal_code') || undefined,
                stateProvince: formString(formData, 'state_province') || undefined,
            });
            form.reset();
        } catch {
            // Parent owns API error normalization and display.
        }
    }

    return (
        <div className="space-y-5">
            <FormSection description="Addresses are employee-scoped HR records. Backend validates and persists them." title="Add Address">
                <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={handleSubmit}>
                    {globalError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-2 xl:col-span-4">{globalError}</div> : null}
                    <Field label="Address type">
                        <Select defaultValue="current" name="address_type" options={[{ label: 'Current', value: 'current' }, { label: 'Permanent', value: 'permanent' }, { label: 'Temporary', value: 'temporary' }, { label: 'Other', value: 'other' }]} />
                        <FieldError message={firstFieldError(errors, 'address_type')} />
                    </Field>
                    <Field label="Line 1">
                        <Input name="address_line_1" />
                        <FieldError message={firstFieldError(errors, 'address_line_1')} />
                    </Field>
                    <Field label="Line 2">
                        <Input name="address_line_2" />
                        <FieldError message={firstFieldError(errors, 'address_line_2')} />
                    </Field>
                    <Field label="City">
                        <Input name="city" />
                        <FieldError message={firstFieldError(errors, 'city')} />
                    </Field>
                    <Field label="State / Province">
                        <Input name="state_province" />
                        <FieldError message={firstFieldError(errors, 'state_province')} />
                    </Field>
                    <Field label="Postal code">
                        <Input name="postal_code" />
                        <FieldError message={firstFieldError(errors, 'postal_code')} />
                    </Field>
                    <Field label="Country">
                        <Input name="country_name" />
                        <FieldError message={firstFieldError(errors, 'country_name')} />
                    </Field>
                    <Field label="Primary">
                        <Select defaultValue="false" name="is_primary" options={[{ label: 'No', value: 'false' }, { label: 'Yes', value: 'true' }]} />
                    </Field>
                    <div className="md:col-span-2 xl:col-span-4">
                        <Button disabled={isSaving} type="submit" variant="secondary">Add Address</Button>
                    </div>
                </form>
            </FormSection>

            {rows.length === 0 ? <EmptyState description="No employee addresses have been saved yet." title="No addresses" /> : (
                <DataTable
                    columns={[
                        { header: 'Type', key: 'addressType' },
                        { header: 'Address', key: 'address', render: (row) => [row.addressLine1, row.addressLine2, row.city].filter(Boolean).join(', ') },
                        { header: 'State', key: 'stateProvince' },
                        { header: 'Postal code', key: 'postalCode' },
                        { header: 'Country', key: 'countryName' },
                        { header: 'Primary', key: 'isPrimary', render: (row) => (row.isPrimary ? 'Primary' : '') },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={rows}
                />
            )}
        </div>
    );
}

export function EmploymentDetailsPanel({
    departments = [],
    designations = [],
    employee,
    employmentTypes = [],
    errors = {},
    globalError,
    isSaving,
    onSubmit,
}: EmployeeLookups & {
    employee: Employee;
    errors?: EmployeeFieldErrors;
    globalError?: string;
    isSaving?: boolean;
    onSubmit: (input: EmploymentDetailsFormInput) => Promise<void> | void;
}) {
    const details = employee.employmentDetails;

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);

        try {
            await onSubmit({
                departmentId: formString(formData, 'department_id') || undefined,
                designationId: formString(formData, 'designation_id') || undefined,
                employmentStatus: (formString(formData, 'employment_status') || employee.status) as EmployeeStatus,
                employmentTypeId: formString(formData, 'employment_type_id') || undefined,
                joiningDate: formString(formData, 'joining_date') || undefined,
                leavingDate: formString(formData, 'leaving_date') || undefined,
                probationEndDate: formString(formData, 'probation_end_date') || undefined,
                reportingManagerId: formString(formData, 'reporting_manager_id') || undefined,
            });
        } catch {
            // Parent owns API error normalization and display.
        }
    }

    return (
        <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
            <FormSection description="Employment details are stored separately from login access and validated by backend." title="Employment Details">
                <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-3" onSubmit={handleSubmit}>
                    {globalError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-2 xl:col-span-3">{globalError}</div> : null}
                    <Field label="Department">
                        <Select defaultValue={details.departmentId ?? employee.departmentId} name="department_id" options={toOptions(departments)} placeholder="No department" />
                        <FieldError message={firstFieldError(errors, 'department_id')} />
                    </Field>
                    <Field label="Designation">
                        <Select defaultValue={details.designationId ?? employee.designationId} name="designation_id" options={toOptions(designations)} placeholder="No designation" />
                        <FieldError message={firstFieldError(errors, 'designation_id')} />
                    </Field>
                    <Field label="Employment type">
                        <Select defaultValue={details.employmentTypeId ?? employee.employmentTypeId} name="employment_type_id" options={toOptions(employmentTypes)} placeholder="No employment type" />
                        <FieldError message={firstFieldError(errors, 'employment_type_id')} />
                    </Field>
                    <Field label="Employment status">
                        <Select defaultValue={details.employmentStatus ?? employee.status} name="employment_status" options={employeeStatusOptions} />
                        <FieldError message={firstFieldError(errors, 'employment_status')} />
                    </Field>
                    <Field label="Joining date">
                        <Input defaultValue={details.joiningDate || employee.joiningDate} name="joining_date" type="date" />
                        <FieldError message={firstFieldError(errors, 'joining_date')} />
                    </Field>
                    <Field label="Probation end date">
                        <Input defaultValue={details.probationEndDate} name="probation_end_date" type="date" />
                        <FieldError message={firstFieldError(errors, 'probation_end_date')} />
                    </Field>
                    <Field label="Leaving date">
                        <Input defaultValue={details.leavingDate} name="leaving_date" type="date" />
                        <FieldError message={firstFieldError(errors, 'leaving_date')} />
                    </Field>
                    <Field label="Reporting manager ID">
                        <Input defaultValue={details.reportingManagerId ?? employee.reportingManagerId} name="reporting_manager_id" />
                        <FieldError message={firstFieldError(errors, 'reporting_manager_id')} />
                    </Field>
                    <div className="md:col-span-2 xl:col-span-3">
                        <Button disabled={isSaving} type="submit" variant="blue">Update Employment Details</Button>
                    </div>
                </form>
            </FormSection>

            <PreviewPanel
                rows={[
                    { label: 'Department', value: employeeDepartment(employee, departments) },
                    { label: 'Designation', value: employeeDesignation(employee, designations) },
                    { label: 'Employment type', value: employeeEmploymentType(employee, employmentTypes) },
                    { label: 'Status', value: details.employmentStatus ?? employee.status },
                    { label: 'Manager', value: details.manager || 'Not set' },
                ]}
                status="Backend owned"
                title="Current Employment Context"
            />
        </div>
    );
}

export function EmployeeUserAccessPanel({
    errors = {},
    globalError,
    isSaving,
    onInviteUser,
    onLinkExisting,
    rows,
}: {
    errors?: EmployeeFieldErrors;
    globalError?: string;
    isSaving?: boolean;
    onInviteUser: (input: EmployeeUserAccessCreateInput) => Promise<void> | void;
    onLinkExisting: (input: EmployeeUserAccessLinkInput) => Promise<void> | void;
    rows: EmployeeUserAccess[];
}) {
    async function handleLinkExisting(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);

        try {
            await onLinkExisting({
                accessRole: formString(formData, 'access_role') || undefined,
                invited: formBoolean(formData, 'invited'),
                isPrimary: formBoolean(formData, 'is_primary'),
                userId: formString(formData, 'user_id'),
            });
            form.reset();
        } catch {
            // Parent owns API error normalization and display.
        }
    }

    async function handleInviteUser(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);

        try {
            await onInviteUser({
                accessRole: formString(formData, 'invite_access_role') || undefined,
                email: formString(formData, 'user_email'),
                invited: true,
                isPrimary: formBoolean(formData, 'invite_is_primary'),
                name: formString(formData, 'user_name') || undefined,
            });
            form.reset();
        } catch {
            // Parent owns API error normalization and display.
        }
    }

    return (
        <div className="space-y-5">
            <PreviewPanel
                rows={[
                    { label: 'Employee identity', value: 'HR employee profile' },
                    { label: 'Login identity', value: 'Linked only when explicitly requested' },
                    { label: 'Automatic user creation', value: 'Disabled in employee create/edit' },
                ]}
                status="Optional"
                title="Employee User Access"
            />
            <div className="grid gap-5 xl:grid-cols-2">
                <FormSection description="Use this when a login user already exists." title="Link Existing User">
                    <form className="grid gap-4 md:grid-cols-2" onSubmit={handleLinkExisting}>
                        {globalError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-2">{globalError}</div> : null}
                        <Field label="User ID">
                            <Input name="user_id" />
                            <FieldError message={firstFieldError(errors, 'user_id')} />
                        </Field>
                        <Field label="Access role">
                            <Input name="access_role" placeholder="employee_portal" />
                            <FieldError message={firstFieldError(errors, 'access_role')} />
                        </Field>
                        <Field label="Primary">
                            <Select defaultValue="false" name="is_primary" options={[{ label: 'No', value: 'false' }, { label: 'Yes', value: 'true' }]} />
                        </Field>
                        <Field label="Invite state">
                            <Select defaultValue="false" name="invited" options={[{ label: 'Active link', value: 'false' }, { label: 'Invited', value: 'true' }]} />
                        </Field>
                        <div className="md:col-span-2">
                            <Button disabled={isSaving} type="submit" variant="secondary">Link User</Button>
                        </div>
                    </form>
                </FormSection>

                <FormSection description="Use this only when access should be explicitly created or invited." title="Invite User Access">
                    <form className="grid gap-4 md:grid-cols-2" onSubmit={handleInviteUser}>
                        <Field label="Name">
                            <Input name="user_name" />
                            <FieldError message={firstFieldError(errors, 'user.name')} />
                        </Field>
                        <Field label="Email">
                            <Input name="user_email" type="email" />
                            <FieldError message={firstFieldError(errors, 'user.email')} />
                        </Field>
                        <Field label="Access role">
                            <Input name="invite_access_role" placeholder="employee_portal" />
                            <FieldError message={firstFieldError(errors, 'access_role')} />
                        </Field>
                        <Field label="Primary">
                            <Select defaultValue="false" name="invite_is_primary" options={[{ label: 'No', value: 'false' }, { label: 'Yes', value: 'true' }]} />
                        </Field>
                        <div className="md:col-span-2">
                            <Button disabled={isSaving} type="submit" variant="secondary">Invite User Access</Button>
                        </div>
                    </form>
                </FormSection>
            </div>

            {rows.length === 0 ? <EmptyState description="This employee currently has no login access linked." title="No user access" /> : (
                <DataTable
                    columns={[
                        { header: 'User', key: 'userName' },
                        { header: 'Email', key: 'userEmail' },
                        { header: 'Role', key: 'accessRole' },
                        { header: 'Primary', key: 'isPrimary', render: (row) => (row.isPrimary ? 'Primary' : '') },
                        { header: 'Status', key: 'accessStatus', render: (row) => <StatusBadge status={row.accessStatus} /> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={rows}
                />
            )}
        </div>
    );
}

export function EmployeeStatusActions({
    employee,
    isSaving,
    onChangeStatus,
}: {
    employee: Employee;
    isSaving?: boolean;
    onChangeStatus: (status: EmployeeStatus, reason?: string) => Promise<void> | void;
}) {
    const [pendingStatus, setPendingStatus] = useState<EmployeeStatus | null>(null);
    const [reason, setReason] = useState('');
    const [error, setError] = useState('');
    const actions: Array<{ label: string; status: EmployeeStatus; variant?: 'secondary' | 'danger' | 'blue' }> = [
        { label: 'Activate', status: 'active', variant: 'blue' },
        { label: 'Deactivate', status: 'inactive', variant: 'secondary' },
        { label: 'Suspend', status: 'suspended', variant: 'secondary' },
        { label: 'Terminate', status: 'terminated', variant: 'danger' },
    ];

    async function confirmStatusChange() {
        if (!pendingStatus) {
            return;
        }

        setError('');
        try {
            await onChangeStatus(pendingStatus, reason || undefined);
            setPendingStatus(null);
            setReason('');
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to change employee status.');
        }
    }

    return (
        <Card className="p-5">
            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">Status Actions</h3>
            <p className="mt-1 text-sm text-slate-500">Backend validates status transitions and writes history.</p>
            {error ? <div className="mt-4 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">{error}</div> : null}
            <div className="mt-4 space-y-3">
                <Input onChange={(event) => setReason(event.target.value)} placeholder="Reason for status change" value={reason} />
                <div className="flex flex-wrap gap-2">
                    {actions.map((action) => (
                        <Button
                            disabled={isSaving || employee.status === action.status}
                            key={action.status}
                            onClick={() => setPendingStatus(action.status)}
                            variant={action.variant}
                        >
                            {action.label}
                        </Button>
                    ))}
                </div>
            </div>
            <p className="mt-4 text-sm font-semibold text-slate-600">Current status: {employee.status.replace('_', ' ')}</p>
            <ConfirmDialog
                message={`Change employee status to ${pendingStatus?.replace('_', ' ')}?`}
                onCancel={() => setPendingStatus(null)}
                onConfirm={() => void confirmStatusChange()}
                open={pendingStatus !== null}
                title="Confirm Status Change"
            />
        </Card>
    );
}

export function EmployeeDocumentsPanel({ rows }: { rows: EmployeeDocument[] }) {
    if (rows.length === 0) {
        return <EmptyState description="No employee documents were returned by the backend." title="No documents" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Document', key: 'documentNumber' },
                { header: 'Type', key: 'documentType' },
                { header: 'Expiry', key: 'expiryDate' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function EmployeeSalaryProfilePanel({ profile }: { profile?: EmployeeSalaryProfile }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Salary type', value: profile?.salaryType ?? 'Not returned' },
                { label: 'Payment method', value: profile?.paymentMethod ?? 'Not returned' },
                { label: 'Effective from', value: profile?.effectiveFrom ?? 'Not returned' },
                { label: 'Payable preview', value: profile?.backendPayablePreview ?? 'Backend calculated' },
            ]}
            status="Backend readonly"
            title="Salary Profile"
        />
    );
}

export function EmployeeActivityPanel({ rows }: { rows: EmployeeAuditEntry[] }) {
    if (rows.length === 0) {
        return <EmptyState description="No employee activity or audit records were returned by the backend." title="No activity" />;
    }

    return (
        <div className="space-y-3">
            {rows.map((entry) => (
                <Card className="p-4" key={entry.id}>
                    <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="font-semibold text-slate-900">{entry.note}</p>
                            <p className="mt-1 text-sm text-slate-500">{entry.actor} / {entry.type}</p>
                        </div>
                        <div className="text-sm text-slate-500">{entry.timestamp}</div>
                    </div>
                </Card>
            ))}
        </div>
    );
}

export function EmployeeOverviewPanels({ employee }: { employee: Employee }) {
    const rows = useMemo(() => [
        { label: 'Attendance', value: 'Backend owned' },
        { label: 'Leave balance', value: 'Backend owned' },
        { label: 'Payroll', value: 'Backend owned' },
        { label: 'Vehicle modules', value: 'Use HR lookup/validation only' },
    ], []);

    return <PreviewPanel rows={rows} status={employee.status} title="HR Boundaries" />;
}
