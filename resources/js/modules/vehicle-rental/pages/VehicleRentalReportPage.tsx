import { useEffect, useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { ExportActions } from '@/modules/reporting/components/ExportActions';
import { ReportDataGrid } from '@/modules/reporting/components/ReportDataGrid';
import { runOperationalReport } from '@/modules/reporting/reportingApi';
import type { OperationalReportParams, OperationalReportResult } from '@/modules/reporting/reportingTypes';
import {
    RentalCustomerLookup,
    RentalDriverLookup,
    RentalSupplierLookup,
    RentalVehicleLookup,
    type RentalLookupOption,
} from '../components/VehicleRentalLookups';
import type { RentalReference } from '../vehicleRentalTypes';

export type VehicleRentalReportKind =
    | 'running-chart'
    | 'chart-exceptions'
    | 'customer-invoices'
    | 'owner-vouchers'
    | 'rental-history';

interface VehicleRentalReportPageProps {
    reportKey: string;
    kind: VehicleRentalReportKind;
}

const CHART_STATUSES = [
    { value: '', label: 'All chart statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'finalized', label: 'Finalized' },
    { value: 'reversed', label: 'Reversed' },
];

const ASSIGNMENT_STATUSES = [
    { value: '', label: 'All assignment statuses' },
    { value: 'planned', label: 'Planned' },
    { value: 'active', label: 'Active' },
    { value: 'returned', label: 'Returned' },
    { value: 'replaced', label: 'Replaced' },
    { value: 'cancelled', label: 'Cancelled' },
];

const INVOICE_STATUSES = [
    { value: '', label: 'Posted financial documents' },
    { value: 'posted', label: 'Posted' },
    { value: 'partially_paid', label: 'Partially paid' },
    { value: 'paid', label: 'Paid' },
    { value: 'reversed', label: 'Reversed' },
    { value: 'approved', label: 'Approved, not posted' },
    { value: 'draft', label: 'Draft' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'void', label: 'Void' },
];

const EXCEPTION_TYPES = [
    { value: '', label: 'All exceptions' },
    { value: 'missing_chart', label: 'Missing chart' },
    { value: 'duplicate_assignment_date', label: 'Duplicate assignment/date' },
    { value: 'duplicate_vehicle_date', label: 'Duplicate vehicle/date' },
];

export function VehicleRentalReportPage({ reportKey, kind }: VehicleRentalReportPageProps) {
    return <VehicleRentalReportContent key={reportKey} reportKey={reportKey} kind={kind} />;
}

function VehicleRentalReportContent({ reportKey, kind }: VehicleRentalReportPageProps) {
    const initialParams = initialReportParams(kind);
    const [params, setParams] = useState<OperationalReportParams>(initialParams);
    const [draft, setDraft] = useState<OperationalReportParams>(initialParams);
    const [result, setResult] = useState<OperationalReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [customer, setCustomer] = useState<RentalLookupOption | null>(null);
    const [supplier, setSupplier] = useState<RentalLookupOption | null>(null);
    const [vehicle, setVehicle] = useState<RentalLookupOption | null>(null);
    const [driver, setDriver] = useState<RentalLookupOption | null>(null);

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
        const initial = initialReportParams(kind);
        setCustomer(null);
        setSupplier(null);
        setVehicle(null);
        setDriver(null);
        setDraft(initial);
        setParams(initial);
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

    if (loading && !result) return <LoadingState label="Loading Vehicle Rental report..." />;

    return (
        <>
            <ContentHeader
                title={result?.report.title ?? titleFor(kind)}
                description={result?.report.description ?? descriptionFor(kind)}
                actions={<LinkButton to="/vehicle-rental/reports" variant="secondary">All rental reports</LinkButton>}
            />
            <ErrorAlert error={error} />
            <form onSubmit={apply}>
                <Panel title="Filters">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Input
                            label="Search"
                            value={draft.search ?? ''}
                            placeholder="Chart, agreement, party or vehicle"
                            onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value }))}
                        />
                        <Input
                            label="From"
                            type="date"
                            required={kind === 'chart-exceptions'}
                            value={draft.date_from ?? ''}
                            onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value || undefined }))}
                        />
                        <Input
                            label="To"
                            type="date"
                            required={kind === 'chart-exceptions'}
                            min={draft.date_from || undefined}
                            value={draft.date_to ?? ''}
                            onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value || undefined }))}
                        />
                        {kind === 'running-chart' && (
                            <Select
                                label="Chart status"
                                value={draft.chart_status ?? ''}
                                options={CHART_STATUSES}
                                onChange={(event) => setDraft((current) => ({ ...current, chart_status: event.target.value || undefined }))}
                            />
                        )}
                        {kind === 'rental-history' && (
                            <Select
                                label="Assignment status"
                                value={draft.assignment_status ?? ''}
                                options={ASSIGNMENT_STATUSES}
                                onChange={(event) => setDraft((current) => ({ ...current, assignment_status: event.target.value || undefined }))}
                            />
                        )}
                        {(kind === 'customer-invoices' || kind === 'owner-vouchers') && (
                            <Select
                                label="Document status"
                                value={draft.invoice_status ?? ''}
                                options={INVOICE_STATUSES}
                                onChange={(event) => setDraft((current) => ({ ...current, invoice_status: event.target.value || undefined }))}
                            />
                        )}
                        {kind === 'chart-exceptions' && (
                            <Select
                                label="Exception type"
                                value={draft.exception_type ?? ''}
                                options={EXCEPTION_TYPES}
                                onChange={(event) => setDraft((current) => ({ ...current, exception_type: event.target.value || undefined }))}
                            />
                        )}
                        {kind !== 'owner-vouchers' && (
                            <RentalCustomerLookup
                                value={customer as RentalReference | null}
                                onChange={(value) => {
                                    setCustomer(value);
                                    setDraft((current) => ({ ...current, customer_id: value?.id ?? null }));
                                }}
                            />
                        )}
                        {kind !== 'customer-invoices' && (
                            <RentalSupplierLookup
                                value={supplier as RentalReference | null}
                                onChange={(value) => {
                                    setSupplier(value);
                                    setDraft((current) => ({ ...current, supplier_id: value?.id ?? null }));
                                }}
                            />
                        )}
                        <RentalVehicleLookup
                            value={vehicle as RentalReference | null}
                            onChange={(value) => {
                                setVehicle(value);
                                setDraft((current) => ({ ...current, vehicle_id: value?.id ?? null }));
                            }}
                        />
                        {(kind === 'running-chart' || kind === 'rental-history') && (
                            <RentalDriverLookup
                                value={driver as RentalReference | null}
                                onChange={(value) => {
                                    setDriver(value);
                                    setDraft((current) => ({ ...current, driver_employee_id: value?.id ?? null }));
                                }}
                            />
                        )}
                    </div>
                    {kind === 'chart-exceptions' && (
                        <p className="mt-3 text-sm text-slate-600">
                            Missing-chart checks stop at today and support a maximum period of 366 calendar days.
                        </p>
                    )}
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button type="submit">Apply filters</Button>
                        <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                    </div>
                </Panel>
            </form>

            {result && (
                <div className="mt-5 space-y-5">
                    <SummaryCards summary={result.summary} />
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="text-sm text-slate-500">
                            {loading ? 'Refreshing...' : `${result.meta?.total ?? 0} rows`}
                        </div>
                        <ExportActions reportKey={reportKey} params={params} />
                    </div>
                    <ReportDataGrid
                        columns={result.report.columns}
                        rows={result.data}
                        sort={params.sort ?? result.report.default_sort}
                        direction={params.direction ?? result.report.default_direction}
                        onSort={sort}
                    />
                    <Pagination
                        meta={result.meta}
                        onPageChange={(page) => setParams((current) => ({ ...current, page }))}
                    />
                </div>
            )}
        </>
    );
}

function initialReportParams(kind: VehicleRentalReportKind): OperationalReportParams {
    if (kind !== 'chart-exceptions') return { page: 1, per_page: 25 };
    const today = businessDateInputValue();

    return {
        page: 1,
        per_page: 25,
        date_from: `${today.slice(0, 7)}-01`,
        date_to: today,
        direction: 'asc',
    };
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

function titleFor(kind: VehicleRentalReportKind): string {
    return {
        'running-chart': 'Daily Running Chart Report',
        'chart-exceptions': 'Missing / Duplicate Running Chart Exceptions',
        'customer-invoices': 'Customer Invoice Register',
        'owner-vouchers': 'Owner Payable Voucher Register',
        'rental-history': 'Vehicle Rental History',
    }[kind];
}

function descriptionFor(kind: VehicleRentalReportKind): string {
    return {
        'running-chart': 'Physical daily usage with customer, owner, vehicle, driver, kilometre and overtime context.',
        'chart-exceptions': 'Assignment dates with missing charts or duplicate current operational evidence.',
        'customer-invoices': 'Posted customer rental invoices traced to calculations and Running Charts.',
        'owner-vouchers': 'Posted self-billed owner settlements traced to calculations and Running Charts.',
        'rental-history': 'Customer-use assignment history with owner source, replacement lineage and finalized chart totals.',
    }[kind];
}
