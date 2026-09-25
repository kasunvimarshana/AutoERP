import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { humanize } from '@/shared/utils/object';
import { ExportActions } from '../components/ExportActions';
import { runVehicleServiceHistoryReport } from '../reportingApi';
import type { VehicleServiceHistoryParams, VehicleServiceHistoryResult, VehicleServiceHistoryRow } from '../reportingTypes';

const reportKey = 'vehicle-service/service-history';
const jobStatuses = ['draft', 'inspected', 'in_progress', 'completed', 'invoiced', 'partially_paid', 'paid', 'cancelled'];
const statusStyles: Record<string, { border: string; badge: string }> = {
    draft: { border: 'border-l-slate-400', badge: 'bg-slate-100 text-slate-700' },
    inspected: { border: 'border-l-violet-500', badge: 'bg-violet-50 text-violet-800' },
    in_progress: { border: 'border-l-amber-500', badge: 'bg-amber-50 text-amber-800' },
    completed: { border: 'border-l-emerald-500', badge: 'bg-emerald-50 text-emerald-800' },
    invoiced: { border: 'border-l-sky-500', badge: 'bg-sky-50 text-sky-800' },
    partially_paid: { border: 'border-l-orange-500', badge: 'bg-orange-50 text-orange-800' },
    paid: { border: 'border-l-teal-600', badge: 'bg-teal-50 text-teal-800' },
    cancelled: { border: 'border-l-rose-500', badge: 'bg-rose-50 text-rose-800' },
};

export default function VehicleServiceHistoryReportPage() {
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [filters, setFilters] = useState<VehicleServiceHistoryParams>({ page: 1, per_page: 10 });
    const [draft, setDraft] = useState(filters);
    const [result, setResult] = useState<VehicleServiceHistoryResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError(null);
        void runVehicleServiceHistoryReport({ ...filters, vehicle_id: vehicle?.id }, controller.signal)
            .then((response) => { if (!controller.signal.aborted) setResult(response); })
            .catch((requestError) => { if (!controller.signal.aborted) setError(toApiError(requestError)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [filters, vehicle]);

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setFilters({ ...draft, page: 1 });
    };

    return (
        <>
            <ContentHeader
                title="Vehicle Service History"
                description="See current service jobs and imported old-system service records in one vehicle timeline."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} title="Could not load vehicle service history" />
            <div className="space-y-5">
                <Panel title="Find a vehicle">
                    <div className="max-w-xl">
                        <VehicleLookupSelect value={vehicle} onChange={(selected) => {
                            setVehicle(selected);
                            setFilters((current) => ({ ...current, page: 1, vehicle_id: selected?.id }));
                            setDraft((current) => ({ ...current, page: 1, vehicle_id: selected?.id }));
                        }} kind="all" />
                        <p className="mt-2 text-sm leading-6 text-slate-500">Search by registration number. Formats such as ABC-1234, ABC 1234, and ABC1234 are accepted.</p>
                    </div>
                </Panel>

                {result?.vehicle && <VehicleHeader result={result} />}

                <Panel title="Narrow the service log">
                    <form className="grid gap-4 md:grid-cols-4" onSubmit={apply}>
                        <Input label="From" type="date" value={draft.date_from ?? ''} onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value }))} />
                        <Input label="To" type="date" value={draft.date_to ?? ''} onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value }))} />
                        <Select label="Job status" value={draft.job_status ?? ''} options={jobStatuses.map((value) => ({ value, label: humanize(value) }))} onChange={(event) => setDraft((current) => ({ ...current, job_status: event.target.value }))} />
                        <Select label="Cancelled jobs" value={draft.include_cancelled ? '1' : '0'} options={[{ value: '0', label: 'Hide cancelled' }, { value: '1', label: 'Include cancelled' }]} onChange={(event) => setDraft((current) => ({ ...current, include_cancelled: event.target.value === '1' }))} />
                        <div className="flex items-end gap-2 md:col-span-4 md:justify-end">
                            <Button type="button" variant="secondary" onClick={() => { const reset = { page: 1, per_page: 10 }; setDraft(reset); setFilters(reset); }}>Reset</Button>
                            <Button type="submit" loading={loading}>Apply filters</Button>
                        </div>
                    </form>
                </Panel>

                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                    <div>
                        <h2 className="text-base font-bold text-slate-950">Service log</h2>
                        <p className="mt-0.5 text-sm text-slate-500">{loading ? 'Refreshing records…' : `${result?.meta?.total ?? 0} service records found`}</p>
                    </div>
                    <ExportActions reportKey={reportKey} params={{ ...filters, vehicle_id: vehicle?.id }} />
                </div>

                {loading && !result ? <LoadingState label="Loading vehicle history..." /> : (
                    <div className="space-y-4">
                        {(result?.data ?? []).map((job) => <ServiceJobCard key={job.id} job={job} />)}
                        {result?.data.length === 0 && (
                            <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
                                <div className="font-semibold text-slate-800">No service records found</div>
                                <p className="mt-1 text-sm text-slate-500">Try a wider date range, another status, or a different vehicle.</p>
                            </div>
                        )}
                    </div>
                )}
                <Pagination meta={result?.meta} onPageChange={(page) => setFilters((current) => ({ ...current, page }))} />
            </div>
        </>
    );
}

function VehicleHeader({ result }: { result: VehicleServiceHistoryResult }) {
    const { vehicle, summary } = result;
    if (!vehicle) return null;

    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="h-1.5 bg-sky-600" />
            <div className="p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div className="text-sm font-medium text-sky-700">Selected vehicle</div>
                        <h2 className="mt-1 text-xl font-bold text-slate-950">{vehicle.display_name}</h2>
                        <p className="text-sm text-slate-600">{[vehicle.make, vehicle.model, vehicle.manufacture_year].filter(Boolean).join(' / ') || 'Vehicle details not recorded'}</p>
                        {vehicle.current_owner?.name && <p className="mt-1 text-sm text-slate-600">Current owner: {vehicle.current_owner.name}</p>}
                    </div>
                    <span className="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{humanize(vehicle.status)}</span>
                </div>
                <dl className="mt-5 grid divide-y divide-slate-200 border-y border-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4">
                    <Metric label="Service jobs" value={String(summary.total_jobs)} />
                    <Metric label="Last service" value={summary.last_service_date ? formatDate(summary.last_service_date) : 'Not recorded'} />
                    <Metric label="Last mileage" value={summary.last_service_mileage ? formatQuantity(summary.last_service_mileage) : 'Not recorded'} />
                    <Metric label="Next service mileage" value={summary.next_service_mileage ? formatQuantity(summary.next_service_mileage) : 'Not recorded'} />
                </dl>
            </div>
        </section>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return <div className="px-3 py-3 first:pl-0 last:pr-0"><dt className="text-sm text-slate-500">{label}</dt><dd className="mt-1 font-bold tabular-nums text-slate-950">{value}</dd></div>;
}

function ServiceJobCard({ job }: { job: VehicleServiceHistoryRow }) {
    const work = job.lines.filter((line) => line.category === 'work');
    const parts = job.lines.filter((line) => line.category === 'part');
    const tone = statusStyles[job.status] ?? statusStyles.draft;

    return (
        <article className={`overflow-hidden rounded-xl border border-l-4 border-slate-200 bg-white shadow-sm ${tone.border}`}>
            <div className="grid gap-5 p-5 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)_auto] lg:items-start">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        {job.live_job_id ? (
                            <Link className="text-lg font-bold text-sky-700 underline-offset-4 hover:underline focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500" to={`/vehicle-service/jobs/${job.live_job_id}`}>{job.job_number}</Link>
                        ) : <span className="text-lg font-bold text-slate-900">{job.job_number}</span>}
                        <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${tone.badge}`}>{humanize(job.status)}</span>
                        <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${job.source === 'legacy' ? 'bg-indigo-50 text-indigo-800' : 'bg-sky-50 text-sky-800'}`}>{job.source_label}</span>
                    </div>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Job type</span>
                        <span className="rounded-md bg-slate-100 px-2 py-1 text-sm font-semibold text-slate-800">{job.job_type_label}</span>
                    </div>
                    <div className="mt-2 font-semibold text-slate-900">{job.vehicle_label}</div>
                    <div className="mt-1 text-sm text-slate-600">{formatDate(job.job_date)}</div>
                    <div className="mt-1 truncate text-sm text-slate-500">{job.customer_name || 'Customer not recorded'}</div>
                </div>
                <div>
                    <div className="text-sm font-medium text-slate-500">{job.source === 'legacy' ? 'Old service items' : 'Complaint or work summary'}</div>
                    <p className="mt-1 line-clamp-2 text-sm leading-6 text-slate-800">{job.complaint || job.work_summary || 'Not recorded'}</p>
                </div>
                <dl className="grid min-w-44 grid-cols-2 gap-x-5 gap-y-3 rounded-lg bg-slate-50 px-4 py-3 lg:grid-cols-1">
                    <CompactMetric label="Odometer" value={job.odometer_reading ? formatQuantity(job.odometer_reading) : 'Not recorded'} />
                    <CompactMetric label="Next service" value={job.next_service_mileage ? formatQuantity(job.next_service_mileage) : 'Not recorded'} />
                </dl>
            </div>
            <details className="border-t border-slate-200">
                <summary className="cursor-pointer bg-slate-50 px-5 py-3 text-sm font-semibold text-sky-700 marker:text-slate-400 hover:bg-sky-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500">{job.source === 'legacy' ? 'View old service items' : 'View full service record'}</summary>
                {job.source === 'legacy' ? (
                    <div className="px-5 py-5">
                        <p className="mb-4 text-sm text-slate-500">This is a read-only record imported from the old AutoERP system.</p>
                        <LineList title="Service items" lines={job.lines} />
                    </div>
                ) : (
                    <div className="grid gap-5 px-5 py-5 lg:grid-cols-2">
                        <Detail label="Inspection notes" value={job.inspection_notes} />
                        <Detail label="Diagnosis" value={job.diagnosis} />
                        <Detail label="Recommended work" value={job.recommended_work} />
                        <Detail label="Technicians" value={job.technician_names} />
                        <Detail label="Supervisor" value={job.supervisor_name} />
                        <LineList title="Work performed" lines={work} />
                        <LineList title="Parts used" lines={parts} />
                        <div className="grid gap-3 border-t border-slate-200 pt-4 sm:grid-cols-3 lg:col-span-2">
                            <Metric label="Invoice total" value={formatMoney(job.invoice_total)} />
                            <Metric label="Paid" value={formatMoney(job.paid_total)} />
                            <Metric label="Balance" value={formatMoney(job.balance_due)} />
                        </div>
                    </div>
                )}
            </details>
        </article>
    );
}

function CompactMetric({ label, value }: { label: string; value: string }) {
    return <div><dt className="text-xs text-slate-500">{label}</dt><dd className="mt-0.5 text-sm font-bold tabular-nums text-slate-900">{value}</dd></div>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><div className="text-sm font-medium text-slate-500">{label}</div><p className="mt-1 text-sm leading-6 text-slate-800">{value || 'Not recorded'}</p></div>;
}

function LineList({ title, lines }: { title: string; lines: VehicleServiceHistoryRow['lines'] }) {
    return <div><div className="text-sm font-medium text-slate-500">{title}</div>{lines.length ? <ul className="mt-2 divide-y divide-slate-100 text-sm text-slate-800">{lines.map((line) => <li className="flex items-start justify-between gap-4 py-2 first:pt-0" key={line.id}><span>{line.description}</span><span className="shrink-0 tabular-nums text-slate-500">{formatQuantity(line.quantity)} {line.uom ?? ''}</span></li>)}</ul> : <p className="mt-1 text-sm text-slate-500">Not recorded</p>}</div>;
}
