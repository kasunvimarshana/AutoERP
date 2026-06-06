import type { ReactNode } from 'react';

export interface DataColumn<T> {
    key: string;
    header: string;
    render: (row: T) => ReactNode;
    className?: string;
}

export function DataTable<T>({ rows, columns, rowKey, emptyMessage = 'No records found.' }: {
    rows: T[];
    columns: DataColumn<T>[];
    rowKey: (row: T) => string | number;
    emptyMessage?: string;
}) {
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>{columns.map((column) => <th key={column.key} className={`px-4 py-3 font-semibold ${column.className ?? ''}`}>{column.header}</th>)}</tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((row) => (
                            <tr key={rowKey(row)} className="hover:bg-slate-50/70">
                                {columns.map((column) => <td key={column.key} className={`px-4 py-3 text-slate-700 ${column.className ?? ''}`}>{column.render(row)}</td>)}
                            </tr>
                        ))}
                    </tbody>
                </table>
                {rows.length === 0 && <div className="px-4 py-12 text-center text-sm text-slate-500">{emptyMessage}</div>}
            </div>
        </div>
    );
}
