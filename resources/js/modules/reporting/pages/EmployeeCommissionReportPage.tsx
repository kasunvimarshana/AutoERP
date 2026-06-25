import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { searchDepartments, searchDesignations, searchEmployees } from '@/modules/hr/hrApi';
import type { HrDepartment, HrDesignation } from '@/modules/hr/hrTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { mapLookupResult } from '@/shared/api/lookupRequest';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Drawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { humanize } from '@/shared/utils/object';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { ExportActions } from '../components/ExportActions';
import { runEmployeeCommissionReport } from '../reportingApi';
import type {
    EmployeeCommissionGroup,
    EmployeeCommissionReportParams,
    EmployeeCommissionReportResult,
    EmployeeCommissionReportRow,
} from '../reportingTypes';

const reportKey = 'vehicle-service/employee-commissions';

interface EmployeeLookupOption extends NamedResource {
    employee_number: string;
    display_name: string;
}

const jobStatuses = ['draft', 'inspected', 'in_progress', 'completed', 'invoiced', 'partially_paid', 'paid', 'cancelled'];
const invoiceStatuses = ['draft', 'approved', 'posted', 'partially_paid', 'paid', 'cancelled', 'void'];
const paymentStatuses = ['draft', 'pending_approval', 'approved', 'posted', 'partially_allocated', 'fully_allocated', 'refunded', 'allocated', 'void', 'reversed', 'cancelled'];
const commissionTypes = ['none', 'fixed', 'percentage'];
const commissionSources = ['technician', 'supervisor'];
const commissionStatuses = ['pending', 'earned', 'cancelled'];
const roleTypes = ['technician', 'helper', 'inspector', 'custom', 'supervisor'];
const enumOptions = (values: string[]) => values.map((value) => ({ value, label: humanize(value) }));

const columns: Array<{ key: string; label: string; sort?: string; numeric?: boolean }> = [
    { key: 'employee', label: 'Employee', sort: 'employee' },
    { key: 'department', label: 'Department', sort: 'department' },
    { key: 'designation', label: 'Designation', sort: 'designation' },
    { key: 'commission_source', label: 'Source', sort: 'commission_source' },
    { key: 'role_type', label: 'Role', sort: 'role_type' },
    { key: 'job', label: 'Job', sort: 'job_number' },
    { key: 'job_date', label: 'Date', sort: 'job_date' },
    { key: 'customer', label: 'Customer', sort: 'customer' },
    { key: 'vehicle', label: 'Vehicle', sort: 'vehicle' },
    { key: 'assigned_hours', label: 'Hours', sort: 'assigned_hours', numeric: true },
    { key: 'rate', label: 'Rate', sort: 'rate', numeric: true },
    { key: 'labour_amount', label: 'Labour value', sort: 'labour_amount', numeric: true },
    { key: 'commission_base', label: 'Commission base', sort: 'commission_base', numeric: true },
    { key: 'commission_type', label: 'Type', sort: 'commission_type' },
    { key: 'commission_value', label: 'Value', sort: 'commission_value', numeric: true },
    { key: 'commission_amount', label: 'Commission', sort: 'commission_amount', numeric: true },
    { key: 'commission_status', label: 'Commission status', sort: 'commission_status' },
    { key: 'invoice_progress', label: 'Invoice progress' },
    { key: 'payment_progress', label: 'Payment progress' },
    { key: 'job_status', label: 'Job status', sort: 'job_status' },
];

const initialFilters: EmployeeCommissionReportParams = {
    page: 1,
    per_page: 25,
    group_by: 'employee',
    sort: 'job_date',
    direction: 'desc',
};

export default function EmployeeCommissionReportPage() {
    const { organizationUnit } = useAuth();
    const [filters, setFilters] = useState<EmployeeCommissionReportParams>(initialFilters);
    const [draft, setDraft] = useState<EmployeeCommissionReportParams>(initialFilters);
    const [result, setResult] = useState<EmployeeCommissionReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [employee, setEmployee] = useState<EmployeeLookupOption | null>(null);
    const [department, setDepartment] = useState<HrDepartment | null>(null);
    const [designation, setDesignation] = useState<HrDesignation | null>(null);
    const [supervisor, setSupervisor] = useState<EmployeeLookupOption | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [selected, setSelected] = useState<EmployeeCommissionReportRow | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        const load = async () => {
            setLoading(true);
            setError(null);
            try {
                const response = await runEmployeeCommissionReport(cleanParams(filters), controller.signal);
                if (!controller.signal.aborted) setResult(response);
            } catch (requestError) {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        };
        void load();
        return () => controller.abort();
    }, [filters]);

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
    const groupMap = useMemo(
        () => new Map((result?.groups ?? []).map((group) => [group.key, group])),
        [result?.groups],
    );
    const exportParams = useMemo(() => cleanParams(filters), [filters]);

    const updateDraft = (patch: Partial<EmployeeCommissionReportParams>) => {
        setDraft((current) => ({ ...current, ...patch }));
    };

    const apply = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setFilters({ ...draft, page: 1 });
    };

    const reset = () => {
        setDraft(initialFilters);
        setFilters(initialFilters);
        setEmployee(null);
        setDepartment(null);
        setDesignation(null);
        setSupervisor(null);
        setCustomer(null);
        setVehicle(null);
    };

    const sort = (key: string) => {
        const direction = filters.sort === key && filters.direction !== 'asc' ? 'asc' : 'desc';
        setFilters((current) => ({ ...current, page: 1, sort: key, direction }));
        setDraft((current) => ({ ...current, sort: key, direction }));
    };

    const focusEmployee = (row: EmployeeCommissionReportRow) => {
        const focused: EmployeeCommissionReportParams = {
            ...filters,
            page: 1,
            employee_id: row.employee?.id ?? null,
            group_by: 'employee',
        };
        setFilters(focused);
        setDraft(focused);
        setEmployee(row.employee ? employeeLookupValue(row) : null);
        setSelected(null);
    };

    return (
        <>
            <ContentHeader
                title="Employee Commission Report"
                description="Technician and supervisor commissions from Vehicle Service jobs, with earned/pending and invoice/payment progress."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} title="Could not load employee commission report" />

            <div className="space-y-5">
                <Panel>
                    <form className="space-y-4" onSubmit={apply}>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Input label="Search" value={draft.search ?? ''} onChange={(event) => updateDraft({ search: event.target.value })} placeholder="Employee, job, customer, vehicle..." />
                            <Input label="From" type="date" value={draft.date_from ?? ''} onChange={(event) => updateDraft({ date_from: event.target.value })} />
                            <Input label="To" type="date" value={draft.date_to ?? ''} onChange={(event) => updateDraft({ date_to: event.target.value })} />
                            <Select
                                label="Group by"
                                value={draft.group_by ?? 'employee'}
                                options={[
                                    { value: 'employee', label: 'Employee' },
                                    { value: 'department', label: 'Department' },
                                    { value: 'designation', label: 'Designation' },
                                    { value: 'supervisor', label: 'Supervisor' },
                                    { value: 'commission_source', label: 'Commission source' },
                                ]}
                                onChange={(event) => updateDraft({ group_by: event.target.value as EmployeeCommissionReportParams['group_by'] })}
                            />
                            <GenericLookupSelect<EmployeeLookupOption>
                                label="Employee"
                                value={employee}
                                onChange={(value) => {
                                    setEmployee(value);
                                    updateDraft({ employee_id: value?.id ?? null });
                                }}
                                search={employeeSearch}
                                formatLabel={(value) => `${value.employee_number} ${value.display_name}`}
                            />
                            <GenericLookupSelect<HrDepartment>
                                label="Department"
                                value={department}
                                onChange={(value) => {
                                    setDepartment(value);
                                    updateDraft({ department_id: value?.id ?? null });
                                }}
                                search={searchDepartments}
                                formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()}
                                loadOnOpen
                                minSearchLength={0}
                            />
                            <GenericLookupSelect<HrDesignation>
                                label="Designation"
                                value={designation}
                                onChange={(value) => {
                                    setDesignation(value);
                                    updateDraft({ designation_id: value?.id ?? null });
                                }}
                                search={searchDesignations}
                                formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()}
                                loadOnOpen
                                minSearchLength={0}
                            />
                            <GenericLookupSelect<EmployeeLookupOption>
                                label="Supervisor"
                                value={supervisor}
                                onChange={(value) => {
                                    setSupervisor(value);
                                    updateDraft({ supervisor_id: value?.id ?? null });
                                }}
                                search={employeeSearch}
                                formatLabel={(value) => `${value.employee_number} ${value.display_name}`}
                            />
                            <CustomerLookupSelect value={customer} onChange={(value) => {
                                setCustomer(value);
                                updateDraft({ customer_id: value?.id ?? null });
                            }} />
                            <VehicleLookupSelect value={vehicle} onChange={(value) => {
                                setVehicle(value);
                                updateDraft({ vehicle_id: value?.id ?? null });
                            }} />
                            <Select label="Job status" value={draft.job_status ?? ''} options={enumOptions(jobStatuses)} onChange={(event) => updateDraft({ job_status: event.target.value })} />
                            <Select label="Invoice status" value={draft.invoice_status ?? ''} options={enumOptions(invoiceStatuses)} onChange={(event) => updateDraft({ invoice_status: event.target.value })} />
                            <Select label="Payment status" value={draft.payment_status ?? ''} options={enumOptions(paymentStatuses)} onChange={(event) => updateDraft({ payment_status: event.target.value })} />
                            <Select label="Commission source" value={draft.commission_source ?? ''} options={enumOptions(commissionSources)} onChange={(event) => updateDraft({ commission_source: event.target.value as EmployeeCommissionReportParams['commission_source'] })} />
                            <Select label="Role" value={draft.role_type ?? ''} options={enumOptions(roleTypes)} onChange={(event) => updateDraft({ role_type: event.target.value })} />
                            <Select label="Commission type" value={draft.commission_type ?? ''} options={enumOptions(commissionTypes)} onChange={(event) => updateDraft({ commission_type: event.target.value })} />
                            <Select label="Commission status" value={draft.commission_status ?? ''} options={enumOptions(commissionStatuses)} onChange={(event) => updateDraft({ commission_status: event.target.value as EmployeeCommissionReportParams['commission_status'] })} />
                            <Select label="Include cancelled" value={draft.include_cancelled ? '1' : '0'} options={[{ value: '0', label: 'No' }, { value: '1', label: 'Yes' }]} onChange={(event) => updateDraft({ include_cancelled: event.target.value === '1' })} />
                            <Input label="Organization unit" value={organizationUnit?.name ?? (organizationUnit?.id ? `Organization ${organizationUnit.id}` : 'Global')} disabled />
                            <Select
                                label="Rows per page"
                                value={String(draft.per_page ?? 25)}
                                options={[10, 25, 50, 100].map((value) => ({ value: String(value), label: String(value) }))}
                                onChange={(event) => updateDraft({ per_page: Number(event.target.value) })}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                            <Button type="submit" loading={loading}>Apply filters</Button>
                        </div>
                    </form>
                </Panel>

                {result && <SummaryCards result={result} />}

                <div className="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div className="text-sm text-slate-500">
                        {loading ? 'Refreshing...' : `${result?.meta?.total ?? 0} commission entries`}
                    </div>
                    <ExportActions reportKey={reportKey} params={exportParams} />
                </div>

                {loading && !result
                    ? <LoadingState label="Loading employee commissions..." />
                    : <GroupedTable
                        rows={result?.data ?? []}
                        groups={groupMap}
                        sortKey={filters.sort}
                        direction={filters.direction}
                        onSort={sort}
                        onEmployee={setSelected}
                    />}

                <Pagination meta={result?.meta} onPageChange={(page) => setFilters((current) => ({ ...current, page }))} />
            </div>

            <EmployeeDrillDown
                row={selected}
                group={selected && filters.group_by === 'employee' ? groupMap.get(String(selected.employee?.id)) : undefined}
                onClose={() => setSelected(null)}
                onFocus={focusEmployee}
            />
        </>
    );
}

function SummaryCards({ result }: { result: EmployeeCommissionReportResult }) {
    const summary = result.summary;
    const cards = [
        ['Entries', String(summary.total_entries)],
        ['Employees', String(summary.total_employees)],
        ['Jobs', String(summary.total_jobs)],
        ['Hours', formatQuantity(summary.total_hours)],
        ['Commission base', formatMoney(summary.total_commission_base)],
        ['Technician commission', formatMoney(summary.technician_commission)],
        ['Supervisor commission', formatMoney(summary.supervisor_commission)],
        ['Total commission', formatMoney(summary.total_commission)],
        ['Earned', formatMoney(summary.earned_commission)],
        ['Pending', formatMoney(summary.pending_commission)],
        ['Cancelled (excluded)', formatMoney(summary.cancelled_commission)],
    ];

    return (
        <div className="space-y-3">
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {cards.map(([label, value]) => (
                    <div key={label} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="text-xs font-semibold uppercase text-slate-500">{label}</div>
                        <div className="mt-2 text-lg font-bold text-slate-900">{value}</div>
                    </div>
                ))}
            </div>
            <div className="grid gap-3 md:grid-cols-2">
                <RankingCard title="Top earning employee" ranking={result.rankings.top_earning_employee} value={result.rankings.top_earning_employee?.labour_value} />
                <RankingCard title="Top commission employee" ranking={result.rankings.top_commission_employee} value={result.rankings.top_commission_employee?.commission_amount} />
            </div>
        </div>
    );
}

function RankingCard({ title, ranking, value }: {
    title: string;
    ranking: EmployeeCommissionReportResult['rankings']['top_earning_employee'];
    value?: string;
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div className="text-xs font-semibold uppercase text-slate-500">{title}</div>
            <div className="mt-2 font-semibold text-slate-900">{ranking?.employee?.name ?? 'No data'}</div>
            <div className="text-sm text-slate-600">{ranking ? formatMoney(value) : '-'}</div>
        </div>
    );
}

function GroupedTable({ rows, groups, sortKey, direction, onSort, onEmployee }: {
    rows: EmployeeCommissionReportRow[];
    groups: Map<string, EmployeeCommissionGroup>;
    sortKey?: string;
    direction?: 'asc' | 'desc';
    onSort: (key: string) => void;
    onEmployee: (row: EmployeeCommissionReportRow) => void;
}) {
    if (rows.length === 0) {
        return <div className="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">No employee commissions found.</div>;
    }

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="min-w-[2250px] divide-y divide-slate-200 text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key} className={`whitespace-nowrap px-3 py-3 font-semibold ${column.numeric ? 'text-right' : ''}`}>
                                    {column.sort
                                        ? <button type="button" className="font-semibold hover:text-slate-900" onClick={() => onSort(column.sort!)}>
                                            {column.label}{sortKey === column.sort ? ` ${direction === 'asc' ? 'Asc' : 'Desc'}` : ''}
                                        </button>
                                        : column.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((row, index) => {
                            const showGroup = index === 0 || rows[index - 1].group_key !== row.group_key;
                            const group = groups.get(row.group_key);
                            return [
                                showGroup && (
                                    <tr key={`group-${row.group_key}-${row.id}`} className="bg-sky-50">
                                        <td colSpan={columns.length} className="px-3 py-2 font-semibold text-sky-950">
                                            {row.group_label}
                                            {group && <span className="ml-3 font-normal text-sky-800">
                                                {group.total_jobs} jobs / {formatQuantity(group.total_hours)} hours / {formatMoney(group.total_commission)} commission
                                            </span>}
                                        </td>
                                    </tr>
                                ),
                                <tr key={row.id} className="hover:bg-slate-50/70">
                                    <td className="whitespace-nowrap px-3 py-3">
                                        <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => onEmployee(row)}>
                                            {row.employee_code} {row.employee_name}
                                        </button>
                                    </td>
                                    <td className="whitespace-nowrap px-3 py-3">{row.department_name || '-'}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{row.designation_name || '-'}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.commission_source)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.role_type)}</td>
                                    <td className="whitespace-nowrap px-3 py-3"><Link className="text-sky-700 hover:underline" to={`/vehicle-service/jobs/${row.job.id}`}>{row.job_number}</Link></td>
                                    <td className="whitespace-nowrap px-3 py-3">{formatDate(row.job_date)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{row.customer_name || '-'}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{row.vehicle_label || '-'}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right">{formatQuantity(row.assigned_hours)}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.rate)}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.labour_amount)}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.commission_base)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.commission_type)}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right">{formatCommissionValue(row)}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right font-semibold">{formatMoney(row.commission_amount)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.commission_status)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.invoice_progress)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.payment_progress)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.job_status)}</td>
                                </tr>,
                            ];
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function EmployeeDrillDown({ row, group, onClose, onFocus }: {
    row: EmployeeCommissionReportRow | null;
    group?: EmployeeCommissionGroup;
    onClose: () => void;
    onFocus: (row: EmployeeCommissionReportRow) => void;
}) {
    return (
        <Drawer open={row !== null} title={row?.employee_name ?? 'Employee'} onClose={onClose}>
            {row && (
                <div className="space-y-5">
                    <Panel title="Employee">
                        <dl className="grid gap-3 sm:grid-cols-2">
                            <Detail label="Code" value={row.employee_code} />
                            <Detail label="Department" value={row.department_name} />
                            <Detail label="Designation" value={row.designation_name} />
                            <Detail label="Role" value={humanize(row.role_type)} />
                        </dl>
                        <div className="mt-4 flex gap-2">
                            <LinkButton to={`/hr/employees/${row.employee?.id}`} variant="secondary">Employee record</LinkButton>
                            <Button type="button" onClick={() => onFocus(row)}>Focus report</Button>
                        </div>
                    </Panel>
                    {group && (
                        <Panel title="Filtered totals">
                            <dl className="grid gap-3 sm:grid-cols-2">
                                <Detail label="Jobs" value={String(group.total_jobs)} />
                                <Detail label="Hours" value={formatQuantity(group.total_hours)} />
                                <Detail label="Labour value" value={formatMoney(group.total_labour_value)} />
                                <Detail label="Commission" value={formatMoney(group.total_commission)} />
                            </dl>
                        </Panel>
                    )}
                    <Panel title="Selected commission">
                        <dl className="grid gap-3 sm:grid-cols-2">
                            <Detail label="Job" value={row.job_number} />
                            <Detail label="Date" value={formatDate(row.job_date)} />
                            <Detail label="Source" value={humanize(row.commission_source)} />
                            <Detail label="Commission status" value={humanize(row.commission_status)} />
                            <Detail label="Labour value" value={formatMoney(row.labour_amount)} />
                            <Detail label="Commission base" value={formatMoney(row.commission_base)} />
                            <Detail label="Commission" value={formatMoney(row.commission_amount)} />
                            <Detail label="Invoice progress" value={humanize(row.invoice_progress)} />
                            <Detail label="Payment progress" value={humanize(row.payment_progress)} />
                            <Detail label="Balance due" value={formatMoney(row.balance_due)} />
                        </dl>
                    </Panel>
                </div>
            )}
        </Drawer>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-semibold uppercase text-slate-500">{label}</dt><dd className="mt-1 text-slate-900">{value || '-'}</dd></div>;
}


function formatCommissionValue(row: EmployeeCommissionReportRow): string {
    if (row.commission_type === 'percentage') return `${formatQuantity(row.commission_value, 6)}%`;
    if (row.commission_type === 'fixed') return formatMoney(row.commission_value);
    return '-';
}

function employeeLookupValue(row: EmployeeCommissionReportRow): EmployeeLookupOption {
    return {
        id: Number(row.employee?.id),
        employee_number: row.employee_code,
        code: row.employee_code,
        name: row.employee_name,
        display_name: row.employee_name,
    };
}

function cleanParams(params: EmployeeCommissionReportParams): EmployeeCommissionReportParams {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    ) as EmployeeCommissionReportParams;
}
