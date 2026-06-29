import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { humanize } from '@/shared/utils/object';
import type { TechnicianWorkReportRow } from '../reportingTypes';

interface ServiceAssignmentTableProps {
    rows: TechnicianWorkReportRow[];
    sortKey?: string;
    direction?: 'asc' | 'desc';
    onSort: (column: string) => void;
}

const columns = [
    ['job_number', 'Job number', 'job_number'],
    ['job_date', 'Job date', 'job_date'],
    ['job_status', 'Job status', 'job_status'],
    ['customer_name', 'Customer', ''],
    ['vehicle_label', 'Vehicle', ''],
    ['employee_name', 'Technician', ''],
    ['role_type', 'Role', 'role_type'],
    ['assigned_hours', 'Hours', 'assigned_hours'],
    ['labour_amount', 'Labour amount', ''],
    ['commission_amount', 'Commission', 'commission_amount'],
    ['invoice_status', 'Invoice status', ''],
    ['payment_document_status', 'Payment document', ''],
    ['payment_posting_status', 'Posting', ''],
    ['payment_allocation_status', 'Allocation', ''],
    ['payment_instrument_status', 'Instrument', ''],
] as const;

export function ServiceAssignmentTable({
    rows,
    sortKey,
    direction,
    onSort,
}: ServiceAssignmentTableProps) {
    if (rows.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">
                No technician work found.
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="min-w-[1900px] divide-y divide-slate-200 text-left text-sm">
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
                        {rows.map((row) => (
                            <tr key={row.id} className="hover:bg-slate-50/70">
                                <td className="whitespace-nowrap px-3 py-3">{row.job_number}</td>
                                <td className="whitespace-nowrap px-3 py-3">{formatDate(row.job_date)}</td>
                                <td className="whitespace-nowrap px-3 py-3">{humanize(row.job_status)}</td>
                                <td className="whitespace-nowrap px-3 py-3">{row.customer_name || '-'}</td>
                                <td className="whitespace-nowrap px-3 py-3">{row.vehicle_label || '-'}</td>
                                <td className="whitespace-nowrap px-3 py-3">{row.employee_name || '-'}</td>
                                <td className="whitespace-nowrap px-3 py-3">{humanize(row.role_type)}</td>
                                <td className="whitespace-nowrap px-3 py-3 text-right">{formatQuantity(row.assigned_hours)}</td>
                                <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.labour_amount)}</td>
                                <td className="whitespace-nowrap px-3 py-3 text-right">{formatMoney(row.commission_amount)}</td>
                                <td className="whitespace-nowrap px-3 py-3">{humanize(row.invoice_status)}</td>
                                <td className="whitespace-nowrap px-3 py-3">{humanize(row.payment_document_status)}</td>
                                <td className="whitespace-nowrap px-3 py-3">{humanize(row.payment_posting_status)}</td>
                                <td className="whitespace-nowrap px-3 py-3">{humanize(row.payment_allocation_status)}</td>
                                <td className="whitespace-nowrap px-3 py-3">{humanize(row.payment_instrument_status)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
