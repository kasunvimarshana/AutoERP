import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { ExportActions } from '../components/ExportActions';
import { runTechnicianWorkReport } from '../reportingApi';
import type { TechnicianWorkReportParams, TechnicianWorkReportResult, TechnicianWorkReportRow } from '../reportingTypes';

const reportKey = 'vehicle-service/technician-work';

const jobStatusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'inspected', label: 'Inspected' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'invoiced', label: 'Invoiced' },
    { value: 'partially_paid', label: 'Partially paid' },
    { value: 'paid', label: 'Paid' },
    { value: 'cancelled', label: 'Cancelled' },
];

const commissionTypeOptions = [
    { value: 'none', label: 'None' },
    { value: 'fixed', label: 'Fixed' },
    { value: 'percentage', label: 'Percentage' },
];

const invoiceStatusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'approved', label: 'Approved' },
    { value: 'posted', label: 'Posted' },
    { value: 'partially_paid', label: 'Partially paid' },
    { value: 'paid', label: 'Paid' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'void', label: 'Void' },
];

const paymentStatusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'approved', label: 'Approved' },
    { value: 'posted', label: 'Posted' },
    { value: 'partially_allocated', label: 'Partially allocated' },
    { value: 'allocated', label: 'Allocated' },
    { value: 'void', label: 'Void' },
    { value: 'reversed', label: 'Reversed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const roleTypeOptions = [
    { value: 'technician', label: 'Technician' },
    { value: 'helper', label: 'Helper' },
    { value: 'supervisor', label: 'Supervisor' },
    { value: 'advisor', label: 'Advisor' },
];

const sortableColumns: Array<{ key: keyof TechnicianWorkReportRow | 'actions'; label: string; sort?: string; className?: string }> = [
    { key: 'job_number', label: 'Job number', sort: 'job_number' },
    { key: 'job_date', label: 'Job date', sort: 'job_date' },
    { key: 'job_status', label: 'Job status', sort: 'job_status' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'vehicle_label', label: 'Vehicle' },
    { key: 'line_description', label: 'Line description', sort: 'line_description' },
    { key: 'line_source_type', label: 'Source', sort: 'line_source_type' },
    { key: 'employee_name', label: 'Technician' },
    { key: 'role_type', label: 'Role', sort: 'role_type' },
    { key: 'assigned_hours', label: 'Hours', sort: 'assigned_hours', className: 'text-right' },
    { key: 'rate', label: 'Rate', sort: 'rate', className: 'text-right' },
    { key: 'labour_amount', label: 'Labour amount', className: 'text-right' },
    { key: 'line_total', label: 'Line total', sort: 'line_total', className: 'text-right' },
    { key: 'commission_type', label: 'Commission', sort: 'commission_type' },
    { key: 'commission_value', label: 'Value', sort: 'commission_value', className: 'text-right' },
    { key: 'commission_amount', label: 'Tech commission', sort: 'commission_amount', className: 'text-right' },
    { key: 'supervisor_name', label: 'Supervisor' },
    { key: 'supervisor_commission_amount', label: 'Supervisor commission', sort: 'supervisor_commission_amount', className: 'text-right' },
    { key: 'invoice_status', label: 'Invoice status' },
    { key: 'payment_status', label: 'Payment status' },
];

export default function TechnicianWorkReportPage() {
    const [filters, setFilters] = useState<TechnicianWorkReportParams>({ page: 1, per_page: 25, sort: 'job_date', direction: 'desc' });
    const [draft, setDraft] = useState<TechnicianWorkReportParams>(filters);
    const [result, setResult] = useState<TechnicianWorkReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [employee, setEmployee] = useState<EmployeeSummary | null>(null);
    const [supervisor, setSupervisor] = useState<EmployeeSummary | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError(null);
        runTechnicianWorkReport(cleanParams(filters), controller.signal)
            .then((data) => {
                if (!controller.signal.aborted) setResult(data);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [filters]);

    const exportParams = useMemo(() => cleanParams(filters), [filters]);
    const employeeSearch = useCallback((query: string, signal: AbortSignal) => searchEmployees(query, signal), []);

    const updateDraft = (patch: Partial<TechnicianWorkReportParams>) => {
        setDraft((current) => ({ ...current, ...patch }));
    };

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setFilters((current) => ({ ...current, ...draft, page: 1 }));
    };

    const resetFilters = () => {
        const next: TechnicianWorkReportParams = { page: 1, per_page: 25, sort: 'job_date', direction: 'desc' };
        setDraft(next);
        setFilters(next);
        setEmployee(null);
        setSupervisor(null);
        setCustomer(null);
        setVehicle(null);
    };

    const sort = (column: string) => {
        setFilters((current) => ({
            ...current,
            page: 1,
            sort: column,
            direction: current.sort === column && current.direction !== 'asc' ? 'asc' : 'desc',
        }));
        setDraft((current) => ({
            ...current,
            sort: column,
            direction: current.sort === column && current.direction !== 'asc' ? 'asc' : 'desc',
        }));
    };

    return (
        <>
            <ContentHeader
                title="Technician Work"
                description="Vehicle service labour assignments, commissions, supervisors, invoices and payments."
                actions={<Link to="/reports"><Button type="button" variant="secondary">All reports</Button></Link>}
            />
            <ErrorAlert error={error} title="Could not load technician work report" />
            <div className="space-y-5">
                <Panel>
                    <form className="grid gap-4" onSubmit={applyFilters}>
                        <div className="grid gap-4 lg:grid-cols-4">
                            <Input label="Search" value={draft.search ?? ''} onChange={(event) => updateDraft({ search: event.target.value })} placeholder="Job, line, customer, vehicle..." />
                            <Input label="From" type="date" value={draft.date_from ?? ''} onChange={(event) => updateDraft({ date_from: event.target.value })} />
                            <Input label="To" type="date" value={draft.date_to ?? ''} onChange={(event) => updateDraft({ date_to: event.target.value })} />
                            <Select label="Job status" value={draft.job_status ?? ''} options={jobStatusOptions} onChange={(event) => updateDraft({ job_status: event.target.value })} />
                            <GenericLookupSelect<EmployeeSummary>
                                label="Technician"
                                value={employee}
                                onChange={(value) => {
                                    setEmployee(value);
                                    updateDraft({ employee_id: value?.id ?? null });
                                }}
                                search={employeeSearch}
                                formatLabel={(value) => `${value.employee_number} ${value.display_name}`}
                            />
                            <GenericLookupSelect<EmployeeSummary>
                                label="Supervisor"
                                value={supervisor}
                                onChange={(value) => {
                                    setSupervisor(value);
                                    updateDraft({ supervisor_id: value?.id ?? null });
                                }}
                                search={employeeSearch}
                                formatLabel={(value) => `${value.employee_number} ${value.display_name}`}
                            />
                            <CustomerLookupSelect
                                value={customer}
                                onChange={(value) => {
                                    setCustomer(value);
                                    updateDraft({ customer_id: value?.id ?? null });
                                }}
                            />
                            <VehicleLookupSelect
                                value={vehicle}
                                onChange={(value) => {
                                    setVehicle(value);
                                    updateDraft({ vehicle_id: value?.id ?? null });
                                }}
                            />
                            <Select label="Role type" value={draft.role_type ?? ''} options={roleTypeOptions} onChange={(event) => updateDraft({ role_type: event.target.value })} />
                            <Select label="Commission type" value={draft.commission_type ?? ''} options={commissionTypeOptions} onChange={(event) => updateDraft({ commission_type: event.target.value })} />
                            <Select label="Invoice status" value={draft.invoice_status ?? ''} options={invoiceStatusOptions} onChange={(event) => updateDraft({ invoice_status: event.target.value })} />
                            <Select label="Payment status" value={draft.payment_status ?? ''} options={paymentStatusOptions} onChange={(event) => updateDraft({ payment_status: event.target.value })} />
                        </div>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="text-sm text-slate-500">Organization unit is scoped from the current session.</div>
                            <div className="flex gap-2">
                                <Button type="button" variant="secondary" onClick={resetFilters}>Reset</Button>
                                <Button type="submit" loading={loading}>Apply</Button>
                            </div>
                        </div>
                    </form>
                </Panel>

                {result && <SummaryCards summary={result.summary} />}

                <div className="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div className="text-sm text-slate-500">{loading ? 'Refreshing...' : `${result?.meta?.total ?? 0} assignments`}</div>
                    <ExportActions reportKey={reportKey} params={exportParams} />
                </div>

                {loading && !result ? <LoadingState label="Loading technician work..." /> : (
                    <>
                        <TechnicianWorkTable
                            rows={result?.data ?? []}
                            sortKey={filters.sort}
                            direction={filters.direction}
                            onSort={sort}
                        />
                        <Pagination meta={result?.meta} onPageChange={(page) => setFilters((current) => ({ ...current, page }))} />
                    </>
                )}
            </div>
        </>
    );
}

function SummaryCards({ summary }: { summary: TechnicianWorkReportResult['summary'] }) {
    const cards = [
        ['Assigned hours', summary.total_assigned_hours],
        ['Labour amount', summary.total_labour_amount],
        ['Technician commission', summary.total_technician_commission],
        ['Supervisor commission', summary.total_supervisor_commission],
        ['Payable commission', summary.total_payable_commission],
    ];

    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            {cards.map(([label, value]) => (
                <div key={label} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-semibold uppercase text-slate-500">{label}</div>
                    <div className="mt-2 text-lg font-bold text-slate-900">{value}</div>
                </div>
            ))}
        </div>
    );
}

function TechnicianWorkTable({ rows, sortKey, direction, onSort }: {
    rows: TechnicianWorkReportRow[];
    sortKey?: string;
    direction?: 'asc' | 'desc';
    onSort: (column: string) => void;
}) {
    if (rows.length === 0) {
        return <div className="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">No technician work found.</div>;
    }

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="min-w-[1900px] divide-y divide-slate-200 text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            {sortableColumns.map((column) => (
                                <th key={column.key} className={`whitespace-nowrap px-3 py-3 font-semibold ${column.className ?? ''}`}>
                                    {column.sort ? (
                                        <button type="button" className="inline-flex items-center gap-1 font-semibold hover:text-slate-900" onClick={() => column.sort && onSort(column.sort)}>
                                            {column.label}
                                            {sortKey === column.sort && <span>{direction === 'asc' ? 'Asc' : 'Desc'}</span>}
                                        </button>
                                    ) : column.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((row) => (
                            <tr key={row.id} className="hover:bg-slate-50/70">
                                {sortableColumns.map((column) => (
                                    <td key={column.key} className={`max-w-72 whitespace-nowrap px-3 py-3 text-slate-700 ${column.className ?? ''}`}>
                                        {cell(row, column.key)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function cell(row: TechnicianWorkReportRow, key: keyof TechnicianWorkReportRow | 'actions') {
    if (key === 'actions') return null;
    const value = row[key];
    if (typeof value === 'object') return value?.name ?? '-';
    if (value === null || value === '') return '-';
    return String(value).replaceAll('_', ' ');
}

function cleanParams(params: TechnicianWorkReportParams): TechnicianWorkReportParams {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    ) as TechnicianWorkReportParams;
}
