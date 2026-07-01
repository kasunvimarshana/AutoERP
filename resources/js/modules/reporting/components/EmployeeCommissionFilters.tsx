import { useCallback, useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { searchDepartments, searchDesignations, searchEmployees } from '@/modules/hr/hrApi';
import type { HrDepartment, HrDesignation } from '@/modules/hr/hrTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { mapLookupResult } from '@/shared/api/lookupRequest';
import { Button } from '@/shared/components/Button';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { humanize } from '@/shared/utils/object';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import type { EmployeeCommissionReportParams } from '../reportingTypes';
import { PaymentLifecycleFilters } from './PaymentLifecycleFilters';

interface EmployeeLookupOption extends NamedResource {
    employee_number: string;
    display_name: string;
}

const jobStatuses = ['draft', 'inspected', 'in_progress', 'completed', 'invoiced', 'partially_paid', 'paid', 'cancelled'];
const invoiceStatuses = ['draft', 'approved', 'posted', 'partially_paid', 'paid', 'cancelled', 'void'];
const commissionTypes = ['none', 'fixed', 'percentage'];
const commissionSources = ['technician', 'supervisor'];
const commissionStatuses = ['pending', 'earned', 'cancelled'];
const roleTypes = ['technician', 'helper', 'inspector', 'custom', 'supervisor'];
const enumOptions = (values: string[]) => values.map((value) => ({ value, label: humanize(value) }));

interface EmployeeCommissionFiltersProps {
    value: EmployeeCommissionReportParams;
    loading: boolean;
    onChange: (patch: Partial<EmployeeCommissionReportParams>) => void;
    onApply: (event: FormEvent<HTMLFormElement>) => void;
    onReset: () => void;
}

export function EmployeeCommissionFilters({
    value,
    loading,
    onChange,
    onApply,
    onReset,
}: EmployeeCommissionFiltersProps) {
    const { organizationUnit } = useAuth();
    const [employee, setEmployee] = useState<EmployeeLookupOption | null>(null);
    const [department, setDepartment] = useState<HrDepartment | null>(null);
    const [designation, setDesignation] = useState<HrDesignation | null>(null);
    const [supervisor, setSupervisor] = useState<EmployeeLookupOption | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);

    const employeeSearch = useCallback(async (params: LookupLoadParams) => {
        const result = await searchEmployees(params);
        return mapLookupResult(result, (row): EmployeeLookupOption => ({
            id: row.id,
            code: row.employee_number,
            name: row.display_name,
            employee_number: row.employee_number,
            display_name: row.display_name,
        }));
    }, []);

    return (
        <form className="space-y-4" onSubmit={onApply}>
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Input
                    label="Search"
                    value={value.search ?? ''}
                    onChange={(event) => onChange({ search: event.target.value })}
                    placeholder="Employee, job, customer or vehicle"
                />
                <Input label="From" type="date" value={value.date_from ?? ''} onChange={(event) => onChange({ date_from: event.target.value })} />
                <Input label="To" type="date" value={value.date_to ?? ''} onChange={(event) => onChange({ date_to: event.target.value })} />
                <Select
                    label="Group by"
                    value={value.group_by ?? 'employee'}
                    options={[
                        { value: 'employee', label: 'Employee' },
                        { value: 'department', label: 'Department' },
                        { value: 'designation', label: 'Designation' },
                        { value: 'supervisor', label: 'Supervisor' },
                        { value: 'commission_source', label: 'Commission source' },
                    ]}
                    onChange={(event) => onChange({ group_by: event.target.value as EmployeeCommissionReportParams['group_by'] })}
                />
                <GenericLookupSelect<EmployeeLookupOption>
                    label="Employee"
                    value={value.employee_id ? employee : null}
                    onChange={(selected) => {
                        setEmployee(selected);
                        onChange({ employee_id: selected?.id ?? null });
                    }}
                    search={employeeSearch}
                    formatLabel={(selected) => `${selected.employee_number} ${selected.display_name}`}
                />
                <GenericLookupSelect<HrDepartment>
                    label="Department"
                    value={value.department_id ? department : null}
                    onChange={(selected) => {
                        setDepartment(selected);
                        onChange({ department_id: selected?.id ?? null });
                    }}
                    search={searchDepartments}
                    formatLabel={(selected) => `${selected.code ?? ''} ${selected.name}`.trim()}
                    loadOnOpen
                    minSearchLength={0}
                />
                <GenericLookupSelect<HrDesignation>
                    label="Designation"
                    value={value.designation_id ? designation : null}
                    onChange={(selected) => {
                        setDesignation(selected);
                        onChange({ designation_id: selected?.id ?? null });
                    }}
                    search={searchDesignations}
                    formatLabel={(selected) => `${selected.code ?? ''} ${selected.name}`.trim()}
                    loadOnOpen
                    minSearchLength={0}
                />
                <GenericLookupSelect<EmployeeLookupOption>
                    label="Supervisor"
                    value={value.supervisor_id ? supervisor : null}
                    onChange={(selected) => {
                        setSupervisor(selected);
                        onChange({ supervisor_id: selected?.id ?? null });
                    }}
                    search={employeeSearch}
                    formatLabel={(selected) => `${selected.employee_number} ${selected.display_name}`}
                />
                <CustomerLookupSelect value={value.customer_id ? customer : null} onChange={(selected) => {
                    setCustomer(selected);
                    onChange({ customer_id: selected?.id ?? null });
                }} />
                <VehicleLookupSelect value={value.vehicle_id ? vehicle : null} onChange={(selected) => {
                    setVehicle(selected);
                    onChange({ vehicle_id: selected?.id ?? null });
                }} />
                <Select label="Job status" value={value.job_status ?? ''} options={enumOptions(jobStatuses)} onChange={(event) => onChange({ job_status: event.target.value })} />
                <Select label="Invoice status" value={value.invoice_status ?? ''} options={enumOptions(invoiceStatuses)} onChange={(event) => onChange({ invoice_status: event.target.value })} />
                <PaymentLifecycleFilters value={value} onChange={onChange} />
                <Select label="Commission source" value={value.commission_source ?? ''} options={enumOptions(commissionSources)} onChange={(event) => onChange({ commission_source: event.target.value as EmployeeCommissionReportParams['commission_source'] })} />
                <Select label="Role" value={value.role_type ?? ''} options={enumOptions(roleTypes)} onChange={(event) => onChange({ role_type: event.target.value })} />
                <Select label="Commission type" value={value.commission_type ?? ''} options={enumOptions(commissionTypes)} onChange={(event) => onChange({ commission_type: event.target.value })} />
                <Select label="Commission status" value={value.commission_status ?? ''} options={enumOptions(commissionStatuses)} onChange={(event) => onChange({ commission_status: event.target.value as EmployeeCommissionReportParams['commission_status'] })} />
                <Select label="Include cancelled" value={value.include_cancelled ? '1' : '0'} options={[{ value: '0', label: 'No' }, { value: '1', label: 'Yes' }]} onChange={(event) => onChange({ include_cancelled: event.target.value === '1' })} />
                <Input label="Organization unit" value={organizationUnit?.name ?? (organizationUnit?.id ? `Organization ${organizationUnit.id}` : 'Global')} disabled />
                <Select label="Rows per page" value={String(value.per_page ?? 25)} options={[10, 25, 50, 100].map((rowCount) => ({ value: String(rowCount), label: String(rowCount) }))} onChange={(event) => onChange({ per_page: Number(event.target.value) })} />
            </div>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onReset}>Reset</Button>
                <Button type="submit" loading={loading}>Apply filters</Button>
            </div>
        </form>
    );
}
