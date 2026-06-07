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
    if (rows.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">
                {emptyMessage}
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div className="grid gap-3 p-3 md:hidden">
                {rows.map((row) => (
                    <article key={rowKey(row)} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <dl className="grid gap-3 text-sm">
                            {columns.map((column) => (
                                <div key={column.key} className="grid gap-1">
                                    {column.header && <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{column.header}</dt>}
                                    <dd className="min-w-0 text-slate-800">{column.render(row)}</dd>
                                </div>
                            ))}
                        </dl>
                    </article>
                ))}
            </div>
            <div className="hidden overflow-x-auto md:block">
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
            </div>
        </div>
    );
}
