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
                description="Review recent Vehicle Service jobs or filter the history by vehicle registration number."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} title="Could not load vehicle service history" />
            <div className="space-y-5">
                <Panel title="Filter by vehicle">
                    <div className="max-w-xl">
                        <VehicleLookupSelect value={vehicle} onChange={(selected) => {
                            setVehicle(selected);
                            setFilters((current) => ({ ...current, page: 1, vehicle_id: selected?.id }));
                            setDraft((current) => ({ ...current, page: 1, vehicle_id: selected?.id }));
                        }} kind="all" />
                        <p className="mt-2 text-sm text-slate-500">Search works with formats such as ABC-1234, ABC 1234, or ABC1234.</p>
                    </div>
                </Panel>
                <>
                        {result?.vehicle && <VehicleHeader result={result} />}
                        <Panel title="Filter service jobs">
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
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-sm text-slate-500">{loading ? 'Refreshing...' : `${result?.meta.total ?? 0} service jobs`}</span>
                            <ExportActions reportKey={reportKey} params={{ ...filters, vehicle_id: vehicle?.id }} />
                        </div>
                        {loading && !result ? <LoadingState label="Loading vehicle history..." /> : (
                            <div className="space-y-3">
                                {(result?.data ?? []).map((job) => <ServiceJobCard key={job.id} job={job} />)}
                                {result?.data.length === 0 && <div className="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">No service jobs match these filters.</div>}
                            </div>
                        )}
                        <Pagination meta={result?.meta} onPageChange={(page) => setFilters((current) => ({ ...current, page }))} />
                </>
            </div>
        </>
    );
}

function VehicleHeader({ result }: { result: VehicleServiceHistoryResult }) {
    const { vehicle, summary } = result;
    if (!vehicle) return null;
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Selected vehicle</div>
                    <h2 className="mt-1 text-xl font-bold text-slate-950">{vehicle.display_name}</h2>
                    <p className="text-sm text-slate-600">{[vehicle.make, vehicle.model, vehicle.manufacture_year].filter(Boolean).join(' · ') || 'Vehicle details not recorded'}</p>
                    {vehicle.current_owner?.name && <p className="mt-1 text-sm text-slate-600">Current owner: {vehicle.current_owner.name}</p>}
                </div>
                <span className="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{humanize(vehicle.status)}</span>
            </div>
            <dl className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Metric label="Service jobs" value={String(summary.total_jobs)} />
                <Metric label="Last service" value={summary.last_service_date ? formatDate(summary.last_service_date) : 'Not recorded'} />
                <Metric label="Last mileage" value={summary.last_service_mileage ? formatQuantity(summary.last_service_mileage) : 'Not recorded'} />
                <Metric label="Next service mileage" value={summary.next_service_mileage ? formatQuantity(summary.next_service_mileage) : 'Not recorded'} />
            </dl>
        </div>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return <div><dt className="text-xs font-semibold uppercase text-slate-500">{label}</dt><dd className="mt-1 font-semibold text-slate-900">{value}</dd></div>;
}

function ServiceJobCard({ job }: { job: VehicleServiceHistoryRow }) {
    const work = job.lines.filter((line) => line.category === 'work');
    const parts = job.lines.filter((line) => line.category === 'part');
    return (
        <article className="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="grid gap-4 p-5 lg:grid-cols-[1.3fr_1fr_1fr_auto] lg:items-center">
                <div>
                    <Link className="text-lg font-bold text-sky-700 hover:underline" to={`/vehicle-service/jobs/${job.id}`}>{job.job_number}</Link>
                    <div className="mt-1 font-semibold text-slate-800">{job.vehicle_label}</div>
                    <div className="mt-1 text-sm text-slate-600">{formatDate(job.job_date)} · {job.customer_name || 'Customer not recorded'}</div>
                </div>
                <div><div className="text-xs font-semibold uppercase text-slate-500">Complaint / work</div><div className="mt-1 text-sm text-slate-800">{job.complaint || job.work_summary || 'Not recorded'}</div></div>
                <div><div className="text-xs font-semibold uppercase text-slate-500">Odometer</div><div className="mt-1 text-sm font-semibold">{job.odometer_reading ? formatQuantity(job.odometer_reading) : 'Not recorded'}</div></div>
                <span className="w-fit rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{humanize(job.status)}</span>
            </div>
            <details className="border-t border-slate-100">
                <summary className="cursor-pointer px-5 py-3 text-sm font-semibold text-sky-700">View service details</summary>
                <div className="grid gap-5 px-5 pb-5 lg:grid-cols-2">
                    <Detail label="Diagnosis" value={job.diagnosis} />
                    <Detail label="Recommended work" value={job.recommended_work} />
                    <Detail label="Technicians" value={job.technician_names} />
                    <Detail label="Supervisor" value={job.supervisor_name} />
                    <LineList title="Work performed" lines={work} />
                    <LineList title="Parts used" lines={parts} />
                    <div className="lg:col-span-2 grid gap-3 rounded-lg bg-slate-50 p-4 sm:grid-cols-3">
                        <Metric label="Invoice total" value={formatMoney(job.invoice_total)} />
                        <Metric label="Paid" value={formatMoney(job.paid_total)} />
                        <Metric label="Balance" value={formatMoney(job.balance_due)} />
                    </div>
                </div>
            </details>
        </article>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><div className="text-xs font-semibold uppercase text-slate-500">{label}</div><p className="mt-1 text-sm text-slate-800">{value || 'Not recorded'}</p></div>;
}

function LineList({ title, lines }: { title: string; lines: VehicleServiceHistoryRow['lines'] }) {
    return <div><div className="text-xs font-semibold uppercase text-slate-500">{title}</div>{lines.length ? <ul className="mt-2 space-y-1 text-sm text-slate-800">{lines.map((line) => <li key={line.id}>• {line.description} ({formatQuantity(line.quantity)} {line.uom ?? ''})</li>)}</ul> : <p className="mt-1 text-sm text-slate-500">Not recorded</p>}</div>;
}
