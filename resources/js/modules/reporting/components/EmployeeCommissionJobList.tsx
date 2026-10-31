import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { getEmployeeCommissionJobs } from '../reportingApi';
import type { EmployeeCommissionJobRow } from '../employeeReportResultTypes';
import type { EmployeeCommissionReportParams } from '../reportingTypes';

export function EmployeeCommissionJobList({ employeeId, params }: { employeeId: number; params: EmployeeCommissionReportParams }) {
    const [page, setPage] = useState(1);
    const result = useApi(
        (signal) => getEmployeeCommissionJobs(employeeId, { ...params, page, per_page: 10 }, signal),
        [employeeId, page, params],
    );

    return <div className="space-y-3" aria-busy={result.loading}>
        <p className="text-sm text-slate-600">One row per job. Eligible amounts were earned in the selected period; pending amounts belong to jobs dated in that period.</p>
        <ErrorAlert error={result.error} title="Could not load employee jobs" />
        {result.loading && !result.data ? <LoadingState label="Loading employee jobs..." /> : result.error && !result.data ? null : <>
            <div className="space-y-2 md:hidden">
                {(result.data?.data ?? []).map((row) => <JobCard key={row.job.id} row={row} />)}
            </div>
            <div className="hidden overflow-x-auto rounded-lg border border-slate-200 bg-white md:block">
                <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead className="bg-slate-50 text-xs font-semibold text-slate-600">
                        <tr>
                            <th className="px-3 py-2">Job</th>
                            <th className="px-3 py-2">Completed</th>
                            <th className="px-3 py-2">Job date</th>
                            <th className="px-3 py-2">Customer / vehicle</th>
                            <th className="px-3 py-2 text-right">EOM eligible</th>
                            <th className="px-3 py-2 text-right">Pending</th>
                            <th className="px-3 py-2 text-right">Total incentive</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {(result.data?.data ?? []).map((row) => <tr key={row.job.id}>
                            <td className="px-3 py-2.5"><Link className="font-semibold text-sky-700 hover:underline" to={`/vehicle-service/jobs/${row.job.id}`}>{row.job.number}</Link>{row.entry_count > 1 && <span className="block text-xs text-slate-500">{row.entry_count} commission entries</span>}</td>
                            <td className="whitespace-nowrap px-3 py-2.5">{row.last_earned_at ? formatDate(row.last_earned_at.slice(0, 10)) : '—'}</td>
                            <td className="whitespace-nowrap px-3 py-2.5 text-slate-600">{formatDate(row.job_date)}</td>
                            <td className="px-3 py-2.5"><span className="block">{row.customer_name || '—'}</span><span className="block text-xs text-slate-500">{row.vehicle_label || '—'}</span></td>
                            <td className="px-3 py-2.5 text-right tabular-nums">{formatMoney(row.earned_commission)}</td>
                            <td className="px-3 py-2.5 text-right tabular-nums">{formatMoney(row.pending_commission)}</td>
                            <td className="px-3 py-2.5 text-right font-semibold tabular-nums">{formatMoney(row.total_commission)}</td>
                        </tr>)}
                    </tbody>
                </table>
            </div>
            {result.data?.data.length === 0 && <p className="py-4 text-center text-sm text-slate-500">No jobs match the selected filters.</p>}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>}
    </div>;
}

function JobCard({ row }: { row: EmployeeCommissionJobRow }) {
    return <article className="rounded-lg border border-slate-200 bg-white p-3">
        <div className="flex items-start justify-between gap-3">
            <div><Link className="font-semibold text-sky-700 hover:underline" to={`/vehicle-service/jobs/${row.job.id}`}>{row.job.number}</Link><div className="text-xs text-slate-500">Job {formatDate(row.job_date)}{row.last_earned_at ? ` · Completed ${formatDate(row.last_earned_at.slice(0, 10))}` : ''}</div></div>
            <strong className="text-right tabular-nums">{formatMoney(row.total_commission)}</strong>
        </div>
        <p className="mt-2 text-sm">{row.customer_name || '—'} <span className="text-slate-500">{row.vehicle_label || ''}</span></p>
        <div className="mt-3 flex justify-between border-t border-slate-100 pt-2 text-sm"><span>Eligible {formatMoney(row.earned_commission)}</span><span>Pending {formatMoney(row.pending_commission)}</span></div>
    </article>;
}
