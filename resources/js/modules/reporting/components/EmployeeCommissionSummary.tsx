import { formatMoney } from '@/shared/utils/formatMoney';
import type { EmployeeCommissionReportResult } from '../reportingTypes';

export function EmployeeCommissionSummary({ result }: { result: EmployeeCommissionReportResult }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div className="text-xs font-semibold uppercase text-slate-500">Total commission</div>
            <div className="mt-2 text-lg font-bold text-slate-900">{formatMoney(result.summary.total_commission)}</div>
        </div>
    );
}
