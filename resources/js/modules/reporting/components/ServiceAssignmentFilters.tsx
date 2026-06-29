import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { Button } from '@/shared/components/Button';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { LookupLoadParams } from '@/shared/types/lookup';
import type { TechnicianWorkReportParams } from '../reportingTypes';
import { PaymentLifecycleFilters } from './PaymentLifecycleFilters';

const jobStatuses = [
    ['draft', 'Draft'],
    ['inspected', 'Inspected'],
    ['in_progress', 'In progress'],
    ['completed', 'Completed'],
    ['invoiced', 'Invoiced'],
    ['partially_paid', 'Partially paid'],
    ['paid', 'Paid'],
    ['cancelled', 'Cancelled'],
];
const invoiceStatuses = [
    ['draft', 'Draft'],
    ['approved', 'Approved'],
    ['posted', 'Posted'],
    ['partially_paid', 'Partially paid'],
    ['paid', 'Paid'],
    ['cancelled', 'Cancelled'],
    ['void', 'Void'],
];
const roles = [
    ['technician', 'Technician'],
    ['helper', 'Helper'],
    ['inspector', 'Inspector'],
    ['custom', 'Custom'],
    ['supervisor', 'Supervisor'],
];
const commissionTypes = [
    ['none', 'None'],
    ['fixed', 'Fixed'],
    ['percentage', 'Percentage'],
];
const options = (values: string[][]) => values.map(([value, label]) => ({ value, label }));

interface ServiceAssignmentFiltersProps {
    value: TechnicianWorkReportParams;
    loading: boolean;
    resetToken: number;
    onChange: (patch: Partial<TechnicianWorkReportParams>) => void;
    onApply: (event: FormEvent<HTMLFormElement>) => void;
    onReset: () => void;
}

export function ServiceAssignmentFilters({
    value,
    loading,
    resetToken,
    onChange,
    onApply,
    onReset,
}: ServiceAssignmentFiltersProps) {
    const [employee, setEmployee] = useState<EmployeeSummary | null>(null);
    const [supervisor, setSupervisor] = useState<EmployeeSummary | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);

    useEffect(() => {
        setEmployee(null);
        setSupervisor(null);
        setCustomer(null);
        setVehicle(null);
    }, [resetToken]);

    const employeeSearch = useCallback(
        (params: LookupLoadParams) => searchEmployees(params),
        [],
    );

    return (
        <form className="grid gap-4" onSubmit={onApply}>
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Input label="Search" value={value.search ?? ''} onChange={(event) => onChange({ search: event.target.value })} placeholder="Job, line, customer or vehicle" />
                <Input label="From" type="date" value={value.date_from ?? ''} onChange={(event) => onChange({ date_from: event.target.value })} />
                <Input label="To" type="date" value={value.date_to ?? ''} onChange={(event) => onChange({ date_to: event.target.value })} />
                <Select label="Job status" value={value.job_status ?? ''} options={options(jobStatuses)} onChange={(event) => onChange({ job_status: event.target.value })} />
                <GenericLookupSelect<EmployeeSummary>
                    label="Technician"
                    value={employee}
                    onChange={(selected) => {
                        setEmployee(selected);
                        onChange({ employee_id: selected?.id ?? null });
                    }}
                    search={employeeSearch}
                    formatLabel={(selected) => `${selected.employee_number} ${selected.display_name}`}
                />
                <GenericLookupSelect<EmployeeSummary>
                    label="Supervisor"
                    value={supervisor}
                    onChange={(selected) => {
                        setSupervisor(selected);
                        onChange({ supervisor_id: selected?.id ?? null });
                    }}
                    search={employeeSearch}
                    formatLabel={(selected) => `${selected.employee_number} ${selected.display_name}`}
                />
                <CustomerLookupSelect value={customer} onChange={(selected) => {
                    setCustomer(selected);
                    onChange({ customer_id: selected?.id ?? null });
                }} />
                <VehicleLookupSelect value={vehicle} onChange={(selected) => {
                    setVehicle(selected);
                    onChange({ vehicle_id: selected?.id ?? null });
                }} />
                <Select label="Role type" value={value.role_type ?? ''} options={options(roles)} onChange={(event) => onChange({ role_type: event.target.value })} />
                <Select label="Commission type" value={value.commission_type ?? ''} options={options(commissionTypes)} onChange={(event) => onChange({ commission_type: event.target.value })} />
                <Select label="Invoice status" value={value.invoice_status ?? ''} options={options(invoiceStatuses)} onChange={(event) => onChange({ invoice_status: event.target.value })} />
                <PaymentLifecycleFilters value={value} onChange={onChange} />
                <Select label="Rows per page" value={String(value.per_page ?? 25)} options={[10, 25, 50, 100].map((count) => ({ value: String(count), label: String(count) }))} onChange={(event) => onChange({ per_page: Number(event.target.value) })} />
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="text-sm text-slate-500">Organization unit is scoped from the current session.</div>
                <div className="flex gap-2">
                    <Button type="button" variant="secondary" onClick={onReset}>Reset</Button>
                    <Button type="submit" loading={loading}>Apply</Button>
                </div>
            </div>
        </form>
    );
}
