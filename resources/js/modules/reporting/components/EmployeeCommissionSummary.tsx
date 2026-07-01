import { formatMoney } from '@/shared/utils/formatMoney';
import type { EmployeeCommissionReportResult } from '../reportingTypes';

export function EmployeeCommissionSummary({ result }: { result: EmployeeCommissionReportResult }) {
    const metrics = [
        ['Total commission', result.summary.total_commission],
        ['Technician commission', result.summary.technician_commission],
        ['Supervisor commission', result.summary.supervisor_commission],
    ] as const;

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <dl className="grid gap-4 sm:grid-cols-3">
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
