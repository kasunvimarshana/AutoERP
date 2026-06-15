import type { MouseEvent, ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';

export interface DataColumn<T> {
    key: string;
    header: ReactNode;
    render: (row: T) => ReactNode;
    className?: string;
    mobile?: boolean;
}

export function DataTable<T>({ rows, columns, rowKey, emptyMessage = 'No records found.', mobileSummary, mobileDetails, mobileActions, rowBadge, rowHref }: {
    rows: T[];
    columns: DataColumn<T>[];
    rowKey: (row: T) => string | number;
    emptyMessage?: string;
    mobileSummary?: (row: T) => ReactNode;
    mobileDetails?: (row: T) => ReactNode;
    mobileActions?: (row: T) => ReactNode;
    rowBadge?: (row: T) => ReactNode;
    rowHref?: (row: T) => string;
}) {
    const navigate = useNavigate();
    const openRow = (event: MouseEvent, row: T) => {
        if (!rowHref || event.defaultPrevented) return;
        const target = event.target;
        if (target instanceof Element && target.closest('a, button, input, select, textarea, summary')) return;
        navigate(rowHref(row));
    };

    if (rows.length === 0) {
        return (
            <div className="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-14 text-center">
                <span className="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400" aria-hidden="true">-</span>
                <p className="text-sm font-medium text-slate-600">{emptyMessage}</p>
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="grid gap-3 p-3 md:hidden">
                {rows.map((row) => (
                    <article key={rowKey(row)} className={`rounded-xl border border-slate-200 bg-white p-4 shadow-sm ${rowHref ? 'cursor-pointer' : ''}`} onClick={(event) => openRow(event, row)}>
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0 font-semibold text-slate-900">{mobileSummary ? mobileSummary(row) : columns[0]?.render(row)}</div>
                            {rowBadge?.(row)}
                        </div>
                        {mobileDetails ? <div className="mt-3 text-sm text-slate-700">{mobileDetails(row)}</div> : <dl className="mt-3 grid gap-3 text-sm">
                            {columns.filter((column) => column.mobile !== false).slice(1).map((column) => (
                                <div key={column.key} className="grid gap-1">
                                    {column.header && <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{column.header}</dt>}
                                    <dd className="min-w-0 text-slate-800">{column.render(row)}</dd>
                                </div>
                            ))}
                        </dl>}
                        {mobileActions && <div className="mt-4 border-t border-slate-100 pt-3">{mobileActions(row)}</div>}
                    </article>
                ))}
            </div>
            <div className="hidden overflow-x-auto md:block">
                <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead className="bg-slate-50/90 text-xs uppercase tracking-wide text-slate-500">
                        <tr>{columns.map((column) => <th key={column.key} className={`px-4 py-3 font-semibold ${column.className ?? ''}`}>{column.header}</th>)}</tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((row) => (
                            <tr key={rowKey(row)} className={`transition-colors hover:bg-blue-50/40 ${rowHref ? 'cursor-pointer' : ''}`} onClick={(event) => openRow(event, row)}>
                                {columns.map((column) => <td key={column.key} className={`px-4 py-3 text-slate-700 ${column.className ?? ''}`}>{column.render(row)}</td>)}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
