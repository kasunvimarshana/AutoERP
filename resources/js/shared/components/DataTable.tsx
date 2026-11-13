import type { KeyboardEvent, MouseEvent, ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';

export interface DataColumn<T> {
    key: string;
    header: ReactNode;
    render: (row: T) => ReactNode;
    footer?: ReactNode;
    className?: string;
    mobile?: boolean;
}

export function DataTable<T>({ rows, columns, rowKey, emptyMessage = 'No records found.', mobileSummary, mobileDetails, mobileActions, rowBadge, rowHref, onRowClick, rowClickEnabled, rowClassName }: {
    rows: T[];
    columns: DataColumn<T>[];
    rowKey: (row: T, index: number) => string | number;
    emptyMessage?: string;
    mobileSummary?: (row: T) => ReactNode;
    mobileDetails?: (row: T) => ReactNode;
    mobileActions?: (row: T) => ReactNode;
    rowBadge?: (row: T) => ReactNode;
    rowHref?: (row: T) => string;
    onRowClick?: (row: T) => void;
    rowClickEnabled?: (row: T) => boolean;
    rowClassName?: (row: T) => string | undefined;
}) {
    const navigate = useNavigate();
    const openRow = (event: MouseEvent | KeyboardEvent, row: T) => {
        const clickEnabled = Boolean(onRowClick) && (rowClickEnabled?.(row) ?? true);
        const isClickable = Boolean(rowHref) || clickEnabled;
        if (!isClickable || event.defaultPrevented) return;
        const target = event.target;
        if (target instanceof Element && target.closest('a, button, input, select, textarea, summary')) return;
        if ('key' in event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
        }
        if (clickEnabled) {
            onRowClick?.(row);
            return;
        }
        if (rowHref) navigate(rowHref(row));
    };

    if (rows.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-2xs">
                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400" aria-hidden="true">
                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <p className="text-sm font-medium text-slate-700">{emptyMessage}</p>
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-2xs">
            <div className="grid gap-3 p-3 md:hidden">
                {rows.map((row, index) => {
                    const clickEnabled = Boolean(onRowClick) && (rowClickEnabled?.(row) ?? true);
                    const isClickable = Boolean(rowHref) || clickEnabled;
                    return <article
                        key={rowKey(row, index)}
                        className={`rounded-xl border border-slate-200 bg-white p-4 shadow-2xs ${rowClassName?.(row) ?? ''} ${isClickable ? 'cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500' : ''}`}
                        tabIndex={isClickable ? 0 : undefined}
                        role={rowHref ? 'link' : isClickable ? 'button' : undefined}
                        onClick={(event) => openRow(event, row)}
                        onKeyDown={(event) => openRow(event, row)}
                    >
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
                    </article>;
                })}
            </div>
            <div className="hidden overflow-x-auto md:block">
                <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase tracking-wider text-slate-600">
                        <tr>{columns.map((column) => <th key={column.key} className={`px-4 py-3 font-semibold ${column.className ?? ''}`}>{column.header}</th>)}</tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((row, index) => {
                            const clickEnabled = Boolean(onRowClick) && (rowClickEnabled?.(row) ?? true);
                            const isClickable = Boolean(rowHref) || clickEnabled;
                            return <tr
                                key={rowKey(row, index)}
                                className={`transition-colors hover:bg-blue-50/40 ${rowClassName?.(row) ?? ''} ${isClickable ? 'cursor-pointer focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500' : ''}`}
                                tabIndex={isClickable ? 0 : undefined}
                                role={rowHref ? 'link' : isClickable ? 'button' : undefined}
                                onClick={(event) => openRow(event, row)}
                                onKeyDown={(event) => openRow(event, row)}
                            >
                                {columns.map((column) => <td key={column.key} className={`px-4 py-3 text-slate-700 align-middle ${column.className ?? ''}`}>{column.render(row)}</td>)}
                            </tr>;
                        })}
                    </tbody>
                    {columns.some((column) => column.footer !== undefined) && (
                        <tfoot className="border-t border-slate-200 bg-slate-50 font-semibold text-slate-900">
                            <tr>
                                {columns.map((column) => (
                                    <td key={column.key} className={`px-4 py-3 ${column.className ?? ''}`}>
                                        {column.footer}
                                    </td>
                                ))}
                            </tr>
                        </tfoot>
                    )}
                </table>
            </div>
        </div>
    );
}
