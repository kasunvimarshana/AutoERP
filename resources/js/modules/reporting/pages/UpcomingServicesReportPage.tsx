import { useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { humanize } from '@/shared/utils/object';
import { ExportActions } from '../components/ExportActions';
import { runUpcomingServicesReport } from '../reportingApi';
import type {
    UpcomingServiceRow,
    UpcomingServicesParams,
    UpcomingStatusPreset,
    UpcomingUrgencyStatus,
} from '../upcomingServicesTypes';

const reportKey = 'vehicle-service/upcoming-services';

const jobStatuses = [
    'draft',
    'inspected',
    'in_progress',
    'completed',
    'invoiced',
    'partially_paid',
    'paid',
];

const urgencyBadgeStyles: Record<UpcomingUrgencyStatus, string> = {
    overdue: 'bg-rose-100 text-rose-800 border border-rose-200 font-semibold',
    due_today: 'bg-amber-100 text-amber-800 border border-amber-200 font-semibold',
    due_this_week: 'bg-yellow-100 text-yellow-800 border border-yellow-200 font-semibold',
    due_this_month: 'bg-sky-100 text-sky-800 border border-sky-200 font-medium',
    upcoming: 'bg-emerald-100 text-emerald-800 border border-emerald-200 font-medium',
};

const urgencyDotStyles: Record<UpcomingUrgencyStatus, string> = {
    overdue: 'bg-rose-500 animate-pulse',
    due_today: 'bg-amber-500',
    due_this_week: 'bg-yellow-500',
    due_this_month: 'bg-sky-500',
    upcoming: 'bg-emerald-500',
};

export default function UpcomingServicesReportPage() {
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);

    const [filters, setFilters] = useState<UpcomingServicesParams>({
        page: 1,
        per_page: 25,
        status_preset: 'all',
        latest_only: true,
    });
    const [draft, setDraft] = useState<UpcomingServicesParams>(filters);

    const report = useApi(
        (signal) =>
            runUpcomingServicesReport(
                {
                    ...filters,
                    vehicle_id: vehicle?.id,
                    customer_id: customer?.id,
                },
                signal,
            ),
        [filters, vehicle?.id, customer?.id],
    );

    const { data: result, loading, error } = report;

    const selectPreset = (preset: UpcomingStatusPreset) => {
        const nextFilters: UpcomingServicesParams = {
            ...filters,
            status_preset: preset,
            date_from: undefined,
            date_to: undefined,
            page: 1,
        };
        setDraft(nextFilters);
        setFilters(nextFilters);
    };

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setFilters({
            ...draft,
            vehicle_id: vehicle?.id,
            customer_id: customer?.id,
            page: 1,
        });
    };

    const reset = () => {
        const resetFilters: UpcomingServicesParams = {
            page: 1,
            per_page: 25,
            status_preset: 'all',
            latest_only: true,
        };
        setVehicle(null);
        setCustomer(null);
        setDraft(resetFilters);
        setFilters(resetFilters);
    };

    const summary = result?.summary;
    const currentPreset = filters.status_preset ?? 'all';

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Upcoming Services Report"
                description="Monitor upcoming service intervals and proactively follow up on vehicles due or overdue for scheduled service."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />

            <ErrorAlert error={error} title="Could not load upcoming services report" />

            {/* KPI Summary Cards */}
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <button
                    type="button"
                    onClick={() => selectPreset('all')}
                    className={`rounded-xl border p-4 text-left shadow-2xs transition-all ${
                        currentPreset === 'all'
                            ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-600/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                    }`}
                >
                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Scheduled</div>
                    <div className="mt-2 text-2xl font-bold text-slate-900">{summary ? summary.total_upcoming : '—'}</div>
                    <div className="mt-1 text-xs text-slate-500">All vehicles with upcoming service</div>
                </button>

                <button
                    type="button"
                    onClick={() => selectPreset('overdue')}
                    className={`rounded-xl border p-4 text-left shadow-2xs transition-all ${
                        currentPreset === 'overdue'
                            ? 'border-rose-600 bg-rose-50/50 ring-2 ring-rose-600/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                    }`}
                >
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-semibold uppercase tracking-wider text-rose-700">Overdue</span>
                        {(summary?.total_overdue ?? 0) > 0 && (
                            <span className="inline-flex h-2 w-2 rounded-full bg-rose-500 animate-pulse" />
                        )}
                    </div>
                    <div className="mt-2 text-2xl font-bold text-rose-700">{summary ? summary.total_overdue : '—'}</div>
                    <div className="mt-1 text-xs text-rose-600">Passed service due date</div>
                </button>

                <button
                    type="button"
                    onClick={() => selectPreset('due_today')}
                    className={`rounded-xl border p-4 text-left shadow-2xs transition-all ${
                        currentPreset === 'due_today'
                            ? 'border-amber-600 bg-amber-50/50 ring-2 ring-amber-600/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                    }`}
                >
                    <div className="text-xs font-semibold uppercase tracking-wider text-amber-700">Due Today</div>
                    <div className="mt-2 text-2xl font-bold text-amber-700">{summary ? summary.total_due_today : '—'}</div>
                    <div className="mt-1 text-xs text-amber-600">Expected today</div>
                </button>

                <button
                    type="button"
                    onClick={() => selectPreset('due_this_week')}
                    className={`rounded-xl border p-4 text-left shadow-2xs transition-all ${
                        currentPreset === 'due_this_week'
                            ? 'border-yellow-600 bg-yellow-50/50 ring-2 ring-yellow-600/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                    }`}
                >
                    <div className="text-xs font-semibold uppercase tracking-wider text-yellow-800">Next 7 Days</div>
                    <div className="mt-2 text-2xl font-bold text-yellow-800">{summary ? summary.total_due_this_week : '—'}</div>
                    <div className="mt-1 text-xs text-yellow-700">Due within one week</div>
                </button>

                <button
                    type="button"
                    onClick={() => selectPreset('due_this_month')}
                    className={`rounded-xl border p-4 text-left shadow-2xs transition-all ${
                        currentPreset === 'due_this_month'
                            ? 'border-sky-600 bg-sky-50/50 ring-2 ring-sky-600/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                    }`}
                >
                    <div className="text-xs font-semibold uppercase tracking-wider text-sky-800">Next 30 Days</div>
                    <div className="mt-2 text-2xl font-bold text-sky-800">{summary ? summary.total_due_this_month : '—'}</div>
                    <div className="mt-1 text-xs text-sky-700">Due in 8 to 30 days</div>
                </button>
            </div>

            {/* Filter Section */}
            <Panel title="Filters & Scope">
                <form onSubmit={apply} className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Input
                            label="Search"
                            placeholder="Registration, Customer, Job no…"
                            value={draft.search ?? ''}
                            onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value }))}
                        />

                        <Input
                            label="Next service from"
                            type="date"
                            value={draft.date_from ?? ''}
                            onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value }))}
                        />

                        <Input
                            label="Next service to"
                            type="date"
                            value={draft.date_to ?? ''}
                            onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value }))}
                        />

                        <Select
                            label="Job status"
                            value={draft.job_status ?? ''}
                            options={[
                                { value: '', label: 'All statuses' },
                                ...jobStatuses.map((status) => ({
                                    value: status,
                                    label: humanize(status),
                                })),
                            ]}
                            onChange={(event) => setDraft((current) => ({ ...current, job_status: event.target.value || undefined }))}
                        />
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <VehicleLookupSelect
                            value={vehicle}
                            onChange={(selected) => {
                                setVehicle(selected);
                                setDraft((current) => ({ ...current, vehicle_id: selected?.id }));
                            }}
                            kind="all"
                        />

                        <CustomerLookupSelect
                            value={customer}
                            onChange={(selected) => {
                                setCustomer(selected);
                                setDraft((current) => ({ ...current, customer_id: selected?.id }));
                            }}
                            lookupKind="all"
                        />
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                checked={draft.latest_only ?? true}
                                onChange={(event) => setDraft((current) => ({ ...current, latest_only: event.target.checked }))}
                            />
                            <span>Latest service per vehicle only (omit superseded history)</span>
                        </label>

                        <div className="flex items-center gap-2">
                            <Button type="button" variant="secondary" onClick={reset}>
                                Reset
                            </Button>
                            <Button type="submit" loading={loading}>
                                Apply filters
                            </Button>
                        </div>
                    </div>
                </form>
            </Panel>

            {/* Records Header & Actions */}
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <div>
                    <h2 className="text-base font-bold text-slate-900">Upcoming Services</h2>
                    <p className="mt-0.5 text-sm text-slate-500">
                        {loading
                            ? 'Refreshing records…'
                            : `${result?.meta?.total ?? 0} service schedules found`}
                    </p>
                </div>
                <ExportActions
                    reportKey={reportKey}
                    params={{
                        ...filters,
                        vehicle_id: vehicle?.id,
                        customer_id: customer?.id,
                    }}
                />
            </div>

            {/* Table or Loading / Empty States */}
            {loading && !result ? (
                <LoadingState label="Loading upcoming services..." />
            ) : result && result.data.length > 0 ? (
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xs">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                                <tr>
                                    <th scope="col" className="px-4 py-3">Due Status</th>
                                    <th scope="col" className="px-4 py-3">Next Service Date</th>
                                    <th scope="col" className="px-4 py-3">Vehicle</th>
                                    <th scope="col" className="px-4 py-3">Customer</th>
                                    <th scope="col" className="px-4 py-3">Last Service</th>
                                    <th scope="col" className="px-4 py-3">Odometer</th>
                                    <th scope="col" className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white text-slate-800">
                                {result.data.map((job) => (
                                    <UpcomingServiceTableRow key={job.id} job={job} />
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {result.meta.last_page > 1 && (
                        <div className="border-t border-slate-200 px-4 py-3">
                            <Pagination
                                meta={result.meta}
                                onPageChange={(page) => setFilters((current) => ({ ...current, page }))}
                            />
                        </div>
                    )}
                </div>
            ) : (
                <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
                    <div className="text-base font-semibold text-slate-900">No upcoming services found</div>
                    <p className="mt-1 text-sm text-slate-500">
                        Try selecting another status preset, clearing the vehicle or customer filter, or widening the date range.
                    </p>
                    <div className="mt-4">
                        <Button type="button" variant="secondary" onClick={reset}>
                            Reset filters
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

function UpcomingServiceTableRow({ job }: { job: UpcomingServiceRow }) {
    const badgeStyle = urgencyBadgeStyles[job.urgency_status] || urgencyBadgeStyles.upcoming;
    const dotStyle = urgencyDotStyles[job.urgency_status] || urgencyDotStyles.upcoming;

    return (
        <tr className="transition-colors hover:bg-slate-50/70">
            {/* Due Status */}
            <td className="whitespace-nowrap px-4 py-3.5">
                <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs ${badgeStyle}`}>
                    <span className={`h-1.5 w-1.5 rounded-full ${dotStyle}`} />
                    <span>{job.urgency_label}</span>
                </span>
            </td>

            {/* Next Service Date */}
            <td className="whitespace-nowrap px-4 py-3.5 font-medium text-slate-900">
                {formatDate(job.next_service_date)}
            </td>

            {/* Vehicle Details */}
            <td className="px-4 py-3.5">
                <div className="font-semibold text-slate-950">{job.vehicle.registration_number}</div>
                <div className="text-xs text-slate-500">
                    {job.vehicle.make || job.vehicle.model ? (
                        <span>
                            {[job.vehicle.make, job.vehicle.model].filter(Boolean).join(' ')}
                            {job.vehicle.year ? ` (${job.vehicle.year})` : ''}
                        </span>
                    ) : (
                        <span>Vehicle #{job.vehicle.vehicle_number || job.vehicle.id}</span>
                    )}
                </div>
            </td>

            {/* Customer Details */}
            <td className="px-4 py-3.5">
                <div className="font-medium text-slate-900">{job.customer.name}</div>
                <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    {job.customer.phone && (
                        <a
                            href={`tel:${job.customer.phone}`}
                            className="text-blue-600 hover:underline"
                            title="Call customer"
                        >
                            {job.customer.phone}
                        </a>
                    )}
                    {job.customer.code && (
                        <span className="rounded bg-slate-100 px-1 py-0.2 text-[10px] text-slate-600 font-mono">
                            {job.customer.code}
                        </span>
                    )}
                </div>
            </td>

            {/* Last Service Details */}
            <td className="px-4 py-3.5">
                <div className="flex items-center gap-1.5">
                    <Link
                        to={`/vehicle-service/jobs/${job.id}`}
                        className="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                    >
                        {job.job_number}
                    </Link>
                    <StatusBadge status={job.job_status} label={job.job_status_label} />
                </div>
                <div className="mt-0.5 text-xs text-slate-500">
                    <span>{formatDate(job.job_date)}</span>
                    <span className="mx-1">•</span>
                    <span>{job.job_type_label}</span>
                </div>
            </td>

            {/* Odometer Details */}
            <td className="whitespace-nowrap px-4 py-3.5 text-xs text-slate-600">
                {job.odometer_reading ? (
                    <div>
                        <span className="text-slate-400">Last: </span>
                        <span className="font-medium text-slate-900">{formatQuantity(job.odometer_reading)}</span>
                        {job.vehicle.odometer_unit && <span className="ml-0.5 text-slate-500">{job.vehicle.odometer_unit}</span>}
                    </div>
                ) : (
                    <span className="text-slate-400">—</span>
                )}
                {job.next_service_mileage && (
                    <div className="mt-0.5">
                        <span className="text-slate-400">Target: </span>
                        <span className="font-medium text-blue-700">{formatQuantity(job.next_service_mileage)}</span>
                        {job.vehicle.odometer_unit && <span className="ml-0.5 text-slate-500">{job.vehicle.odometer_unit}</span>}
                    </div>
                )}
            </td>

            {/* Actions */}
            <td className="whitespace-nowrap px-4 py-3.5 text-right">
                <div className="flex items-center justify-end gap-2">
                    <Link
                        to={`/vehicle-service/jobs/${job.id}`}
                        className="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-2xs hover:bg-slate-50"
                    >
                        View Job
                    </Link>
                    <Link
                        to={`/vehicle-service/jobs/create?vehicle_id=${job.vehicle.id}&customer_id=${job.customer.id}`}
                        className="rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-medium text-white shadow-2xs hover:bg-blue-500"
                    >
                        New Service
                    </Link>
                </div>
            </td>
        </tr>
    );
}
