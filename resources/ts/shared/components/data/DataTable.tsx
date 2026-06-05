import type { ReactNode } from 'react';
import { cn } from '../../utils/cn';

export type DataTableColumn<T> = {
    className?: string;
    header: string;
    key: string;
    render?: (row: T) => ReactNode;
};

type DataTableProps<T> = {
    columns: Array<DataTableColumn<T>>;
    dark?: boolean;
    getRowKey: (row: T) => string | number;
    rows: T[];
};

export function DataTable<T>({ columns, dark = false, getRowKey, rows }: DataTableProps<T>) {
    return (
        <div className={cn('overflow-hidden rounded-lg border', dark ? 'border-slate-800 bg-slate-950' : 'border-slate-200 bg-white')}>
            <table className="w-full border-collapse text-left text-sm">
                <thead className={dark ? 'bg-black text-slate-300' : 'bg-slate-50 text-slate-500'}>
                    <tr>
                        {columns.map((column) => (
                            <th className={cn('px-5 py-3 text-xs font-bold uppercase tracking-widest', column.className)} key={column.key}>
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className={dark ? 'divide-y divide-slate-800 text-slate-200' : 'divide-y divide-slate-100 text-slate-600'}>
                    {rows.map((row) => (
                        <tr className={dark ? 'hover:bg-slate-900' : 'hover:bg-slate-50/70'} key={getRowKey(row)}>
                            {columns.map((column) => (
                                <td className={cn('px-5 py-4 align-middle', column.className)} key={column.key}>
                                    {column.render ? column.render(row) : String((row as Record<string, unknown>)[column.key] ?? '')}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
