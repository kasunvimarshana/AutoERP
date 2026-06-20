import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Button, LinkButton } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary, HrDepartment } from '@/modules/hr/hrTypes';
import { HrDepartmentSelect } from '@/modules/hr/components/HrDepartmentSelect';
import { ItemLookupSelect, SupplierLookupSelect } from '@/modules/purchase/components/PurchaseLookups';
import { ExportActions } from '../components/ExportActions';
import { ReportDataGrid } from '../components/ReportDataGrid';
import { runOperationalReport } from '../reportingApi';
import type {
    OperationalReportKind,
    OperationalReportParams,
    OperationalReportResult,
} from '../reportingTypes';

interface OperationalReportPageProps {
    reportKey: 'purchase/detailed' | 'vehicle-service/detailed' | 'vehicle-service/employee-incentives';
    kind: OperationalReportKind;
}

const PURCHASE_STATUSES = [
    ['draft', 'Draft'],
    ['pending_approval', 'Pending approval'],
    ['approved', 'Approved'],
    ['closed', 'Closed'],
    ['cancelled', 'Cancelled'],
] as const;

const JOB_STATUSES = [
    ['draft', 'Draft'],
    ['inspected', 'Inspected'],
    ['in_progress', 'In progress'],
    ['completed', 'Completed'],
    ['invoiced', 'Invoiced'],
    ['partially_paid', 'Partially paid'],
    ['paid', 'Paid'],
    ['cancelled', 'Cancelled'],
] as const;

const LINE_SOURCES = [
    ['inventory_item', 'Inventory item'],
    ['external_item', 'External item'],
    ['service_item', 'Service item'],
    ['labour_item', 'Labour item'],
    ['combo_parent', 'Combo parent'],
    ['combo_child', 'Combo child'],
] as const;

export default function OperationalReportPage({ reportKey, kind }: OperationalReportPageProps) {
    const initialParams = useMemo<OperationalReportParams>(() => ({ page: 1, per_page: 25 }), [reportKey]);
    const [params, setParams] = useState<OperationalReportParams>(initialParams);
    const [draft, setDraft] = useState<OperationalReportParams>(initialParams);
    const [result, setResult] = useState<OperationalReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [item, setItem] = useState<NamedResource | null>(null);
    const [customer, setCustomer] = useState<NamedResource | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [employee, setEmployee] = useState<EmployeeSummary | null>(null);
    const [department, setDepartment] = useState<HrDepartment | null>(null);

    useEffect(() => {
        setParams(initialParams);
        setDraft(initialParams);
        setResult(null);
        setSupplier(null);
        setItem(null);
        setCustomer(null);
        setVehicle(null);
        setEmployee(null);
        setDepartment(null);
    }, [initialParams]);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError(null);
        runOperationalReport(reportKey, params, controller.signal)
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
    }, [params, reportKey]);

    const apply = (event?: FormEvent) => {
        event?.preventDefault();
        setParams({ ...draft, page: 1 });
    };

    const reset = () => {
        setSupplier(null);
        setItem(null);
        setCustomer(null);
        setVehicle(null);
        setEmployee(null);
        setDepartment(null);
        setDraft(initialParams);
        setParams(initialParams);
    };

    const sort = (column: string) => {
        setParams((current) => {
            const next: OperationalReportParams = {
                ...current,
                page: 1,
                sort: column,
                direction: current.sort === column && current.direction !== 'asc' ? 'asc' : 'desc',
            };
            setDraft(next);
            return next;
        });
    };

    if (loading && !result) return <LoadingState label="Loading report..." />;

    return (
        <>
            <ContentHeader
                title={result?.report.title ?? titleFor(kind)}
                description={result?.report.description ?? descriptionFor(kind)}
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} />
            <form onSubmit={apply}>
                <Panel title="Filters">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Input label="Search" value={draft.search ?? ''} onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value }))} />
                        <Input label="From" type="date" value={draft.date_from ?? ''} onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value || undefined }))} />
                        <Input label="To" type="date" value={draft.date_to ?? ''} onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value || undefined }))} />
                        {kind === 'purchase' && <Select
                            label="PO status"
                            value={draft.purchase_status ?? ''}
                            options={[{ value: '', label: 'All statuses' }, ...PURCHASE_STATUSES.map(([value, label]) => ({ value, label }))]}
                            onChange={(event) => setDraft((current) => ({ ...current, purchase_status: event.target.value || undefined }))}
                        />}
                        {kind !== 'purchase' && <Select
                            label="Job status"
                            value={draft.job_status ?? ''}
                            options={[{ value: '', label: 'All statuses' }, ...JOB_STATUSES.map(([value, label]) => ({ value, label }))]}
                            onChange={(event) => setDraft((current) => ({ ...current, job_status: event.target.value || undefined }))}
                        />}
                        {kind === 'purchase' && <SupplierLookupSelect
                            value={supplier}
                            onChange={(value) => {
                                setSupplier(value);
                                setDraft((current) => ({ ...current, supplier_id: value?.id ?? null }));
                            }}
                            loadOnOpen
                            minSearchLength={0}
                        />}
                        {kind === 'purchase' && <ItemLookupSelect
                            value={item}
                            onChange={(value) => {
                                setItem(value);
                                setDraft((current) => ({ ...current, item_id: value?.id ?? null }));
                            }}
                        />}
                        {kind !== 'purchase' && <LookupSelect
                            label="Customer"
                            value={customer}
                            onChange={(value) => {
                                setCustomer(value);
                                setDraft((current) => ({ ...current, customer_id: value?.id ?? null }));
                            }}
                            search={lookupApi.customers}
                            placeholder="Search customers..."
                        />}
                        {kind !== 'purchase' && <VehicleLookupSelect
                            kind="all"
                            value={vehicle}
                            onChange={(value) => {
                                setVehicle(value);
                                setDraft((current) => ({ ...current, vehicle_id: value?.id ?? null }));
                            }}
                        />}
                        {kind === 'vehicle-service' && <Select
                            label="Line source"
                            value={draft.line_source_type ?? ''}
                            options={[{ value: '', label: 'All sources' }, ...LINE_SOURCES.map(([value, label]) => ({ value, label }))]}
                            onChange={(event) => setDraft((current) => ({ ...current, line_source_type: event.target.value || undefined }))}
                        />}
                        {kind === 'vehicle-service' && <ItemLookupSelect
                            value={item}
                            onChange={(value) => {
                                setItem(value);
                                setDraft((current) => ({ ...current, item_id: value?.id ?? null }));
                            }}
                        />}
                        {kind === 'employee-incentive' && <Select
                            label="Incentive source"
                            value={draft.incentive_source ?? ''}
                            options={[
                                { value: '', label: 'Technician and supervisor' },
                                { value: 'technician', label: 'Technician' },
                                { value: 'supervisor', label: 'Supervisor' },
                            ]}
                            onChange={(event) => setDraft((current) => ({ ...current, incentive_source: event.target.value as OperationalReportParams['incentive_source'] }))}
                        />}
                        {kind === 'employee-incentive' && <GenericLookupSelect<EmployeeSummary>
                            label="Employee"
                            value={employee}
                            onChange={(value) => {
                                setEmployee(value);
                                setDraft((current) => ({ ...current, employee_id: value?.id ?? null }));
                            }}
                            search={(loadParams) => searchEmployees(loadParams)}
                            formatLabel={(value) => `${value.employee_number} ${value.display_name}`}
                        />}
                        {kind === 'employee-incentive' && <HrDepartmentSelect
                            value={department}
                            onChange={(value) => {
                                setDepartment(value);
                                setDraft((current) => ({ ...current, department_id: value?.id ?? null }));
                            }}
                        />}
                    </div>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button type="submit">Apply filters</Button>
                        <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                    </div>
                </Panel>
            </form>

            {result && <div className="mt-5 space-y-5">
                <SummaryCards summary={result.summary} />
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="text-sm text-slate-500">{loading ? 'Refreshing...' : `${result.meta?.total ?? 0} rows`}</div>
                    <ExportActions reportKey={reportKey} params={params} />
                </div>
                <ReportDataGrid
                    columns={result.report.columns}
                    rows={result.data}
                    sort={params.sort ?? result.report.default_sort}
                    direction={params.direction ?? result.report.default_direction}
                    onSort={sort}
                />
                <Pagination meta={result.meta} onPageChange={(page) => setParams((current) => ({ ...current, page }))} />
            </div>}
        </>
    );
}

function SummaryCards({ summary }: { summary: Record<string, string | number> }) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            {Object.entries(summary).map(([key, value]) => (
                <div key={key} className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label(key)}</div>
                    <div className="mt-1 break-words text-lg font-semibold tabular-nums text-slate-900">{formatSummary(value)}</div>
                </div>
            ))}
        </div>
    );
}

function label(value: string): string {
    return value.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function formatSummary(value: string | number): string {
    if (typeof value === 'number') return value.toLocaleString();
    const match = value.match(/^([+-]?)(\d+)(?:\.(\d+))?$/);
    if (!match) return value;
    const [, sign, integer, fraction = ''] = match;
    const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const trimmed = fraction.replace(/0+$/, '').padEnd(2, '0');
    return `${sign}${grouped}.${trimmed}`;
}

function titleFor(kind: OperationalReportKind): string {
    return {
        purchase: 'Detailed Purchase Report',
        'vehicle-service': 'Detailed Vehicle Service Report',
        'employee-incentive': 'Employee Incentive Report',
    }[kind];
}

function descriptionFor(kind: OperationalReportKind): string {
    return {
        purchase: 'Line-level purchase quantities, progress and financial values.',
        'vehicle-service': 'Line-level service revenue, recorded cost and financial progress.',
        'employee-incentive': 'Technician and supervisor incentives from Vehicle Service jobs.',
    }[kind];
}
