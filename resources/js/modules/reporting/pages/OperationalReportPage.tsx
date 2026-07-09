import { useEffect, useState, type FormEvent } from 'react';
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

const OPERATIONAL_STATUSES = [
    ['draft', 'Draft'],
    ['inspected', 'Inspected'],
    ['in_progress', 'In progress'],
    ['completed', 'Completed'],
    ['cancelled', 'Cancelled'],
] as const;

const BILLING_STATUSES = [
    ['unbilled', 'Unbilled'],
    ['partially_billed', 'Partially billed'],
    ['billed', 'Billed'],
] as const;

const SERVICE_PAYMENT_STATUSES = [
    ['unpaid', 'Unpaid'],
    ['partially_paid', 'Partially paid'],
    ['paid', 'Paid'],
] as const;

const LINE_SOURCES = [
    ['inventory_item', 'Inventory item'],
    ['external_item', 'External item'],
    ['service_item', 'Service item'],
    ['labour_item', 'Labour item'],
    ['combo_parent', 'Combo parent'],
    ['combo_child', 'Combo child'],
] as const;

const INITIAL_OPERATIONAL_REPORT_PARAMS: OperationalReportParams = { page: 1, per_page: 25 };

export default function OperationalReportPage(props: OperationalReportPageProps) {
    return <OperationalReportContent key={props.reportKey} {...props} />;
}

function OperationalReportContent({ reportKey, kind }: OperationalReportPageProps) {
    const initialParams = INITIAL_OPERATIONAL_REPORT_PARAMS;
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
        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setLoading(true);
            setError(null);
        });
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
                            label="Operational status"
                            value={draft.operational_status ?? ''}
                            options={[{ value: '', label: 'All statuses' }, ...OPERATIONAL_STATUSES.map(([value, label]) => ({ value, label }))]}
                            onChange={(event) => setDraft((current) => ({ ...current, operational_status: event.target.value || undefined }))}
                        />}
                        {kind !== 'purchase' && <Select
                            label="Billing status"
                            value={draft.billing_status ?? ''}
                            options={[{ value: '', label: 'All billing states' }, ...BILLING_STATUSES.map(([value, label]) => ({ value, label }))]}
                            onChange={(event) => setDraft((current) => ({ ...current, billing_status: event.target.value || undefined }))}
                        />}
                        {kind !== 'purchase' && <Select
                            label="Service payment status"
                            value={draft.payment_status ?? ''}
                            options={[{ value: '', label: 'All service payment states' }, ...SERVICE_PAYMENT_STATUSES.map(([value, label]) => ({ value, label }))]}
                            onChange={(event) => setDraft((current) => ({ ...current, payment_status: event.target.value || undefined }))}
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
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {Object.entries(summary).map(([key, value]) => (
                <Panel key={key} className="rounded-lg">
                    <p className="text-xs uppercase tracking-wide text-slate-500">{key.replaceAll('_', ' ')}</p>
                    <p className="mt-2 text-2xl font-semibold text-slate-900">{value}</p>
                </Panel>
            ))}
        </div>
    );
}

function titleFor(kind: OperationalReportKind): string {
    if (kind === 'purchase') return 'Detailed Purchase Report';
    if (kind === 'employee-incentive') return 'Employee Incentive Report';
    return 'Detailed Vehicle Service Report';
}

function descriptionFor(kind: OperationalReportKind): string {
    if (kind === 'purchase') return 'Purchase orders, items, suppliers, received quantities, and invoice/payment progress.';
    if (kind === 'employee-incentive') return 'Technician and supervisor incentives for completed Vehicle Service work.';
    return 'Vehicle Service job lines, materials, labour, external work and invoice/payment progress.';
}
