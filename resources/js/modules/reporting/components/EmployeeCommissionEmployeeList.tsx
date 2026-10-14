import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { humanize } from '@/shared/utils/object';
import { useApi } from '@/shared/hooks/useApi';
import { runEmployeeCommissionReport } from '../reportingApi';
import type {
    EmployeeCommissionGroup,
    EmployeeCommissionReportParams,
    EmployeeCommissionReportRow,
} from '../reportingTypes';

export function EmployeeCommissionEmployeeList({ groups, params }: {
    groups: EmployeeCommissionGroup[];
    params: EmployeeCommissionReportParams;
}) {
    const [expandedGroup, setExpandedGroup] = useState<string | null>(null);

    if (groups.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">
                No employee commissions found.
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            {groups.map((group) => {
                const employeeId = getEmployeeId(group);
                const expanded = expandedGroup === group.key;
                const detailsId = `employee-commission-details-${group.key}`;

                return (
                    <section key={group.key} className="border-b border-slate-200 last:border-b-0">
                        <button
                            type="button"
                            className="grid w-full grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-3 p-4 text-left transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 sm:grid-cols-[minmax(0,1.4fr)_minmax(10rem,1fr)_auto_auto] sm:px-5"
                            aria-expanded={expanded}
                            aria-controls={detailsId}
                            disabled={!employeeId}
                            onClick={() => employeeId && setExpandedGroup(expanded ? null : group.key)}
                        >
                            <span className="min-w-0">
                                <strong className="block break-words font-semibold text-slate-900">{group.label}</strong>
                                {group.resource?.code && <span className="mt-0.5 block text-sm text-slate-500">{group.resource.code}</span>}
                            </span>
                            <span className="min-w-0 text-sm text-slate-700 sm:col-start-2 sm:row-start-1">
                                <span className="block text-xs font-medium text-slate-500">Designation</span>
                                <span className="mt-0.5 block break-words">{group.designation_name || '—'}</span>
                            </span>
                            <span className="text-right sm:col-start-3 sm:row-start-1">
                                <span className="block text-xs font-medium text-slate-500">Total incentive</span>
                                <strong className="mt-0.5 block tabular-nums text-slate-950">{formatMoney(group.total_commission)}</strong>
                            </span>
                            <span className="col-span-2 flex items-center justify-between gap-3 border-t border-slate-100 pt-2 text-xs text-slate-500 sm:col-span-1 sm:col-start-4 sm:row-start-1 sm:flex-col sm:items-end sm:justify-center sm:border-0 sm:pt-0">
                                <span>{group.total_jobs} {group.total_jobs === 1 ? 'job' : 'jobs'}</span>
                                <span className="inline-flex items-center gap-2">
                                    <span>Earned {formatMoney(group.earned_commission ?? '0')}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>Pending {formatMoney(group.pending_commission ?? '0')}</span>
                                </span>
                                <span className="sr-only">{expanded ? 'Hide job details' : employeeId ? 'Show job details' : 'No employee details available'}</span>
                                <span className={`text-slate-400 transition-transform ${expanded ? 'rotate-180' : ''}`} aria-hidden="true">⌄</span>
                            </span>
                        </button>
                        {expanded && employeeId && (
                            <div id={detailsId} className="border-t border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                                <EmployeeCommissionDetails
                                    key={`${employeeId}-${JSON.stringify(params)}`}
                                    employeeId={employeeId}
                                    params={params}
                                />
                            </div>
                        )}
                    </section>
                );
            })}
        </div>
    );
}

function EmployeeCommissionDetails({ employeeId, params }: {
    employeeId: number;
    params: EmployeeCommissionReportParams;
}) {
    const [page, setPage] = useState(1);
    const result = useApi(
        (signal) => runEmployeeCommissionReport(cleanParams({
            ...params,
            employee_id: employeeId,
            group_by: 'employee',
            page,
        }), signal),
        [employeeId, page, params],
    );

    return (
        <div aria-busy={result.loading}>
            <ErrorAlert error={result.error} title="Could not load employee job details" />
            {result.loading && !result.data ? <LoadingState label="Loading employee jobs..." /> : (
                <>
                    <div className="space-y-2 md:hidden">
                        {(result.data?.data ?? []).map((row) => <MobileCommissionJob key={row.id} row={row} />)}
                        {result.data?.data.length === 0 && (
                            <p className="rounded-lg border border-slate-200 bg-white px-3 py-5 text-center text-sm text-slate-500">
                                No commission entries for this employee in the selected filters.
                            </p>
                        )}
                    </div>
                    <div className="hidden overflow-x-auto rounded-lg border border-slate-200 bg-white md:block">
                        <table className="min-w-[850px] divide-y divide-slate-200 text-left text-sm">
                            <thead className="bg-white text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-3 py-2 font-semibold">Job</th>
                                    <th className="px-3 py-2 font-semibold">Date</th>
                                    <th className="px-3 py-2 font-semibold">Customer / vehicle</th>
                                    <th className="px-3 py-2 font-semibold">Work</th>
                                    <th className="px-3 py-2 text-right font-semibold">Hours</th>
                                    <th className="px-3 py-2 text-right font-semibold">Commission</th>
                                    <th className="px-3 py-2 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {(result.data?.data ?? []).map((row) => (
                                    <tr key={row.id}>
                                        <td className="whitespace-nowrap px-3 py-2.5">
                                            <Link className="font-semibold text-sky-700 hover:underline" to={`/vehicle-service/jobs/${row.job.id}`}>{row.job_number}</Link>
                                        </td>
                                        <td className="whitespace-nowrap px-3 py-2.5 text-slate-600">{formatDate(row.job_date)}</td>
                                        <td className="max-w-48 px-3 py-2.5 text-slate-700">
                                            <span className="block break-words">{row.customer_name || '—'}</span>
                                            <span className="block break-words text-xs text-slate-500">{row.vehicle_label || '—'}</span>
                                        </td>
                                        <td className="max-w-xs px-3 py-2.5 text-slate-700">
                                            <span className="block break-words">{row.line_description || '—'}</span>
                                            <span className="block text-xs text-slate-500">{humanize(row.role_type)}</span>
                                        </td>
                                        <td className="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{formatQuantity(row.assigned_hours)}</td>
                                        <td className="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums">{formatMoney(row.commission_amount)}</td>
                                        <td className="whitespace-nowrap px-3 py-2.5">
                                            <CommissionStatus status={row.commission_status} />
                                        </td>
                                    </tr>
                                ))}
                                {result.data?.data.length === 0 && (
                                    <tr><td className="px-3 py-5 text-center text-slate-500" colSpan={7}>No commission entries for this employee in the selected filters.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pagination meta={result.data?.meta} onPageChange={setPage} />
                </>
            )}
        </div>
    );
}

function MobileCommissionJob({ row }: { row: EmployeeCommissionReportRow }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-3">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <Link className="font-semibold text-sky-700 hover:underline" to={`/vehicle-service/jobs/${row.job.id}`}>{row.job_number}</Link>
                    <p className="mt-0.5 text-xs text-slate-500">{formatDate(row.job_date)}</p>
                </div>
                <CommissionStatus status={row.commission_status} />
            </div>
            <p className="mt-2 break-words text-sm font-medium text-slate-900">{row.line_description || '—'}</p>
            <p className="text-xs text-slate-500">{humanize(row.role_type)}</p>
            <dl className="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-100 pt-3 text-sm">
                <Detail label="Customer" value={row.customer_name} />
                <Detail label="Vehicle" value={row.vehicle_label} />
                <Detail label="Hours" value={formatQuantity(row.assigned_hours)} />
                <Detail label="Commission" value={formatMoney(row.commission_amount)} />
            </dl>
        </article>
    );
}

function CommissionStatus({ status }: { status: string }) {
    const style = status === 'earned'
        ? 'bg-emerald-100 text-emerald-800'
        : status === 'pending'
            ? 'bg-amber-100 text-amber-800'
            : 'bg-slate-100 text-slate-700';

    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style}`}>{humanize(status)}</span>;
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-slate-500">{label}</dt>
            <dd className="mt-0.5 break-words font-medium text-slate-800">{value || '—'}</dd>
        </div>
    );
}

function getEmployeeId(group: EmployeeCommissionGroup): number | null {
    const id = Number(group.resource?.id);
    return Number.isSafeInteger(id) && id > 0 ? id : null;
}

function cleanParams(params: EmployeeCommissionReportParams): EmployeeCommissionReportParams {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    ) as EmployeeCommissionReportParams;
}
