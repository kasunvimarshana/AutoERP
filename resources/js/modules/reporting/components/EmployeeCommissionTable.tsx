import { Link } from 'react-router-dom';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { humanize } from '@/shared/utils/object';
import type {
    EmployeeCommissionGroup,
    EmployeeCommissionReportRow,
} from '../reportingTypes';

interface EmployeeCommissionTableProps {
    rows: EmployeeCommissionReportRow[];
    groups: Map<string, EmployeeCommissionGroup>;
    sortKey?: string;
    direction?: 'asc' | 'desc';
    onSort: (key: string) => void;
    onSelect: (row: EmployeeCommissionReportRow) => void;
}

const columns = [
    ['employee', 'Employee', 'employee'],
    ['job', 'Job', 'job_number'],
    ['job_date', 'Date', 'job_date'],
    ['customer', 'Customer', 'customer'],
    ['vehicle', 'Vehicle', 'vehicle'],
    ['hours', 'Hours', 'assigned_hours'],
    ['labour', 'Labour value', 'labour_amount'],
    ['commission', 'Commission', 'commission_amount'],
    ['commission_status', 'Commission status', 'commission_status'],
    ['invoice_progress', 'Invoice progress', ''],
    ['payment_progress', 'Payment progress', ''],
    ['job_status', 'Job status', 'job_status'],
] as const;

export function EmployeeCommissionTable({
    rows,
    groups,
    sortKey,
    direction,
    onSort,
    onSelect,
}: EmployeeCommissionTableProps) {
    if (rows.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">
                No employee commissions found.
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="min-w-[1500px] divide-y divide-slate-200 text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            {columns.map(([key, label, sort]) => (
                                <th key={key} className="whitespace-nowrap px-3 py-3 font-semibold">
                                    {sort ? (
                                        <button type="button" className="font-semibold hover:text-slate-900" onClick={() => onSort(sort)}>
                                            {label}{sortKey === sort ? ` ${direction === 'asc' ? 'Asc' : 'Desc'}` : ''}
                                        </button>
                                    ) : label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.flatMap((row, index) => {
                            const groupChanged = index === 0 || rows[index - 1].group_key !== row.group_key;
                            const group = groups.get(row.group_key);
                            const result = [];

                            if (groupChanged) {
                                result.push(
                                    <tr key={`group-${row.group_key}-${row.id}`} className="bg-sky-50">
                                        <td colSpan={columns.length} className="px-3 py-2 font-semibold text-sky-950">
                                            {row.group_label}
                                            {group && (
                                                <span className="ml-3 font-normal text-sky-800">
                                                    {group.total_jobs} jobs / {formatQuantity(group.total_hours)} hours / {formatMoney(group.total_commission)} commission
                                                </span>
                                            )}
                                        </td>
                                    </tr>,
                                );
                            }

                            result.push(
                                <tr key={row.id} className="hover:bg-slate-50/70">
                                    <td className="whitespace-nowrap px-3 py-3">
                                        <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => onSelect(row)}>
                                            {row.employee_code} {row.employee_name}
                                        </button>
                                    </td>
                                    <td className="whitespace-nowrap px-3 py-3"><Link className="text-sky-700 hover:underline" to={`/vehicle-service/jobs/${row.job.id}`}>{row.job_number}</Link></td>
                                    <td className="whitespace-nowrap px-3 py-3">{formatDate(row.job_date)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{row.customer_name || '-'}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{row.vehicle_label || '-'}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right">{formatQuantity(row.assigned_hours)}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.labour_amount)}</td>
                                    <td className="whitespace-nowrap px-3 py-3 text-right font-semibold">{formatMoney(row.commission_amount)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.commission_status)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.invoice_progress)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.payment_progress)}</td>
                                    <td className="whitespace-nowrap px-3 py-3">{humanize(row.job_status)}</td>
                                </tr>,
                            );

                            return result;
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
