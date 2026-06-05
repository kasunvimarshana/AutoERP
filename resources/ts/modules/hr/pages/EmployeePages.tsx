import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import {
    EmployeeActivityPanel,
    EmployeeAddressesPanel,
    EmployeeContactsPanel,
    EmployeeDocumentsPanel,
    EmployeeForm,
    EmployeeOverviewPanels,
    EmployeeSalaryProfilePanel,
    EmployeeStatusActions,
    EmployeeSummaryCard,
    EmployeeTable,
    EmployeeUserAccessPanel,
    EmploymentDetailsPanel,
    type EmployeeFieldErrors,
    employeeStatusOptions,
} from '../components/EmployeeComponents';
import { hrApi } from '../services/hrApi';
import type {
    Department,
    Designation,
    Employee,
    EmployeeAddressFormInput,
    EmployeeContactFormInput,
    EmployeeFormInput,
    EmployeeStatus,
    EmployeeUserAccessCreateInput,
    EmployeeUserAccessLinkInput,
    EmploymentDetails,
    EmploymentDetailsFormInput,
    EmploymentType,
} from '../types/hr.types';

type EmployeeLookups = {
    departments: Department[];
    designations: Designation[];
    employmentTypes: EmploymentType[];
};

type FormErrorState = {
    errors: EmployeeFieldErrors;
    message: string;
};

const emptyLookups: EmployeeLookups = {
    departments: [],
    designations: [],
    employmentTypes: [],
};

const emptyFormError: FormErrorState = {
    errors: {},
    message: '',
};

function pageError(error: unknown, fallback: string) {
    if (error instanceof ApiError) {
        return error.message;
    }

    return error instanceof Error ? error.message : fallback;
}

function formError(error: unknown, fallback: string): FormErrorState {
    if (error instanceof ApiError) {
        return { errors: error.errors, message: error.message };
    }

    return { errors: {}, message: error instanceof Error ? error.message : fallback };
}

function mergeEmploymentDetails(employee: Employee, details: EmploymentDetails): EmploymentDetails {
    const hasBackendDetails = Boolean(details.departmentId || details.designationId || details.employmentTypeId || details.joiningDate || details.reportingManagerId);

    return hasBackendDetails ? details : employee.employmentDetails;
}

async function fetchEmployeeDetail(id: string): Promise<Employee> {
    const employee = (await hrApi.employees.get(id)).data;
    const [contacts, addresses, employmentDetails, userAccess] = await Promise.all([
        hrApi.contacts.list(id),
        hrApi.addresses.list(id),
        hrApi.employmentDetails.get(id),
        hrApi.userAccess.list(id),
    ]);

    return {
        ...employee,
        addresses: addresses.data,
        contacts: contacts.data,
        employmentDetails: mergeEmploymentDetails(employee, employmentDetails.data),
        userAccess: userAccess.data,
    };
}

function useEmployeeLookups() {
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [lookups, setLookups] = useState<EmployeeLookups>(emptyLookups);

    useEffect(() => {
        let mounted = true;

        Promise.all([
            hrApi.departments.list({ isActive: true }),
            hrApi.designations.list({ isActive: true }),
            hrApi.employmentTypes.list({ isActive: true }),
        ])
            .then(([departments, designations, employmentTypes]) => {
                if (!mounted) {
                    return;
                }

                setLookups({
                    departments: departments.data,
                    designations: designations.data,
                    employmentTypes: employmentTypes.data,
                });
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setError(pageError(error, 'Unable to load HR setup data.'));
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, []);

    return { error, isLoading, lookups };
}

export function EmployeeListPage() {
    const { error: lookupError, isLoading: lookupsLoading, lookups } = useEmployeeLookups();
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [rows, setRows] = useState<Employee[]>([]);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');

    useEffect(() => {
        let mounted = true;

        setIsLoading(true);
        setError('');
        hrApi.employees
            .list({ search, status: status ? (status as EmployeeStatus) : undefined })
            .then((response) => {
                if (mounted) {
                    setRows(response.data);
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setError(pageError(error, 'Unable to load employees.'));
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [search, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'status') {
            setStatus(typeof value === 'string' ? value : '');
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/hr/employees/new"><Button>New Employee</Button></Link>}
                eyebrow="HR"
                subtitle="Employees are HR/staff profiles. Login user access is optional and managed separately."
                title="Employees"
            />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: employeeStatusOptions, placeholder: 'All statuses', type: 'status' }]}
                isLoading={lookupsLoading || isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={(filterId) => updateFilter(filterId, undefined)}
                onResetFilters={() => setStatus('')}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views need a user-preferences backend before they can be enabled for employee lists."
                searchPlaceholder="Search employee code, name, email, or mobile..."
                searchValue={search}
            />
            {lookupsLoading || isLoading ? <EmptyState description="Loading employees from the HR API..." title="Loading employees" /> : null}
            {lookupError || error ? <EmptyState description={lookupError || error} title="Unable to load employees" /> : null}
            {!lookupsLoading && !isLoading && !lookupError && !error && rows.length === 0 ? (
                <EmptyState description="Create an employee profile without creating a user account." title="No employees found" />
            ) : null}
            {!lookupsLoading && !isLoading && !lookupError && !error && rows.length > 0 ? (
                <EmployeeTable
                    departments={lookups.departments}
                    designations={lookups.designations}
                    employmentTypes={lookups.employmentTypes}
                    rows={rows}
                />
            ) : null}
        </div>
    );
}

export function EmployeeCreatePage() {
    const navigate = useNavigate();
    const { error, isLoading, lookups } = useEmployeeLookups();
    const [formState, setFormState] = useState<FormErrorState>(emptyFormError);
    const [isSaving, setIsSaving] = useState(false);

    async function handleSubmit(input: EmployeeFormInput) {
        setFormState(emptyFormError);
        setIsSaving(true);

        try {
            const response = await hrApi.employees.create(input);
            navigate(`/hr/employees/${response.data.id}`);
        } catch (error) {
            setFormState(formError(error, 'Unable to create employee.'));
            throw error;
        } finally {
            setIsSaving(false);
        }
    }

    if (isLoading) {
        return <EmptyState description="Loading HR setup before employee creation..." title="Loading HR setup" />;
    }

    if (error) {
        return <EmptyState description={error} title="Unable to create employee" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="HR"
                subtitle="Create an employee profile. This does not create a user account."
                title="New Employee"
            />
            <EmployeeForm
                departments={lookups.departments}
                designations={lookups.designations}
                employmentTypes={lookups.employmentTypes}
                errors={formState.errors}
                globalError={formState.message}
                isSaving={isSaving}
                mode="create"
                onSubmit={handleSubmit}
            />
        </div>
    );
}

export function EmployeeEditPage() {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const { error: lookupError, isLoading: lookupsLoading, lookups } = useEmployeeLookups();
    const [employee, setEmployee] = useState<Employee | null>(null);
    const [error, setError] = useState('');
    const [formState, setFormState] = useState<FormErrorState>(emptyFormError);
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        let mounted = true;

        setIsLoading(true);
        hrApi.employees
            .get(id)
            .then((response) => {
                if (mounted) {
                    setEmployee(response.data);
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setError(pageError(error, 'Unable to load employee.'));
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [id]);

    async function handleSubmit(input: EmployeeFormInput) {
        if (!employee) {
            return;
        }

        setFormState(emptyFormError);
        setIsSaving(true);

        try {
            const response = await hrApi.employees.update(employee.id, input);
            navigate(`/hr/employees/${response.data.id}`);
        } catch (error) {
            setFormState(formError(error, 'Unable to update employee.'));
            throw error;
        } finally {
            setIsSaving(false);
        }
    }

    if (isLoading || lookupsLoading) {
        return <EmptyState description="Loading employee and HR setup..." title="Loading employee" />;
    }

    if (error || lookupError || !employee) {
        return <EmptyState description={error || lookupError || 'Employee was not found.'} title="Unable to edit employee" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="HR"
                subtitle="Edit the employee HR profile. User access remains separate."
                title={`Edit ${employee.displayName || employee.fullName}`}
            />
            <EmployeeForm
                departments={lookups.departments}
                designations={lookups.designations}
                employee={employee}
                employmentTypes={lookups.employmentTypes}
                errors={formState.errors}
                globalError={formState.message}
                isSaving={isSaving}
                mode="edit"
                onSubmit={handleSubmit}
            />
        </div>
    );
}

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Contacts', value: 'contacts' },
    { label: 'Addresses', value: 'addresses' },
    { label: 'Employment Details', value: 'employment' },
    { label: 'User Access', value: 'user' },
    { label: 'Documents', value: 'documents' },
    { label: 'Salary Profile', value: 'salary' },
    { label: 'Activity / Audit', value: 'audit' },
];

export function EmployeeDetailPage() {
    const { id = '' } = useParams();
    const { error: lookupError, isLoading: lookupsLoading, lookups } = useEmployeeLookups();
    const [activeTab, setActiveTab] = useState('overview');
    const [addressForm, setAddressForm] = useState<FormErrorState>(emptyFormError);
    const [contactForm, setContactForm] = useState<FormErrorState>(emptyFormError);
    const [employee, setEmployee] = useState<Employee | null>(null);
    const [employmentForm, setEmploymentForm] = useState<FormErrorState>(emptyFormError);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [operation, setOperation] = useState('');
    const [statusMessage, setStatusMessage] = useState('');
    const [userAccessForm, setUserAccessForm] = useState<FormErrorState>(emptyFormError);

    const loadEmployee = useCallback(async () => {
        const employee = await fetchEmployeeDetail(id);
        setEmployee(employee);
    }, [id]);

    useEffect(() => {
        let mounted = true;

        setIsLoading(true);
        setError('');
        fetchEmployeeDetail(id)
            .then((employee) => {
                if (mounted) {
                    setEmployee(employee);
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setError(pageError(error, 'Unable to load employee.'));
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [id]);

    async function handleContactCreate(input: EmployeeContactFormInput) {
        setContactForm(emptyFormError);
        setOperation('contact');

        try {
            await hrApi.contacts.create(id, input);
            await loadEmployee();
        } catch (error) {
            setContactForm(formError(error, 'Unable to save contact.'));
            throw error;
        } finally {
            setOperation('');
        }
    }

    async function handleAddressCreate(input: EmployeeAddressFormInput) {
        setAddressForm(emptyFormError);
        setOperation('address');

        try {
            await hrApi.addresses.create(id, input);
            await loadEmployee();
        } catch (error) {
            setAddressForm(formError(error, 'Unable to save address.'));
            throw error;
        } finally {
            setOperation('');
        }
    }

    async function handleEmploymentUpdate(input: EmploymentDetailsFormInput) {
        setEmploymentForm(emptyFormError);
        setOperation('employment');

        try {
            await hrApi.employmentDetails.update(id, input);
            await loadEmployee();
        } catch (error) {
            setEmploymentForm(formError(error, 'Unable to update employment details.'));
            throw error;
        } finally {
            setOperation('');
        }
    }

    async function handleLinkExistingUser(input: EmployeeUserAccessLinkInput) {
        setUserAccessForm(emptyFormError);
        setOperation('user-access');

        try {
            await hrApi.userAccess.linkExisting(id, input);
            await loadEmployee();
        } catch (error) {
            setUserAccessForm(formError(error, 'Unable to link user access.'));
            throw error;
        } finally {
            setOperation('');
        }
    }

    async function handleInviteUser(input: EmployeeUserAccessCreateInput) {
        setUserAccessForm(emptyFormError);
        setOperation('user-access');

        try {
            await hrApi.userAccess.create(id, input);
            await loadEmployee();
        } catch (error) {
            setUserAccessForm(formError(error, 'Unable to invite user access.'));
            throw error;
        } finally {
            setOperation('');
        }
    }

    async function handleStatusChange(status: EmployeeStatus, reason?: string) {
        setOperation('status');

        try {
            const response = await hrApi.employees.changeStatus(id, status, reason);
            setEmployee(response.data);
            setStatusMessage(`Employee status changed to ${status.replace('_', ' ')}.`);
        } catch (error) {
            throw new Error(pageError(error, 'Unable to change employee status.'));
        } finally {
            setOperation('');
        }
    }

    async function validateAssignmentContext(context: string) {
        setStatusMessage('');
        setOperation(context);

        try {
            const response = await hrApi.employees.validateForAssignmentContext(id, context);
            const data = response.data as Record<string, unknown>;
            setStatusMessage(data.is_assignable === false ? `${context} validation: not assignable.` : `${context} validation: assignable.`);
        } catch (error) {
            setStatusMessage(pageError(error, `Unable to validate ${context}.`));
        } finally {
            setOperation('');
        }
    }

    if (isLoading || lookupsLoading) {
        return <EmptyState description="Loading employee profile, contacts, addresses, employment details, and user access..." title="Loading employee" />;
    }

    if (error || lookupError || !employee) {
        return <EmptyState description={error || lookupError || 'Employee was not found.'} title="Unable to load employee" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={(
                    <>
                        <Link to={`/hr/employees/${employee.id}/edit`}><Button>Edit</Button></Link>
                        <Button disabled={operation !== ''} onClick={() => void validateAssignmentContext('vehicle_service_technician')} variant="secondary">Validate Technician</Button>
                        <Button disabled={operation !== ''} onClick={() => void validateAssignmentContext('vehicle_rental_driver')} variant="secondary">Validate Driver</Button>
                    </>
                )}
                eyebrow="HR"
                subtitle="Employee profile, optional user access, and backend-owned employment context."
                title={employee.displayName || employee.fullName}
            />

            {statusMessage ? <div className="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700">{statusMessage}</div> : null}
            <Card className="p-5"><Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} /></Card>

            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                    <div className="space-y-5">
                        <EmployeeSummaryCard
                            departments={lookups.departments}
                            designations={lookups.designations}
                            employee={employee}
                            employmentTypes={lookups.employmentTypes}
                        />
                        <EmployeeOverviewPanels employee={employee} />
                    </div>
                    <EmployeeStatusActions employee={employee} isSaving={operation === 'status'} onChangeStatus={handleStatusChange} />
                </div>
            ) : null}

            {activeTab === 'contacts' ? (
                <EmployeeContactsPanel
                    errors={contactForm.errors}
                    globalError={contactForm.message}
                    isSaving={operation === 'contact'}
                    onCreate={handleContactCreate}
                    rows={employee.contacts}
                />
            ) : null}

            {activeTab === 'addresses' ? (
                <EmployeeAddressesPanel
                    errors={addressForm.errors}
                    globalError={addressForm.message}
                    isSaving={operation === 'address'}
                    onCreate={handleAddressCreate}
                    rows={employee.addresses}
                />
            ) : null}

            {activeTab === 'employment' ? (
                <EmploymentDetailsPanel
                    departments={lookups.departments}
                    designations={lookups.designations}
                    employee={employee}
                    employmentTypes={lookups.employmentTypes}
                    errors={employmentForm.errors}
                    globalError={employmentForm.message}
                    isSaving={operation === 'employment'}
                    onSubmit={handleEmploymentUpdate}
                />
            ) : null}

            {activeTab === 'user' ? (
                <EmployeeUserAccessPanel
                    errors={userAccessForm.errors}
                    globalError={userAccessForm.message}
                    isSaving={operation === 'user-access'}
                    onInviteUser={handleInviteUser}
                    onLinkExisting={handleLinkExistingUser}
                    rows={employee.userAccess}
                />
            ) : null}

            {activeTab === 'documents' ? <EmployeeDocumentsPanel rows={employee.documents} /> : null}
            {activeTab === 'salary' ? <EmployeeSalaryProfilePanel profile={employee.salaryProfile} /> : null}
            {activeTab === 'audit' ? <EmployeeActivityPanel rows={employee.audit} /> : null}

            <PreviewPanel
                rows={[
                    { label: 'VehicleService', value: 'Uses HR assignment validation endpoint' },
                    { label: 'VehicleRental', value: 'Uses HR assignment validation endpoint' },
                    { label: 'Frontend calculations', value: 'None for payroll, leave, attendance, or finance' },
                ]}
                status="HR boundary"
                title="Module Boundary"
            />
        </div>
    );
}
