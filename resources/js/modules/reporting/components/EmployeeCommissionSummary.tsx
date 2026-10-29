import { formatMoney } from '@/shared/utils/formatMoney';
import type { EmployeeCommissionReportResult } from '../reportingTypes';

export function EmployeeCommissionSummary({ result, periodView }: { result: EmployeeCommissionReportResult; periodView?: boolean }) {
    const metrics = periodView ? [
        ['Total incentive', result.summary.total_commission],
        ['EOM eligible (earned)', result.summary.earned_commission],
        ['Pending for jobs dated in period', result.summary.pending_commission],
    ] : [
        ['Total commission', result.summary.total_commission],
        ['Earned commission', result.summary.earned_commission],
        ['Pending commission', result.summary.pending_commission],
        ['Cancelled commission', result.summary.cancelled_commission],
    ];

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <dl className={`grid gap-4 sm:grid-cols-2 ${periodView ? 'xl:grid-cols-3' : 'xl:grid-cols-4'}`}>
                {metrics.map(([label, value]) => (
                    <div key={label}>
                        <dt className="text-xs font-semibold uppercase text-slate-500">{label}</dt>
                        <dd className="mt-2 text-lg font-bold text-slate-900">{formatMoney(value)}</dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
