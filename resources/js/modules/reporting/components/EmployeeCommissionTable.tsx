import { useState } from 'react';
import { Link } from 'react-router-dom';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { humanize } from '@/shared/utils/object';
import type {
    EmployeeCommissionGroup,
    EmployeeCommissionReportRow,
} from '../reportingTypes';
import { EmployeeCommissionDrawer } from './EmployeeCommissionDrawer';

interface EmployeeCommissionTableProps {
    rows: EmployeeCommissionReportRow[];
    groups: Map<string, EmployeeCommissionGroup>;
    sortKey?: string;
    direction?: 'asc' | 'desc';
    onSort: (key: string) => void;
    onSelect?: (row: EmployeeCommissionReportRow) => void;
}

const columns = [
    ['employee', 'Employee', 'employee'],
    ['job', 'Job', 'job_number'],
    ['job_date', 'Date', 'job_date'],
    ['operational_status', 'Operational', 'operational_status'],
    ['billing_status', 'Billing', 'billing_status'],
    ['payment_status', 'Payment', 'payment_status'],
    ['customer', 'Customer', 'customer'],
    ['vehicle', 'Vehicle', 'vehicle'],
    ['hours', 'Hours', 'assigned_hours'],
    ['labour', 'Labour value', 'labour_amount'],
    ['commission', 'Commission', 'commission_amount'],
    ['commission_status', 'Commission status', 'commission_status'],
    ['invoice_total', 'Invoice total', ''],
    ['paid_total', 'Paid total', ''],
    ['balance_due', 'Balance due', ''],
] as const;

export function EmployeeCommissionTable({
    rows,
    groups,
    sortKey,
    direction,
    onSort,
    onSelect,
}: EmployeeCommissionTableProps) {
    const [selected, setSelected] = useState<EmployeeCommissionReportRow | null>(null);

    if (rows.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">
                No employee commissions found.
            </div>
        );
    }

    const select = (row: EmployeeCommissionReportRow) => {
        setSelected(row);
        onSelect?.(row);
    };

    return (
        <>
            <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-[1700px] divide-y divide-slate-200 text-left text-sm">
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
                                            <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => select(row)}>
                                                {row.employee_code} {row.employee_name}
                                            </button>
                                        </td>
                                        <td className="whitespace-nowrap px-3 py-3"><Link className="text-sky-700 hover:underline" to={`/vehicle-service/jobs/${row.job.id}`}>{row.job_number}</Link></td>
                                        <td className="whitespace-nowrap px-3 py-3">{formatDate(row.job_date)}</td>
                                        <td className="whitespace-nowrap px-3 py-3">{humanize(row.operational_status)}</td>
                                        <td className="whitespace-nowrap px-3 py-3">{humanize(row.billing_status)}</td>
                                        <td className="whitespace-nowrap px-3 py-3">{humanize(row.payment_status)}</td>
                                        <td className="whitespace-nowrap px-3 py-3">{row.customer_name || '-'}</td>
                                        <td className="whitespace-nowrap px-3 py-3">{row.vehicle_label || '-'}</td>
                                        <td className="whitespace-nowrap px-3 py-3 text-right">{formatQuantity(row.assigned_hours)}</td>
                                        <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.labour_amount)}</td>
                                        <td className="whitespace-nowrap px-3 py-3 text-right font-semibold">{formatMoney(row.commission_amount)}</td>
                                        <td className="whitespace-nowrap px-3 py-3">{humanize(row.commission_status)}</td>
                                        <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.invoice_total)}</td>
                                        <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.paid_total)}</td>
                                        <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.balance_due)}</td>
                                    </tr>,
                                );

                                return result;
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
            <EmployeeCommissionDrawer
                row={selected}
                group={selected ? groups.get(selected.group_key) : undefined}
                onClose={() => setSelected(null)}
            />
        </>
    );
}
