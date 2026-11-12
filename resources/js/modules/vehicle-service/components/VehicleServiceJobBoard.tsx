import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { VehicleServiceStatusBadge } from './VehicleServiceStatusBadge';
import type { VehicleServiceJob, VehicleServiceJobBoardLane, VehicleServiceJobBoardResult, VehicleServiceJobStatus } from '../vehicleServiceTypes';

const lanes: Array<{
    key: VehicleServiceJobBoardLane;
    label: string;
    statuses: VehicleServiceJobStatus[];
    tone: string;
    accent: string;
}> = [
    { key: 'pending', label: 'Pending', statuses: ['draft', 'inspected'], tone: 'bg-slate-100 text-slate-700', accent: 'bg-slate-400' },
    { key: 'in_progress', label: 'In progress', statuses: ['in_progress'], tone: 'bg-amber-50 text-amber-900', accent: 'bg-amber-500' },
    { key: 'completed', label: 'Completed', statuses: ['completed'], tone: 'bg-emerald-50 text-emerald-900', accent: 'bg-emerald-500' },
    { key: 'billing_closed', label: 'Billing & closed', statuses: ['invoiced', 'partially_paid', 'paid', 'cancelled'], tone: 'bg-blue-50 text-blue-800', accent: 'bg-blue-600' },
];

export function VehicleServiceJobBoard({ result, status, onLoadMore }: {
    result: VehicleServiceJobBoardResult;
    status: VehicleServiceJobStatus | '';
    onLoadMore: (lane: VehicleServiceJobBoardLane) => void;
}) {
    const visibleLanes = status === '' ? lanes : lanes.filter((lane) => lane.statuses.includes(status));
    const total = visibleLanes.reduce((sum, lane) => sum + result.lanes[lane.key].total, 0);

    return (
        <section aria-label="Service job board">
            <p className="mb-3 text-sm font-semibold text-slate-600">{total} {total === 1 ? 'job' : 'jobs'}</p>
            <div className={`grid gap-4 ${visibleLanes.length === 1 ? '' : 'md:grid-cols-2 xl:grid-cols-4'}`}>
                {visibleLanes.map((lane) => {
                    const data = result.lanes[lane.key];
                    return <section key={lane.key} aria-label={`${lane.label} jobs`} className="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100/70">
                        <div className={`flex items-center justify-between gap-3 px-4 py-3 ${lane.tone}`}>
                            <h2 className="flex items-center gap-2 font-bold"><span className={`h-4 w-1 rounded-full ${lane.accent}`} />{lane.label}</h2>
                            <span className="rounded-full bg-white px-2.5 py-0.5 text-sm font-bold tabular-nums">{data.total}</span>
                        </div>
                        <div className="space-y-3 p-3">
                            {data.data.map((job) => <JobTile key={job.id} job={job} />)}
                            {data.data.length === 0 && <p className="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">No jobs match these filters.</p>}
                            {data.has_more && <Button type="button" variant="secondary" className="w-full" onClick={() => onLoadMore(lane.key)}>Load more {lane.label.toLowerCase()} jobs</Button>}
                        </div>
                    </section>;
                })}
            </div>
        </section>
    );
}

function JobTile({ job }: { job: VehicleServiceJob }) {
    return (
        <Link to={`/vehicle-service/jobs/${job.id}`} className="block rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-blue-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" aria-label={`Open ${job.job_number}`}>
            <div className="flex flex-wrap items-start justify-between gap-1">
                <strong className="text-base text-slate-950">{readableRelation(job.vehicle)}</strong>
                <span className="text-xs font-semibold text-blue-700">{job.job_number}</span>
            </div>
            <p className="mt-1 text-sm font-semibold text-slate-800">{job.type_label ?? job.type.replaceAll('_', ' ')}</p>
            <p className="mt-1 truncate text-sm text-slate-600" title={readableRelation(job.customer)}>{readableRelation(job.customer)}</p>
            <div className="mt-3 flex items-center justify-between gap-2">
                <VehicleServiceStatusBadge status={job.status} />
                <span className="text-xs text-slate-500">{formatDate(job.job_date)}</span>
            </div>
            <div className="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2 text-sm">
                <span className="text-slate-500">Job total</span>
                <strong className="tabular-nums text-slate-950"><MoneyDisplay value={job.grand_total} /></strong>
            </div>
        </Link>
    );
}
